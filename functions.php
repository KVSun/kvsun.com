<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\DOM as DOM;

function exception_error_handler($severity, $message, $file, $line)
{
	$console = \shgysk8zer0\Core\Console::getInstance();
	$e = new \ErrorException($message, 0, $severity, $file, $line);
	$console->error(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'code'    => $e->getCode(),
		'trace'   => $e->getTrace(),
	]]);
}

function use_icon($icon, DOM\HTMLElement $parent, Array $attrs = array())
{
	$attrs = array_merge([
		'xmlns' => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
		'version' => 1.1,
		'height' => 64,
		'width' => 64,
	], $attrs);
	$svg = $parent->append('svg', null, $attrs);
	$use = $svg->append('use', null, ['xlink:href' => DOMAIN . SPRITES . "#{$icon}"]);
	return $svg;
}

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
			Core\PDO::load(DB_CREDS),
			new Page(Core\URL::getInstance()),
		);
	}
	$ret = require_once(COMPONENTS . $file . $ext);

	if (is_callable($ret)) {
		return call_user_func_array($ret, $args);
	} elseif (is_string($ret)) {
		return $ret;
	} else {
		trigger_error("$file did not return a function or string.");
	}
}

function append_to_dom($fname, DOM\HTMLElement $el)
{
	$ext = pathinfo($fname, PATHINFO_EXTENSION);
	if (empty($ext)) {
		$fname .= '.html';
	}
	$html = file_get_contents(COMPONENTS . $fname);
	return $el->importHTML($html);
}

function get_path()
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}
