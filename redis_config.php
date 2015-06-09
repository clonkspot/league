<?php

require 'vendor/autoload.php';

function create_redis_client() {
	return new Predis\Client();
}
