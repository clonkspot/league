<?php
/** used by build-script, 
 * stateless, 
 * authentification done in every call by GET-Parameters
 * use GET/REQUEST (no POST for securtiy needed because it's just called by other scripts)
 */

 //TODO: remote-address-check:
// if($_SERVER['REMOTE_ADDR'] != "127.0.0.1")
// {
//	echo "error_localhost_only";
////	exit;
// }

require_once('lib/Debug.class.php');
require_once('lib/database.class.php');
require_once('config.php');

//error_reporting(E_ALL);
//ini_set('display_errors', true);

require_once('lib/log.class.php');
require_once('lib/language.class.php');
require_once('lib/user.class.php');
$language = new language();
$language->load_stringtable();

require_once('lib/message_box.class.php');
$message_box = new message_box();

$user = new user();

$user->login($_REQUEST['login_name'],$_REQUEST['login_password']);

if($user->is_logged_in() && $user->is_admin())
{
	if($user->check_admin_permission(@$_REQUEST['part'],@$_REQUEST['method']))
	{
		switch(@$_REQUEST['part']) {
			case 'scenario':
			{
				require_once('lib/scenario.class.php');
				switch(@$_REQUEST['method']) {
					case 'add2':
					{
						$scen = new scenario();
						//echo new id
						echo $scen->add($_REQUEST['scenario'],$_REQUEST['versions'],@$_REQUEST['leagues']);
						break;	
					}	
					case 'add_version2':
					{
						$scen = new scenario();
						if($_REQUEST['scenario_id'])
							echo $scen->load_data($_REQUEST['scenario_id']);
						else
							echo $scen->load_data_by_league_filename($_REQUEST['league_id'], $_REQUEST['filename']);
						$scen->add_version($_REQUEST['version']);
						break;	
					}
					case 'delete_all_versions2':
					{
						$scen = new scenario();
						if($_REQUEST['scenario_id'])
							echo $scen->load_data($_REQUEST['scenario_id']);
						else
							echo $scen->load_data_by_league_filename($_REQUEST['league_id'], $_REQUEST['filename']);
						$scen->delete_all_versions();
						break;	
					}					
					/*case 'edit2':
					{
						$scen = new scenario();
						$scen->edit($_REQUEST['scenario'],$_REQUEST['versions'],$_REQUEST['scenarios_merge'],@$_POST['leagues']);
						break;	
					}
					case 'delete2':
					{
						$scen = new scenario();
						$scen->delete($_REQUEST['scenario']['id']);
						break;	
					}*/					
				}
			break;
			}
			case 'resource':
			{
				require_once('lib/resource.class.php');
				$resource = new resource();
				switch(@$_REQUEST['method']) {			
					case 'add2':
					{
						$resource->add($_REQUEST['resource']);
						return;
					}				
					/*case 'edit2':
					{
						$resource->edit($_REQUEST['resource'], $_REQUEST['old_hash']);
						break;	
					}					
					case 'delete2':
					{
						$resource->delete($_REQUEST['resource']['hash']);
						break;	
					}*/					
				}
			}
		}
	}
	else
	{
		//no permission:
		echo "error_access_denied";
	}
}
else
	echo "error_access_denied";

?>