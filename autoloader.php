<?php
namespace KVSun\AutoLoader;

error_reporting(defined('\KVSun\ERROR_REPORTING') ? \KVSun\ERROR_REPORTING : 0);
ob_start();

if (array_key_exists('MIN_PHP_VERSION', $_SERVER)) {
	if (version_compare(\PHP_VERSION, $_SERVER['MIN_PHP_VERSION'], '<')) {
		http_response_code(500);
		exit("PHP {$_SERVER['MIN_PHP_VERSION']} or greater is required.");
	}
}
require_once __DIR__ . DIRECTORY_SEPARATOR . 'consts.php';

spl_autoload_register('spl_autoload');

if (defined('\KVSun\INCLUDE_PATH')) {
	set_path(\KVSun\INCLUDE_PATH);
}

if (defined('\KVSun\ERROR_HANDLER')) {
	set_error_handler(\KVSun\ERROR_HANDLER);
}
if (defined('KVSun\EXCEPTION_HANDLER')) {
	set_exception_handler(\KVSun\EXCEPTION_HANDLER);
}

if (defined('\KVSun\REQUIRED')) {
	array_map(
		function($file)
		{
			require_once __DIR__ . DIRECTORY_SEPARATOR .$file;
		},
		\KVSun\REQUIRED
	);
}
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start([
		'name' => $_SERVER['SERVER_NAME'],
		'cookie_secure' => true,
		'cookie_httponly' => true,
	]);
}

function set_path(Array $path, Bool $use_existing = true)
{
	$path = array_map('realpath', $path);
	$path = join(\PATH_SEPARATOR, $path);
	if ($use_existing === true) {
		$path .= \PATH_SEPARATOR . get_include_path();
	}
	return set_include_path($path);
}
