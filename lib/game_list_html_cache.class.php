<?php

// Creates a Redis cache when available or a dummy cache.
function create_game_list_html_cache() {
	global $redis;
	if (isset($redis)) {
		return new game_list_html_cache_redis($redis);
	} else {
		return new game_list_html_cache();
	}
}

class game_list_html_cache {
	function get($game_id) {
		return array(
			'game_list_html'   => NULL,
			'game_list_html_2' => NULL
		);
	}

	function set($game_id, $ghtml) {
	}

	function del($game_id) {
	}
}


// Caches list entries using Redis.
class game_list_html_cache_redis extends game_list_html_cache {
	private $redis;

	// TTL for the cache entries
	const EXPIRY = 3600;

	function __construct($redis) {
		$this->redis = $redis;
	}

	function get($game_id) {
		$language_id = $this->get_language_id();
		return array(
			'game_list_html'   => $this->redis->get("league:game_list_html:$game_id:$language_id"),
			'game_list_html_2' => $this->redis->get("league:game_list_html_2:$game_id:$language_id")
		);
	}

	function set($game_id, $ghtml) {
		$language_id = $this->get_language_id();
		$this->redis->setex("league:game_list_html:$game_id:$language_id", self::EXPIRY, $ghtml['game_list_html']);
		$this->redis->setex("league:game_list_html_2:$game_id:$language_id", self::EXPIRY, $ghtml['game_list_html_2']);
	}

	function del($game_id) {
		global $language;
		foreach ($language->get_languages() as $lang) {
			$language_id = $lang['id'];
			$this->redis->del("league:game_list_html:$game_id:$language_id", "league:game_list_html_2:$game_id:$language_id");
		}
	}

	private function get_language_id() {
		global $language;
		return $language->get_current_language_id();
	}
}
