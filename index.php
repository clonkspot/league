<?php

require_once('lib/database.class.php');
$profiling_start_time = microtime_float();

require_once('config.php');

if(FALSE == $debug_skip_session_path)
	session_save_path("/var/session/league2");
session_start();

require_once('lib/language.class.php');
require_once('lib/log.class.php');

require_once('lib/user.class.php');

require_once('lib/message_box.class.php');

include_once('lib/debug_counter.class.php');

require_once('lib/Debug.class.php');

$language = new language();
$language->load_stringtable();

$user = new user();
$login_user = $user;

$user->session_login();


//flood protection: limit request per hour
require_once('lib/flood_protection.class.php');
if (!$debug_skip_flood_protection && !$user->is_admin() && count($user->get_operator_leagues()) == 0) {
	$flood_protection = new flood_protection();
	$flood_protection->check_exit("website",40,3600,"website"); //max. 40 request/hour
}


if($user->is_logged_in() && $user->is_admin())
{
	if($user->check_admin_permission("debug","show_all"))
		$debug_user = TRUE;
}

$message_box = new message_box();

//default:
if($_REQUEST['part'] == "")
{
	$_REQUEST['part'] = "game";
	$_REQUEST['method'] = "list";
}



switch(@$_REQUEST['part']) {
	
	case 'league':
	{
		require_once('lib/league.class.php');
		switch(@$_REQUEST['method']) {
			case 'list':
			{
				$league = new league();
				$league->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
				break;	
			}
			case 'ranking':
			{
				$league = new league();
				$league->show_ranking($_REQUEST['league']['id'],$_REQUEST['filter'],$_REQUEST['page'], $_REQUEST['sort'], $_REQUEST['highlight']);
				break;
			}
			case 'clan_ranking':
			{
				$league = new league();
				$league->show_clan_ranking($_REQUEST['league']['id'],$_REQUEST['filter'],$_REQUEST['page'], $_REQUEST['sort'], $_REQUEST['highlight']);
				break;
			}
		}
	break;
	}
	case 'game':
	{
		require_once('lib/game.class.php');
		switch(@$_REQUEST['method']) {
			case 'list':
			{
				$game = new game();
				$game->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
				break;	
			}
			case 'details':
			{
				$game = new game();
				$game->show_details($_REQUEST['game']['id']);
				break;
			}
			case 'revoke':
			{
				$game = new game();
				$game->load_data($_POST['game']['id']);
				$game->revoke();
				$game->show_details($_POST['game']['id']);
				break;	
			}
	
		}
	break;
	}
	case 'scenario':
	{
		require_once('lib/scenario.class.php');
		switch(@$_REQUEST['method']) {
			case 'list':
			{
				$game = new scenario();
				$game->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
				break;	
			}
			case 'toggle_league':
			{
				$scenario = new scenario();
				if($scenario->load_data($_REQUEST['scenario']))
				{
					$scenario->operator_toggle_league($user, $_REQUEST['league']);
				}
				$game = new scenario();
				$game->show_list();
				break;	
			}
		}
	break;
	}
	case 'clan':
	{
		require_once('lib/clan.class.php');
		$clan = new clan();
		if($user->is_logged_in())
		{
			switch(@$_POST['method']) {
				case 'add':
				{
					$clan->show_add();
					break;
				}
				case 'add2':
				{
					if(false == $user->get_clan_id())
						$clan->create($_POST['clan'], $user);
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;
				}
				case 'edit':
				{
					$clan->load_data($user->get_clan_id());
					$clan->show_edit();
					break;
				}
				case 'edit2':
				{
					if($clan->load_data($user->get_clan_id()))
					{
						if($clan->data['founder_user_id'] == $user->data['id'])
						{
							$clan->edit($_POST['clan']);
							$clan->show_edit();
						}
					}
					break;	
				}
				case 'kick':
				{
					if($clan->load_data($user->get_clan_id()))
					{
						if($clan->data['founder_user_id'] == $user->data['id'])
						{
							$clan->kick($_POST['user']['id']);
						}
					}
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;	
				}
				case 'transfer_founder':
				{
					if($clan->load_data($user->get_clan_id()))
					{
						if($clan->data['founder_user_id'] == $user->data['id'])
						{
							$clan->transfer_founder($_POST['user']['id']);
						}
					}
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;	
				}
				case 'delete2':
				{
					if($clan->load_data($_POST['clan']['id']))
					{
						if($clan->data['founder_user_id'] == $user->data['id'])
							$clan->delete($_POST['clan']['id']);
					}
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;	
				}
				case 'join':
				{
					if($clan->load_data($_POST['clan']['id']))
					{
						if($clan->check_password($_POST['clan']['password']))
							$user->join_clan($_POST['clan']['id']);
					}
					else
					{
						$message_box->add_error($language->s('error_clan_none_selected'));
					}
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;	
				}
				case 'leave':
				{
					$user->leave_clan();
					$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					break;	
				}
			}
		}
		switch(@$_REQUEST['method']) {
			case 'list':
			{
				$clan->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
				break;
			}
			case 'details':
			{
				$clan->show_details($_REQUEST['clan']['id']);
				break;
			}
		}
	break;
	}
	case 'user':
	{
		if($user->is_logged_in())
		{
			switch(@$_REQUEST['method']) {
				case 'logout':
				{
					$user->logout();
					require_once('lib/league.class.php');
					$league = new league();
					$league->show_list();
					$_REQUEST['part']='league';
					$_REQUEST['method']='list';
					break;
				}
				case 'set_score':
				{
					$user->set_score($_REQUEST['user']['id'], $_REQUEST['league']['id'], $_REQUEST['score']);
					$user->show_details($_REQUEST['user']['id']);
					break;
				}
				case 'suicide':
				{
					$message_box->add_error($language->s('error_abuse'));				
					//$user->set_score($_REQUEST['user']['id'], $_REQUEST['league']['id'], 0);
					break;
				}
			}
		}
		switch(@$_REQUEST['method']) {
			case 'details':
			{
				$user->show_details($_REQUEST['user']['id']);
				break;
			}
			case 'list':
			{
				$user->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
				break;
			}
		}
	break;
	}
	case 'login':
	{
		switch(@$_REQUEST['method']) {
			case 'login':
			{
				$user->login($_REQUEST['login_name'],$_REQUEST['login_password'],@$_REQUEST['login_new_name']);
				if($user->is_logged_in())
				{
					require_once('lib/game.class.php');
					$game = new game();
					$game->show_list($_REQUEST['filter'], $_REQUEST['page'], $_REQUEST['sort']);
					$_REQUEST['part']='game';
					$_REQUEST['method']='list';
				}
				elseif($_REQUEST['login_name'] && is_numeric($_REQUEST['login_name'])
					&& false == $user->cuid_user_exists($_REQUEST['login_name'])
					&& $user->get_error() != 'error_webcode_auth_na'
					&& $user->get_error() != 'error_webcode_auth_failed')
				{
					//it's a CUID - ask for a nickname to create a new account
					$_REQUEST['part']='login';
					$_REQUEST['method']='new_user';
				}
				else
				{
					$_REQUEST['part']='login';
					$_REQUEST['method']='error';
				}
				break;
			}
		}
	break;
	}
}

$smarty->assign("user_logged_in",$user->is_logged_in());
$smarty->assign("user_is_admin",$user->is_admin());
$smarty->assign("u",$user);

$smarty->assign("part", $_REQUEST['part']);
$smarty->assign("method", $_REQUEST['method']);

//$smarty->assign("s",$language->get_strings());
$smarty->assign("languages",$language->get_languages());
$smarty->assign("l",$language);


//messages:
$smarty->assign("message_box", $message_box);

$smarty->display('main.tpl');

//debug-stuff:
/*
$game = new game();
$game->load_data(200);
$a = array();
$game->insert_score_data_into_end_response($a);
print_a($a);
print_a($game);*/

/*if($debug)
{
	show_vars();
	$database->display_debug_sql();
}*/


$duration = microtime_float() - $profiling_start_time;
$debug_counter = new debug_counter();
$debug_counter->increment("site_".$_REQUEST['part']."_".$_REQUEST['method'],$duration);
$debug_counter->increment("site",$duration);


if($user->is_logged_in() && $user->is_admin())
{
	if($user->check_admin_permission("debug","show_all"))
	{
		show_vars();
		$database->display_debug_sql();	
	}
}

?>
