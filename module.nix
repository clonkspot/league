{ config, lib, pkgs, ... }:

let
  inherit (lib.options) mkEnableOption mkOption;

  writePhpFile = name: text: pkgs.writeTextFile {
    inherit name;
    text = "<?php\n${text}";
    checkPhase = "${pkgs.php}/bin/php --syntax-check $target";
  };

  cfg = config.services.league;
  webserver = config.services.caddy;
  configFile = writePhpFile "league-config.php" (''
    ini_set('default_charset', 'ISO-8859-1');
    require 'vendor/autoload.php';
    Smarty::$_CHARSET = 'ISO-8859-1';

    $smarty = new Smarty();
    $webroot = __DIR__.'/';
    $smarty->template_dir = $webroot.'template/';
    $smarty->compile_dir = '${cfg.stateDir}/template_c/';
    $smarty->cache_dir = '${cfg.stateDir}/cache/';
    $smarty->config_dir = $webroot.'configs/';
    $smarty->addPluginsDir($webroot.'smarty_plugins/');

    $log_folder = "${cfg.stateDir}/logs/";
    $record_folder = "${cfg.stateDir}/records/";
    $statistics_db = "${cfg.stateDir}/data/statistics.sqlite";
    $base_path = '/';

    unset($webroot);

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
      $remote_ip_address = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    else
    {
      $remote_ip_address = $_SERVER['REMOTE_ADDR'];
    }

    $redis = new Predis\Client(['scheme' => 'unix', 'path' => '${config.services.redis.servers.league.unixSocket}']);
  '' + (lib.optionalString cfg.enableMysql ''
    $database = new database('localhost', '${cfg.user}', "", 'league');
  '') + cfg.extraConf);
  smartyConfigFileDE = pkgs.writeText "smarty-de.conf" ''
    header = "${cfg.headerFileDE}"
    footer = "${cfg.footerFileDE}"
  '';
  smartyConfigFileEN = pkgs.writeText "smarty-en.conf" ''
    header = "${cfg.headerFileEN}"
    footer = "${cfg.footerFileEN}"
  '';
  pkg = pkgs.league.override {
    inherit configFile smartyConfigFileDE smartyConfigFileEN;
  };

  gameEventsListen = "[::1]:62318";
in
{
  options.services.league = {
    enable = mkEnableOption "Clonk league server";

    enableMysql = mkEnableOption "automatic MySQL server setup";

    hostname = mkOption {
      type = lib.types.str;
      description = "hostname the league should be served from";
      example = "league.example.com";
    };

    user = mkOption {
      type = lib.types.str;
      description = "user the PHP app runs as";
      default = "league";
    };

    stateDir = mkOption {
      type = lib.types.path;
      description = "Location of the league state directory";
      default = "/var/lib/league";
    };

    extraConf = mkOption {
      type = lib.types.str;
      description = "extra configuration (config.php)";
      default = ''
        $debug = FALSE;
        $debug_xml_log = FALSE;
        $debug_sql_slow_log = FALSE;
        $debug_skip_backend_checksum = TRUE;
        $debug_skip_flood_protection = TRUE;
        $debug_skip_session_path = TRUE;
        $debug_skip_resource_checksum = TRUE;

        $cfg_official_server = array();
        $cfg_settle_on_official_server_only = false;
        $cfg_settle_with_latest_engine_only = false;
      '';
    };

    headerFileDE = mkOption {
      type = lib.types.path;
      description = "Path to header HTML file";
    };

    footerFileDE = mkOption {
      type = lib.types.path;
      description = "Path to footer HTML file";
    };

    headerFileEN = mkOption {
      type = lib.types.path;
      description = "Path to header HTML file";
    };

    footerFileEN = mkOption {
      type = lib.types.path;
      description = "Path to footer HTML file";
    };

    poolConfig = mkOption {
      type = with lib.types; attrsOf (oneOf [ str int bool ]);
      default = {
        "pm" = "dynamic";
        "pm.max_children" = 32;
        "pm.start_servers" = 2;
        "pm.min_spare_servers" = 2;
        "pm.max_spare_servers" = 4;
        "pm.max_requests" = 500;
      };
      description = ''
        Options for the league PHP pool. See the documentation on `php-fpm.conf`
        for details on configuration directives.
      '';
    };
  };

  config = lib.mkIf cfg.enable {
     users.users.${cfg.user} = {
      group = webserver.group;
      isSystemUser = true;
    };

    systemd.tmpfiles.rules = [
      "d ${cfg.stateDir}/template_c 0700 ${cfg.user} ${webserver.group} - -"
      "d ${cfg.stateDir}/cache      0700 ${cfg.user} ${webserver.group} - -"
      "d ${cfg.stateDir}/records    0750 ${cfg.user} ${webserver.group} - -"
      "d ${cfg.stateDir}/data       0750 ${cfg.user} ${webserver.group} - -"
      "d ${cfg.stateDir}/logs       0700 ${cfg.user} ${webserver.group} - -"
    ];

    systemd.timers.league-5min = {
      wantedBy = [ "timers.target" ];
      timerConfig = {
        OnStartupSec = "5min";
        OnUnitActiveSec = "5min";
      };
    };
    systemd.services.league-5min = {
      description = "Clonk league 5 min cronjob";
      serviceConfig = {
        Type = "oneshot";
        ExecStart = "${pkgs.php}/bin/php ${pkg}/share/php/league/cronjob_5min.php";
        User = cfg.user;
      };
    };
    systemd.services.league-daily = {
      description = "Clonk league daily cronjob";
      startAt = "04:00:00";
      serviceConfig = {
        Type = "oneshot";
        ExecStart = "${pkgs.php}/bin/php ${pkg}/share/php/league/cronjob_daily.php";
        User = cfg.user;
      };
    };

    services.phpfpm.pools.league = {
      user = cfg.user;
      group = webserver.group;
      settings = {
        "listen.owner" = webserver.user;
        "listen.group" = webserver.group;
      } // cfg.poolConfig;
    };

    services.redis = {
      servers.league = {
        enable = true;
        user = cfg.user;
        group = webserver.group;
      };
    };

    services.mysql = lib.mkIf cfg.enableMysql {
      enable = true;
      package = pkgs.mariadb;
      initialDatabases = [
        { name = "league"; schema = ./table_structure.sql; }
      ];
      ensureUsers = [
        {
          name = cfg.user;
          ensurePermissions = {
            "league.*" = "ALL PRIVILEGES";
          };
        }
      ];
    };

    systemd.services.league-game-events = {
      description = "Clonk league game events server";
      wantedBy = [ "multi-user.target" ];
      after = [ "redis-league.service" ];
      environment = {
        LISTEN_URL = gameEventsListen;
        REDIS_URL = "unix://${config.services.redis.servers.league.unixSocket}";
      };
      serviceConfig = {
        Type = "exec";
        ExecStart = "${pkgs.league-game-events}/bin/league-game-events";
        Restart = "on-failure";
        User = cfg.user;
        ProtectSystem = "strict";
      };
    };

    services.caddy = {
      enable = true;
      virtualHosts.${cfg.hostname}.extraConfig = ''
        root * ${pkg}/share/php/league
        encode zstd gzip

        route {
          @static_files path /images/* *.css
          file_server @static_files
          @dyn_files path /records/* /data/*
          file_server @dyn_files {
            root ${cfg.stateDir}
          }

          error /cronjob_*.php 403

          # CR and LC request league.clonkspot.org
          @clonk {
            header User-Agent *Clonk*
            path /
          }
          rewrite @clonk /league.php

          # Pretty URLs
          @pretty path_regexp ^/(\w+)/(\w+)$
          rewrite @pretty /index.php?{query}&part={re.1}&method={re.2}

          php_fastcgi unix/${config.services.phpfpm.pools.league.socket} {
            try_files {path} {path}/index.php
          }

          @game_events path /game_events /game_events.php /poll_game_events.php
          reverse_proxy @game_events ${gameEventsListen}

          error 404
        }
      '';
    };
  };
}
