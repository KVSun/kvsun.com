<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\DOM as DOM;

function load(...$files)
{
	return array_map(__NAMESPACE__ . '\load_file', $files);
}

function load_file($file, $ext = EXT)
{
	static $args = null;

	if (is_null($args)) {
		$args = array(
			DOM\HTML::getInstance(),
			Core\PDO::load('connect'),
		);
	}
	$ret = require_once(COMPONENTS . $file . $ext);

	if (is_callable($ret)) {
		return call_user_func_array($ret, $args);
	} elseif (is_string($ret)) {
		return $ret;
	} else {
		trigger_error("$file did not return a function.");
	}
}

function get_path()
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}
