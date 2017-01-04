<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\DOM as DOM;

/**
 * [exception_error_handler description]
 * @param  Int    $severity [description]
 * @param  String $message  [description]
 * @param  String $file     [description]
 * @param  Int    $line     [description]
 * @return Bool             [description]
 */
function exception_error_handler(
	Int $severity,
	String $message,
	String $file,
	Int $line
): Bool
{
	$e = new \ErrorException($message, 0, $severity, $file, $line);
	Core\Console::getInstance()->error(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'code'    => $e->getCode(),
		'trace'   => $e->getTrace(),
	]]);
	return true;
}

/**
 * Gets login user from cookie or session
 * @param void
 * @return shgysk8zer0\Login\User [description]
 */
function restore_login() : \shgysk8zer0\Login\User
{
	static $user = null;
	if (is_null($user)) {
		$user = \shgysk8zer0\Login\User::restore();
	}

	return $user;
}

function setcookie(
	String $name,
	String $value,
	Bool $httpOnly = true,
	String $path = '/'
) : Bool
{
	return \setcookie(
		$name,
		$value,
		strtotime('+1 month'),
		$path,
		$_SERVER['HTTP_HOST'],
		array_key_exists('HTTPS', $_SERVER),
		$httpOnly
	);
}

/**
 * [use_icon description]
 * @param  String         $icon   [description]
 * @param  DOMHTMLElement $parent [description]
 * @param  array          $attrs  [description]
 * @return [type]                 [description]
 */
function use_icon(
	String $icon,
	DOM\HTMLElement $parent,
	Array $attrs = array()
): DOM\HTMLElement
{
	$attrs = array_merge([
		'xmlns'       => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
		'version'     => 1.1,
		'height'      => 64,
		'width'       => 64,
	], $attrs);
	$svg = $parent->append('svg', null, $attrs);
	$svg->append('use', null, [
		'xlink:href' => DOMAIN . SPRITES . "#{$icon}"
	]);

	return $svg;
}

/**
 * [load description]
 * @param  Array $files  file1, file2, ...
 * @return Array         [description]
 */
function load(...$files) : Array
{
	return array_map(__NAMESPACE__ . '\load_file', $files);
}

/**
 * [load_file description]
 * @param  String $file [description]
 * @param  String $ext  [description]
 * @return mixed        [description]
 */
function load_file(String $file, String $ext = EXT)
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

/**
 * [append_to_dom description]
 * @param  String          $fname   [description]
 * @param  DOM\HTML\Element  $el    [description]
 * @return DOM\HTML\Element         [description]
 */
function append_to_dom(String $fname, DOM\HTMLElement $el): DOM\HTMLElement
{
	$ext = pathinfo($fname, PATHINFO_EXTENSION);
	if (empty($ext)) {
		$fname .= '.html';
	}
	$html = file_get_contents(COMPONENTS . $fname);
	return $el->importHTML($html);
}

/**
 * [get_path description]
 * @return Array [description]
 */
function get_path(): Array
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}
