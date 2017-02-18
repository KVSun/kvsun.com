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

if (defined('\KVSun\REQUIRED') and is_array(\KVSun\REQUIRED)) {
	array_map(
		function($file)
		{
			require_once __DIR__ . DIRECTORY_SEPARATOR .$file;
		},
		\KVSun\REQUIRED
	);
}

if (defined('\KVSun\INCLUDE') and is_array(\KVSun\INCLUDED)) {
	array_map(
		function($file)
		{
			include_once __DIR__ . DIRECTORY_SEPARATOR .$file;
		},
		\KVSun\INCLUDED
	);
}

if (!@file_exists(\KVSun\PUBLIC_KEY) or !@file_exists(\KVSun\PRIVATE_KEY)) {
	$keys = \shgysk8zer0\PHPCrypt\KeyPair::generateKeyPair(\KVSun\PASSWD);
	$keys->public->exportToFile(\KVSun\PUBLIC_KEY);
	$keys->private->exportToFile(\KVSun\PRIVATE_KEY, \KVSun\PASSWD);
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
