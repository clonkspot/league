<?php

require_once('redis_config.php');

header("Content-Type: text/event-stream\n\n");
// Don't stop the script after 30s.
set_time_limit(0);

// We need two clients as we want to query Redis during pubsub.
$psclient = create_redis_client();
$redis = create_redis_client();

// First, publish all currently active games.
$active_game_ids = $redis->smembers('league:active_games');
$active_games = $redis->pipeline(function($pipe) use ($active_game_ids) {
    foreach ($active_game_ids as $game_id) {
	$pipe->get("league:game:$game_id");
    }
});
echo "event: init\n";
echo 'data: [';
echo implode(',', $active_games);
echo "]\n\n";

ob_flush();
flush();
unset($active_games);
unset($active_game_ids);

$pubsub = $psclient->pubSubLoop();
$pubsub->psubscribe('league:game:*');

foreach ($pubsub as $message) {
    switch ($message->kind) {
        case 'subscribe':
			// No output.
            break;
        case 'pmessage':
			// Skip league:game:
			$type = substr($message->channel, 12);
			$game_id = $message->payload;
			$game = $redis->get("league:game:$game_id");

			echo "event: $type\n";
			echo "data: $game\n\n";

			ob_flush();
			flush();
            break;
    }
}

// We shouldn't get here, but just in case, do proper cleanup.
unset($pubsub);
