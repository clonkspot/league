<?php

class flood_protection
{
	// Tries to get the value for $key via redis or apc.
	private function get($key) {
		global $redis;
		if (isset($redis)) {
			return $redis->get($key);
		} else {
			return apc_fetch($floodKey);
		}
	}

	// Sets the $value for $key with the given $expiry in seconds.
	private function set($key, $value, $expiry) {
		global $redis;
		if (isset($redis)) {
			$redis->setex($key, $expiry, $value);
		} else {
			return apc_fetch($floodKey);
			apc_store($key, $value, $expiry);
		}
	}

	public function getRemoteIPAddress() {
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
		{
			return $_SERVER['HTTP_CLIENT_IP'];
		}
		else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	//string is just for display
	function check_exit($floodKey, $floodMax, $floodSeconds, $string="")
	{
		$floodNow = time();
		$floodKey = "league:flood:".$floodKey.":".getRemoteIPAddress();
		$floodVal = $this->get($floodKey);
	
		if (!$floodVal) { 
			$floodStart = $floodNow; 
			$floodNum = 1; 
		}
		else {
			list($floodStart, $floodNum) = explode(":", $floodVal);
			$floodNum++;
		}
	
		if ($floodNum > $floodMax && $floodVal != FALSE) 
		{
			http_response_code(429); // Too Many Requests
			exit("Flood protection, max. of $floodMax $string requests per $floodSeconds second(s) reached");
			return false; //not used, because of exit
		}
		else 
		//use max to prevent an entry getting stored with value 0 (=forever)
			$this->set($floodKey, "$floodStart:$floodNum", max(1,$floodSeconds - ($floodNow - $floodStart)));
				
		return true;
	}
	
}



?>
