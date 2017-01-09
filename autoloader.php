<?php
namespace KVSun;

error_reporting(0);
ob_start();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'consts.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

set_path(INCLUDE_PATH);
spl_autoload_register('spl_autoload');

session_name($_SERVER['SERVER_NAME']);
session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], array_key_exists('HTTPS', $_SERVER), true);
session_start();

if (defined(__NAMESPACE__ . '\REQUIRED')) {
	foreach(REQUIRED as $req) {
		require_once __DIR__ . DIRECTORY_SEPARATOR . $req;
	}
	unset($req);
}
require_once __DIR__ . DIRECTORY_SEPARATOR . 'events.php';

set_exception_handler('\shgysk8zer0\Core\Listener::exception');
set_error_handler('\shgysk8zer0\Core\Listener::error');

function set_path(Array $path, $use_existing = true)
{
	$path = array_map('realpath', $path);
	$path = join(\PATH_SEPARATOR, $path);
	if ($use_existing === true) {
		$path .= \PATH_SEPARATOR . get_include_path();
	}
	return set_include_path($path);
}
