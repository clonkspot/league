<?php

require_once('lib/database.class.php');
require_once('lib/game.class.php');
require_once('config.php');

header("Content-Type: text/event-stream\n\n");

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
}

function echo_id($prev_ids) {
	$id = json_encode(array('time' => time(), 'games' => $prev_ids));
	echo "retry: 2000\n";
	echo "data: null\n";
	echo "id: $id\n\n";
}

// Is this the first request?
if (!isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
	// Those are needed later to find deleted games.
	$game_ids = get_active_game_ids();
	echo_event('init', array_map('get_game_json', $game_ids));
	echo_id($game_ids);
} else {
	// Decode the header.
	$id = json_decode($_SERVER['HTTP_LAST_EVENT_ID']);
	$last_update = $id->time;
	$last_game_ids = $id->games;

	$did_output = false;
	$retries = 10;
	while (!$did_output && $retries--) {
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
			$did_output = true;
		}

		$current_games = get_active_game_ids();
		$deleted_games = array_filter($last_game_ids, function($game_id) use ($current_games) {
			return !in_array($game_id, $current_games);
		});
		foreach ($deleted_games as $game_id) {
			echo_event('delete', array('id' => intval($game_id, 10)));
			$did_output = true;
		}
		if ($did_output) {
			echo_id($current_games);
		} else {
			$last_update = time();
			$last_game_ids = $current_games;
			sleep(1);
		}
	}
}
