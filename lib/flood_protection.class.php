<?php

class flood_protection
{

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
		$floodKey = "flood_".$floodKey."_".getRemoteIPAddress();
		$floodVal = apc_fetch($floodKey);
	
		if ($floodVal === FALSE) { 
			$floodStart = $floodNow; 
			$floodNum = 1; 
		}
		else {
			list($floodStart, $floodNum) = explode(":", $floodVal);
			$floodNum++;
		}
	
		if ($floodNum > $floodMax && $floodVal != FALSE) 
		{
			exit("Flood protection, max. of $floodMax $string requests per $floodSeconds second(s) reached");
			return false; //not used, because of exit
		}
		else 
		//use max to prevent an entry getting stored with value 0 (=forever)
			apc_store($floodKey, "$floodStart:$floodNum", max(1,$floodSeconds - ($floodNow - $floodStart)));
				
		return true;
	}
	
}



?>