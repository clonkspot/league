<?php

$webroot = 'D:/www/league2/';
//$webroot = '/is/htdocs/wp1144497_5BDDWAFPQY/openclonk/league/';

// MwfAuth path
require_once($_SERVER['DOCUMENT_ROOT'].'/../auth/mwf_auth.php');

require_once('smarty/libs/SmartyBC.class.php');
$smarty = new SmartyBC();

$smarty->template_dir = $webroot.'template/';
$smarty->compile_dir = $webroot.'template_c/';
$smarty->cache_dir = $webroot.'cache/';
$smarty->config_dir = $webroot.'configs/';

unset($webroot);

//$database = new database('127.0.0.1','db1144497-league',geheim,geheim);
$database = new database('127.0.0.1','league2','CC67eWMqKcsHJ8UG','league2');

$debug = FALSE;
$debug_xml_log = FALSE;
$debug_sql_slow_log = FALSE;
$debug_skip_backend_checksum = TRUE;
$debug_skip_flood_protection = TRUE;
$debug_skip_session_path = TRUE;
$debug_skip_resource_checksum = TRUE;

$cfg_official_server = array('1.2.3.4','1.2.3.4');
$cfg_settle_on_official_server_only = false;
$cfg_settle_with_latest_engine_only = false;

$cronjob_password = '';

//$smarty->assign("helplink",'http://wiki.openclonk.org/w/FAQ');
?>
