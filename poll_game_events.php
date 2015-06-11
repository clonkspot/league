<?php

require_once('lib/database.class.php');
require_once('lib/game.class.php');
require_once('config.php');

if (isset($redis)) {
	header('Location: game_events.php');
	die();
}

header("Content-Type: text/event-stream\n\n");
// Don't stop the script after 30s.
set_time_limit(0);

function get_game_json($game_id) {
	$game = new game;
	$game->load_data($game_id);
	return $game->to_json();
}

function get_active_game_ids() {
	global $database;
	$ids = $database->get_array("SELECT id FROM lg_games WHERE status != 'ended'");
	return array_map(function($a) { return $a['id']; }, $ids);
}

function get_updated_games($since) {
	global $database;
	$ids = $database->get_array('SELECT id FROM lg_games WHERE date_last_update > ' . $database->escape($since));
	return array_map(function($a) { return $a['id']; }, $ids);
}

function echo_event($event, $data) {
	$data = json_encode($data);
	echo "event: $event\n";
	echo "data: $data\n\n";
	ob_flush();
	flush();
}

// Those are needed later to find deleted games.
$last_game_ids = get_active_game_ids();

echo_event('init', array_map('get_game_json', $last_game_ids));

while (true) {
	$last_update = time();
	sleep(1);

	$updated_games = get_updated_games($last_update);
	foreach ($updated_games as $game_id) {
		$game = get_game_json($game_id);
		if (!in_array($game_id, $last_game_ids))
			$event = 'create';
		else if ($game['status'] == 'ended')
			$event = 'end';
		else
			$event = 'update';
		echo_event($event, $game);
	}

	$current_games = get_active_game_ids();
	$deleted_games = array_filter($last_game_ids, function($game_id) use ($current_games) {
		return !in_array($game_id, $current_games);
	});
	foreach ($deleted_games as $game_id) {
		echo_event('delete', array('id' => intval($game_id, 10)));
	}
	$last_game_ids = $current_games;
}
