<?php
namespace KVSun;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'consts.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

set_path(INCLUDE_PATH);
spl_autoload_register('spl_autoload');

session_name($_SERVER['SERVER_NAME']);
session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], array_key_exists('HTTPS', $_SERVER), true);
session_start();

function set_path(Array $path, $use_existing = true)
{
	$path = array_map('realpath', $path);
	$path = join(\PATH_SEPARATOR, $path);
	if ($use_existing === true) {
		$path .= \PATH_SEPARATOR . get_include_path();
	}
	return set_include_path($path);
}
