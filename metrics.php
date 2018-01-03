<?php

// Exports metrics from lg_debug_counters in the Prometheus text format.

require_once('lib/database.class.php');
require_once('config.php');

// value > 0 filters the 'dummy' entry which forces a new revision.
$counters = $database->get_array("SELECT name, UNIX_TIMESTAMP(last_update)*1000 as timestamp, value*mean_duration AS duration FROM lg_debug_counter
	WHERE revision = (SELECT MAX(revision) FROM lg_debug_counter) AND value > 0
	ORDER BY name");

header('Content-Type: text/plain; version=0.0.4');

$type_seen = array();
function print_counter($name, $labels, $duration, $timestamp)
{
	global $type_seen;
	$lstr = '';
	if (count($labels))
	{
		$lstr = '{'. implode(',', array_map(function($k, $v) { return $k.'="'. addcslashes($v, "\"\\\n") .'"'; }, array_keys($labels), $labels)) .'}';
	}
	$metric_name = "league_{$name}_duration_seconds_total";
	if (!in_array($metric_name, $type_seen))
	{
		echo "\n# TYPE $metric_name counter\n";
		$type_seen[] = $metric_name;
	}
	// Don't print timestamps - our metrics are always valid at the time of the scrape.
	//echo "$metric_name$lstr $duration $timestamp\n";
	echo "$metric_name$lstr $duration\n";
}

foreach ($counters as $counter)
{
	$labels = array();
	$name = $counter["name"];
	$name_parts = explode("_", $name, 2);
	if (count($name_parts) == 2 && in_array($name_parts[0], array("site", "action")))
	{
		$name = $name_parts[0];
		$labels[$name] = $name_parts[1];
	}
	print_counter($name, $labels, $counter["duration"], $counter["timestamp"]);
}
