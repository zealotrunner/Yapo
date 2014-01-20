<?php

require 'vendor/autoload.php';
error_reporting(E_ALL);

define('TEST_PATH', realpath(dirname( __FILE__ )) . '/');


$db = getenv('DB') ?: 'sqlite';
$env = getenv('TRAVIS') == 'true' ? 'travis' : 'dev';
$load = TEST_PATH . "envs/{$db}_{$env}.php";

require $load;

echo "ENV: $load" . PHP_EOL;

function pluck($list, $property) {
	return array_map(function($l) use ($property) {
		return $l->$property;
	}, $list);
}