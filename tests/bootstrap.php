<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);

define('DEBUG_MODE', false);
define('TEST_DSN', 'sqlite:' . __DIR__ .'/test.sqlite');
define('TEST_USER', '');
define('TEST_PASS', '');

// define('DEBUG_MODE', true);
// define('TEST_DSN', 'mysql:host=192.168.1.24;dbname=think_test');
// define('TEST_USER', 'new_home');
// define('TEST_PASS', '123456');