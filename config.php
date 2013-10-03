<?php

$webroot = '/what/where/Sites/league2/';

require_once($webroot.'smarty/libs/Smarty.class.php');
$smarty = new Smarty();

$smarty->template_dir = $webroot.'template/';
$smarty->compile_dir = $webroot.'template_c/';
$smarty->cache_dir = $webroot.'cache/';
$smarty->config_dir = $webroot.'configs/';

unset($webroot);

$database = & new database('127.0.0.1','league2','league2','league2');

$debug = TRUE;
$debug_xml_log = TRUE;
$debug_sql_slow_log = TRUE;
$debug_skip_backend_checksum = TRUE;
$debug_skip_flood_protection = FALSE;
$debug_skip_session_path = TRUE;
$debug_skip_resource_checksum = TRUE;

$cfg_official_server = array('1.2.3.4','1.2.3.4');
$cfg_settle_on_official_server_only = false;
$cfg_settle_with_latest_engine_only = false;
?>