<?php

require_once('lib/database.class.php');
$profiling_start_time = microtime_float();

require_once('config.php');

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
if (!$debug_skip_flood_protection && !$user->is_logged_in()) {
	$flood_protection = new flood_protection();
	$flood_protection->check_exit("website",40,3600,"website"); //max. 40 request/hour
}


if($user->is_logged_in() && $user->is_admin())
{
	if($user->check_admin_permission("debug","show_all"))
		$debug_user = TRUE;
}

$message_box = new message_box();

// Returns the given $_REQUEST parameter or a default value.
function param($key, $default = NULL)
{
	return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}

$method = param('method');
//default:
if(isset($_REQUEST['part']))
{
	$part = $_REQUEST['part'];
}
else
{
	$part = "game";
	$method = "list";
}



switch($part) {
	
	case 'league':
	{
		require_once('lib/league.class.php');
		switch($method) {
			case 'list':
			{
				$league = new league();
				$league->show_list(param('filter'), param('page'), param('sort'));
				break;	
			}
			case 'ranking':
			{
				$league = new league();
				$league->show_ranking(param('league')['id'],param('filter'),param('page'), param('sort'), param('highlight'));
				break;
			}
			case 'clan_ranking':
			{
				$league = new league();
				$league->show_clan_ranking(param('league')['id'],param('filter'),param('page'), param('sort'), param('highlight'));
				break;
			}
		}
	break;
	}
	case 'game':
	{
		require_once('lib/game.class.php');
		switch($method) {
			case 'list':
			{
				$game = new game();
				$game->show_list(param('filter'), param('page'), param('sort'));
				break;	
			}
			case 'details':
			{
				$game = new game();
				$game->show_details(param('game')['id']);
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
		switch($method) {
			case 'list':
			{
				$game = new scenario();
				$game->show_list(param('filter'), param('page'), param('sort'));
				break;	
			}
			case 'toggle_league':
			{
				$scenario = new scenario();
				if($scenario->load_data(param('scenario')))
				{
					$scenario->operator_toggle_league($user, param('league'));
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
			switch($method) {
				case 'add':
				{
					$clan->show_add();
					break;
				}
				case 'add2':
				{
					if(false == $user->get_clan_id())
						$clan->create($_POST['clan'], $user);
					$clan->show_list(param('filter'), param('page'), param('sort'));
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
					$clan->show_list(param('filter'), param('page'), param('sort'));
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
					$clan->show_list(param('filter'), param('page'), param('sort'));
					break;	
				}
				case 'delete2':
				{
					if($clan->load_data($_POST['clan']['id']))
					{
						if($clan->data['founder_user_id'] == $user->data['id'])
							$clan->delete($_POST['clan']['id']);
					}
					$clan->show_list(param('filter'), param('page'), param('sort'));
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
					$clan->show_list(param('filter'), param('page'), param('sort'));
					break;	
				}
				case 'leave':
				{
					$user->leave_clan();
					$clan->show_list(param('filter'), param('page'), param('sort'));
					break;	
				}
			}
		}
		switch($method) {
			case 'list':
			{
				$clan->show_list(param('filter'), param('page'), param('sort'));
				break;
			}
			case 'details':
			{
				$clan->show_details(param('clan')['id']);
				break;
			}
		}
	break;
	}
	case 'user':
	{
		if($user->is_logged_in())
		{
			switch($method) {
				case 'edit':
				{
					$user->show_edit();
					break;
				}
				case 'logout':
				{
					$user->logout();
					require_once('lib/league.class.php');
					$league = new league();
					$league->show_list();
					$part='league';
					$method='list';
					break;
				}
				case 'set_score':
				{
					$user->set_score(param('user')['id'], param('league')['id'], param('score'));
					$user->show_details(param('user')['id']);
					break;
				}
				case 'suicide':
				{
					$message_box->add_error($language->s('error_abuse'));				
					//$user->set_score(param('user')['id'], param('league')['id'], 0);
					$user->show_edit();
					break;
				}
			}
			switch($method) {
				case 'edit2':
				{
					$user->edit($_POST['user']);
					$user->show_edit();
					break;	
				}
				case 'delete2':
				{
					//
					//$user->delete(param('scenario')['id']);
					break;	
				}
			}
		}
		switch($method) {
			case 'details':
			{
				$user->show_details(param('user')['id']);
				break;
			}
			case 'list':
			{
				$user->show_list(param('filter'), param('page'), param('sort'));
				break;
			}
		}
	break;
	}
	case 'login':
	{
		switch($method) {
			case 'login':
			{
				$user->login(param('login_name'),param('login_password'),param('login_new_name'));
				if($user->is_logged_in())
				{
					require_once('lib/game.class.php');
					$game = new game();
					$game->show_list(param('filter'), param('page'), param('sort'));
					$part='game';
					$method='list';
				}
				elseif(isset($_REQUEST['login_name']) && is_numeric($_REQUEST['login_name'])
					&& false == $user->cuid_user_exists($_REQUEST['login_name'])
					&& $user->get_error() != 'error_webcode_auth_na'
					&& $user->get_error() != 'error_webcode_auth_failed')
				{
					//it's a CUID - ask for a nickname to create a new account
					$part='login';
					$method='new_user';
				}
				else
				{
					$part='login';
					$method='error';
				}
				break;
			}
		}
	break;
	}
}

$smarty->assign("user_logged_in",$user->is_logged_in());
$smarty->assign("user_logged_in_via_cookie",$user->is_logged_in_via_cookie());
$smarty->assign("user_is_admin",$user->is_admin());
$smarty->assign("u",$user);

$smarty->assign("part", $part);
$smarty->assign("method", $method);

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
$debug_counter->increment("site_".$part."_".$method,$duration);
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
