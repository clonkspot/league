<?php

require 'Predis/Autoloader.php';
Predis\Autoloader::register();

function create_redis_client() {
	return new Predis\Client();
}
