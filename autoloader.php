<?php
namespace KVSun\AutoLoader;
use \shgysk8zer0\PHPCrypt\{KeyPair};

use const \KVSun\{
	ERROR_REPORTING,
	INCLUDE_PATH,
	ERROR_HANDLER,
	EXCEPTION_HANDLER,
	REQUIRED,
	INCLUDED,
	PUBLIC_KEY,
	PRIVATE_KEY,
	PASSWD
};

use function \KVSun\{defined};

if (array_key_exists('MIN_PHP_VERSION', $_SERVER)) {
	if (version_compare(\PHP_VERSION, $_SERVER['MIN_PHP_VERSION'], '<')) {
		http_response_code(500);
		exit("PHP {$_SERVER['MIN_PHP_VERSION']} or greater is required.");
	}
}
require_once __DIR__ . DIRECTORY_SEPARATOR . 'consts.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

ob_start();
error_reporting(defined('ERROR_REPORTING') ? ERROR_REPORTING : 0);

spl_autoload_register('spl_autoload');

if (defined('INCLUDE_PATH')) {
	set_path(INCLUDE_PATH);
}

if (defined('ERROR_HANDLER')) {
	set_error_handler(ERROR_HANDLER);
}

if (defined('EXCEPTION_HANDLER')) {
	set_exception_handler(EXCEPTION_HANDLER);
}

if (defined('REQUIRED') and is_array(REQUIRED)) {
	array_map(
		function($file)
		{
			require_once __DIR__ . DIRECTORY_SEPARATOR .$file;
		},
		REQUIRED
	);
}

if (defined('INCLUDE') and is_array(INCLUDED)) {
	array_map(
		function($file)
		{
			include_once __DIR__ . DIRECTORY_SEPARATOR .$file;
		},
		INCLUDED
	);
}

if (!@file_exists(PUBLIC_KEY) or !@file_exists(PRIVATE_KEY)) {
	$keys = KeyPair::generateKeyPair(PASSWD);
	$keys->public->exportToFile(PUBLIC_KEY);
	$keys->private->exportToFile(PRIVATE_KEY, PASSWD);
	unset($keys);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start([
		'name' => $_SERVER['SERVER_NAME'],
		'cookie_secure' => true,
		'cookie_httponly' => true,
	]);
}

function set_path(Array $path, Bool $use_existing = true): String
{
	if (
		in_array(PHP_SAPI, ['cli', 'cli-server'])
		or $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR']
	) {
		array_unshift($path, '..');
	}
	$path = array_map('realpath', $path);
	$path = join(\PATH_SEPARATOR, $path);
	if ($use_existing === true) {
		$path .= \PATH_SEPARATOR . get_include_path();
	}
	return set_include_path($path);
}
