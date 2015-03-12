<?php

function smarty_function_url($params, Smarty_Internal_Template $template) {
	global $base_path;
	if ($base_path === null) {
		$url = "?part=$params[part]&method=$params[method]&";
	} else {
		$url = "$base_path$params[part]/$params[method]?";
	}
	if (isset($params["q"])) {
		$url .= $params["q"];
	} else {
		$url = substr($url, 0, -1);
	}
	return $url;
}
