<?php

function base64_encode_multi(&$val) 
{
	if (is_array($val)) 
		array_walk($val,'base64_encode_multi');
	else 
	{
		$val = base64_encode($val);
	}
}

function base64_decode_multi(&$val) 
{
	if (is_array($val)) 
		array_walk($val,'base64_decode_multi');
	else 
	{
		$val = base64_decode($val);
	}
}

//from http://de.php.net/array
function array_deep_copy (&$array, &$copy) {
	if(!is_array($copy)) $copy = array();
	if(is_array($array))
	{
		foreach($array as $k => $v) {
			if(is_array($v)) {
				array_deep_copy($v,$copy[$k]);
			} else {
				$copy[$k] = $v;
			}
		}
	}
}

function remove_quotes($string)
{
	if($string[0] == '"' && $string[strlen($string)-1] == '"')
	{
		$string = substr($string,1,strlen($string)-2);
	}
	return $string;
}


function decode_octal($string)
{
	$string = preg_replace_callback('/(^|[^\\\])\\\([0-9]+)/m', function($m) {
		return $m[1] . chr(octdec($m[2]));
	}, $string);
	$string = str_replace('\\\\','\\',$string);
	return $string;
}

function decode_octal_array(&$a, $index)
{
	for($i=0;$i<count($a);$i++)
	{
		$a[$i][$index] = decode_octal($a[$i][$index]);

	}
}
