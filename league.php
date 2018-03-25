<?php
require_once('lib/database.class.php');
$profiling_start_time = microtime_float();

require_once('lib/Debug.class.php');

require_once('lib/league_backend.class.php');



require_once('config.php');
$debug = FALSE;

require_once('lib/log.class.php');

require_once('lib/language.class.php');
require_once('lib/message_box.class.php');

include_once('lib/debug_counter.class.php');

//flood protection: limit request per hour
require_once('lib/flood_protection.class.php');
if (!$debug_skip_flood_protection) {
	$flood_protection = new flood_protection();
	//alle 8s ein Query, alle 30s ein Record-Stream, alle 2min ein Update, jeweils maximal
	//= 450 + 120 + 30 = 600/Stunde maximal durch normalen Betrieb pro Client	
	$flood_protection->check_exit("backend",700,3600,"backend"); //max. 40 request/hour
}



$message_box = new message_box();
$language = new language();
$language->load_stringtable();


$league_backend = new league_backend();

// Input socket
$inputSocket = fopen('php://input','rb');
$post_data = stream_get_contents($inputSocket);
fclose($inputSocket);

//to debug with test_client.php:
if(!$post_data && $_REQUEST['r'])
	$post_data = $_REQUEST['r'];

global $debug_req;
if(isset($debug_req))
	$post_data = $debug_req;
	
	//because it is double-escaped for no obvious reason...?! (TODO)
//	$post_data = stripslashes(stripslashes($post_data));
	
//to get a single reference by link:
if($_REQUEST['action'] == 'query' && $_REQUEST['game_id'])
{
	$league_backend->send_game_reference($_REQUEST['game_id']);
	$duration = microtime_float() - $profiling_start_time;
	$debug_counter = new debug_counter();
	$debug_counter->increment('send_game_reference',$duration);	
}
//action=query & product_id is dispatched in recieve_reference
elseif($_REQUEST['action'] == 'version')
{
	$league_backend->send_version();
	$duration = microtime_float() - $profiling_start_time;
	$debug_counter = new debug_counter();
	$debug_counter->increment('version',$duration);		
}
elseif($_REQUEST['action'] == 'news_statistics')
{
	require_once('lib/news_statistics.class.php');
	$news_statistics = new news_statistics();
	echo $news_statistics->get_news_statistics_text();
	$duration = microtime_float() - $profiling_start_time;
	$debug_counter = new debug_counter();
	$debug_counter->increment('news_statistics',$duration);		
}
elseif($_REQUEST['action'] == 'stream_record')
{
	$league_backend->recieve_record_stream($_REQUEST['game_id'], $_REQUEST['pos'],  $_REQUEST['end'], $post_data);
	$duration = microtime_float() - $profiling_start_time;
	$debug_counter = new debug_counter();
	$debug_counter->increment('stream_record',$duration);	
}
else
{
	global $debug_skip_reference_hashcheck;
	if(isset($debug_skip_backend_checksum) && $debug_skip_backend_checksum == TRUE) //deactivate checksum-check for local tests
		$league_backend->recieve_reference($post_data);
	else
	{		
	    # Require a successful checksum test for every complex query
	    if($post_data == "" or TRUE)
		    $league_backend->recieve_reference($post_data);
	    else
	    	$league_backend->checksum_error();
	}

}

// Send buffered output
$response = $league_backend->get_response();
if(!isset($debug_req)) {
	header("HTTP/1.0 200 OK");
	header("Content-Type: text/plain");
	header("Content-Length: " . strlen($response)); 
	echo $response;
}
else
{
	global $debug_response;
	$debug_response = $response;
}

?>
