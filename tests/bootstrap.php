<?php

require 'vendor/autoload.php';
error_reporting(E_ALL);

define('TEST_PATH', realpath(dirname( __FILE__ )) . '/');


$db = getenv('DB') ?: 'mysql';
$env = getenv('TRAVIS') == 'true' ? 'travis' : 'dev';
$load = TEST_PATH . "envs/{$db}_{$env}.php";

require $load;

echo "ENV: $load" . PHP_EOL;
