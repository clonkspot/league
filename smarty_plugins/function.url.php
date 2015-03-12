<?php

function smarty_function_url($params, Smarty_Internal_Template $template) {
	global $base_path;
	if ($base_path === null) {
		return "?part=$params[part]&method=$params[method]&";
	} else {
		return "$base_path/$params[part]/$params[method]?";
	}
}
