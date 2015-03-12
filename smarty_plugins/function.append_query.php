<?php

// {append_query url="foo?bar=1" q="a=2&b=3"}
// => foo?bar=1&a=2&b=3
function smarty_function_append_query($params, Smarty_Internal_Template $template) {
	$url = $params['url'];
	$url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . preg_replace('/^(\?|\&)/', '', $params['q'], 1);
	return $url;
}
