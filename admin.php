<?php

require_once('lib/Debug.class.php');
require_once('lib/database.class.php');
require_once('config.php');

session_start();

//error_reporting(E_ALL);
//ini_set('display_errors', true);


require_once('lib/log.class.php');

// Newer versions of smarty seem to turn the template dir into arrays internally
if (is_array($smarty->template_dir))
	$smarty->template_dir = $smarty->template_dir[0].'admin/';
else
	$smarty->template_dir = $smarty->template_dir.'admin/';
$smarty->compile_dir = $smarty->compile_dir.'admin/';
require_once('lib/language.class.php');
require_once('lib/user.class.php');
$language = new language();
$language->load_stringtable();

$user = new user();
$user->session_login();
$login_user = $user;

require_once('lib/message_box.class.php');
$message_box = new message_box();

if($user->is_logged_in() && $user->is_admin())
{
	if($user->check_admin_permission(@$_REQUEST['part'],@$_REQUEST['method']))
	{
		switch(@$_REQUEST['part']) {
			case 'scenario':
			{
				require_once('lib/scenario.class.php');
				switch(@$_POST['method']) {
					case 'add2':
					{
						$scen = new scenario();
						$scen->add($_POST['scenario'],$_POST['versions'],@$_POST['leagues']);
						$scen->show_list();
						break;	
					}	
					case 'edit2':
					{
						$scen = new scenario();
						$scen->edit($_POST['scenario'],$_POST['versions'],$_POST['scenarios_merge'],@$_POST['leagues']);
						$scen->show_list();
						break;	
					}
					case 'delete2':
					{
						$scen = new scenario();
						$scen->delete($_POST['scenario']['id']);
						$scen->show_list();
						break;	
					}
					case 'delete_never_played2':
					{
						$scen = new scenario();
						$scen->delete_never_played();
						$scen->show_list();
						break;	
					}
				}
				switch(@$_REQUEST['method']) {
					case 'list':
					{
						$scen = new scenario();
						$scen->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;	
					}
					case 'add':
					{
						$scen = new scenario();
						$scen->show_add();
						break;	
					}
					case 'edit':
					{
						$scen = new scenario();
						$scen->show_edit($_REQUEST['scenario']['id']);
						break;	
					}
				}
			break;
			}
			case 'league':
			{
				require_once('lib/league.class.php');
				require_once('lib/league_settle.class.php');
				require_once('lib/league_settle_custom.class.php');
				switch(@$_POST['method']) {
					case 'add2':
					{
						$league = new league();
						$league->add($_POST['league']);
						$league->show_list();
						break;	
					}
					case 'edit2':
					{
						$league = new league();
						$league->edit($_POST['league']);
						$league->show_list();
						break;
					}
					case 'delete2':
					{
						$league = new league();
						$league->delete($_POST['league']['id']);
						$league->show_list();
						break;
					}
					case 'calculate_ranks2':
					{
						$league = new league();
						$league->load_data($_POST['league']['id']);
						$league->calculate_ranks();
						break;
					}
					case 'calculate_scores2':
					{
						$league = new league();
						$league->load_data($_POST['league']['id']);
						if($league->data['type'] == 'settle')
						{
							if($league->is_custom_scoring())
							{
								$league_settle = new league_settle_custom();
								$league_settle->load_data($_POST['league']['id']);
								$league_settle->recalculate_all_scores();
							}
							else
							{
								$league_settle = new league_settle();
								$league_settle->load_data($_POST['league']['id']);
								$league_settle->recalculate_all_scores();
							}
						}
						break;
					}
					case 'restore_all_player_scores':
					{
						$league = new league();
						$league->load_data($_POST['league']['id']);
						if($league->data['type'] == 'settle')
						{
							if($league->is_custom_scoring())
							{
								$league_settle = new league_settle_custom();
								$league_settle->load_data($_POST['league']['id']);
								$league_settle->restore_all_player_scores();
							}
						}
						break;
					}
				}
				switch(@$_REQUEST['method']) {
					case 'list':
					{
						$league = new league();
						$league->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;	
					}
					case 'add':
					{
						$league = new league();
						$league->show_add();
						break;	
					}
					case 'edit':
					{
						$league = new league();
						$league->show_edit($_REQUEST['league']['id']);
						break;	
					}
				}
			break;
			}
			case 'log':
			{
				switch(@$_REQUEST['method']) {
					case 'list':
					{
						$log = new log();
						$log->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;	
					}
					case 'statistics':
					{
						require_once("lib/debug_counter.class.php");
						$debug_counter = new debug_counter();
						$debug_counter->show_statistics($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;	
					}
					case 'reset_statistics2':
					{
						require_once("lib/debug_counter.class.php");
						$debug_counter = new debug_counter();
						$debug_counter->reset($_REQUEST['revision']);
						$debug_counter->show_statistics($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;		
					}
				}
			break;
			}
			case 'user':
			{
				switch(@$_POST['method']) {
					case 'reset_password':
					{
						$user->reset_password($_POST['user']['id']);
						$user->show_list(NULL, NULL, NULL, true);
						break;	
					}
					case 'delete':
					{
						$user->delete($_POST['user']['id']);
						$user->show_list(NULL, NULL, NULL, true);
						break;	
					}
					case 'rename':
					{
						$user->rename($_POST['user']['id'],$_POST['user']['name']);
						$user->show_list(NULL, NULL, NULL, true);
						break;	
					}
				}
				switch(@$_REQUEST['method']) {
					case 'logout':
					{
						$user->logout();
					break;
					}
					case 'details':
					{
						$user->show_details($_REQUEST['user']['id']);
						break;
					}
					case 'list':
					{
						$user->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort'], true);
						break;
					}
				}
			break;
			}
			case 'clan':
			{
				$clan = new clan();
				switch(@$_POST['method']) {
					case 'delete':
					{
						$clan->delete($_POST['clan']['id']);
						$clan->show_list(NULL, NULL, NULL);
						break;	
					}
				}
				switch(@$_REQUEST['method']) {
					case 'details':
					{
						$clan->show_details($_REQUEST['clan']['id']);
						break;
					}
					case 'list':
					{
						$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;
					}
				}
			break;
			}
			case 'game':
			{
				require_once('lib/game.class.php');
				$game = new game();
				switch(@$_REQUEST['method']) {
					case 'add':
					{
						$game->show_add();
						break;	
					}	
				}
				switch(@$_POST['method']) {
					case 'add2':
					{
						$game->add($_POST['game'], $_POST['leagues'], $_POST['players']);
						$log = new log();
						$log->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;
					}
					case 'revoke':
					{
						$game->load_data($_POST['game']['id']);
						if($game->data['is_revoked'] == 0)
						{
							$game->revoke();
						}
						$log = new log();
						$log->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;	
					}
				}
			break;
			}
			case 'cuid_ban':
			{
				require_once('lib/cuid_ban.class.php');
				$cuid_ban = new cuid_ban();
				switch(@$_POST['method']) {			
					case 'add2':
					{
						$cuid_ban->add($_POST['cuid_ban']);
						$cuid_ban->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}				
					case 'edit2':
					{
						$cuid_ban->edit($_POST['cuid_ban']);
						$cuid_ban->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}					
					case 'delete2':
					{
						$cuid_ban->delete($_POST['cuid_ban']['cuid']);
						$cuid_ban->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}					
				}
				switch(@$_REQUEST['method']) {
					case 'add':
					{
						$cuid_ban->show_add();
						break;	
					}							
					case 'edit':
					{
						$cuid_ban->show_edit($_REQUEST['cuid_ban']['cuid']);
						break;	
					}						
					case 'list':
					{
						$cuid_ban->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;
					}
				}
			}
			case 'resource':
			{
				require_once('lib/resource.class.php');
				$resource = new resource();
				switch(@$_POST['method']) {			
					case 'add2':
					{
						$resource->add($_POST['resource']);
						$resource->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}				
					case 'edit2':
					{
						$resource->edit($_POST['resource'], $_REQUEST['old_hash']);
						$resource->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}					
					case 'delete2':
					{
						$resource->delete($_POST['resource']['hash']);
						$resource->show_list(@$_REQUEST['filter'], @$_REQUEST['page'], @$_REQUEST['sort']);
						break;	
					}					
				}
				switch(@$_REQUEST['method']) {
					case 'add':
					{
						$resource->show_add();
						break;	
					}							
					case 'edit':
					{
						$resource->show_edit($_REQUEST['resource']['hash']);
						break;	
					}						
					case 'list':
					{
						$resource->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
						break;
					}
				}
			}
		}
	}
	else
	{
		//no permission:
		$_REQUEST['part'] = '';
		$_REQUEST['method'] = '';
		$message_box->add_error($language->s('error_access_denied'));
	}
}
else
{
	if($_REQUEST['part'] == 'login' && $_REQUEST['method'] == 'login')
	{
		$user->login($_REQUEST['login_name'],$_REQUEST['login_password']);
		if($user->is_logged_in())
		{
			//TODO
		}
	}
}

$smarty->assign("user_logged_in",$user->is_logged_in() && $user->is_admin());
$smarty->assign("part", $_REQUEST['part']);
$smarty->assign("method", $_REQUEST['method']);

//$smarty->assign("s",$language->get_strings());
$smarty->assign("languages",$language->get_languages());
$smarty->assign("l",$language);

//messages:
$smarty->assign("message_box", $message_box);

$smarty->display('main.tpl');

if($debug)
{
	show_vars();
	$database->display_debug_sql();
}


?>
