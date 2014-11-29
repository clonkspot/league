<?php

require_once('redis_config.php');

header("Content-Type: text/event-stream\n\n");
// Don't stop the script after 30s.
set_time_limit(0);

// We need two clients as we want to query Redis during pubsub.
$psclient = create_redis_client();
$redis = create_redis_client();

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
			list($game, $game_reference) = $redis->mget(array("league:game:$game_id", "league:game_reference:$game_id"));
			if (empty($game)) $game = 'null';
			if (empty($game_reference)) $game_reference = 'null';

			echo "event: $type\n";
			echo 'data: {"id":'.$game_id.',"game":'.$game.',"reference":'.$game_reference."}\n\n";

			ob_flush();
			flush();
            break;
    }
}

// We shouldn't get here, but just in case, do proper cleanup.
unset($pubsub);
