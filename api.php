<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API\Abstracts\HTTPStatusCodes as Status;

ob_start();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

if (DEBUG) {
	Core\Console::getInstance()->asErrorHandler()->asExceptionHandler();
}

$header = Core\Headers::getInstance();
if ($header->accept === 'application/json') {
	$resp = Core\JSON_Response::getInstance();
	if (array_key_exists('url', $_GET)) {
		$url = new Core\URL($_GET['url']);
		$page = new \KVSun\Page($url);
		$header->content_type = 'application/json';
		Core\Console::getInstance()->log($page);
		exit($page);
	} elseif (array_key_exists('form', $_REQUEST) and is_array($_REQUEST[$_REQUEST['form']])) {
		require_once COMPONENTS . 'handlers' . DIRECTORY_SEPARATOR . 'form.php';
	} elseif (array_key_exists('datalist', $_GET)) {
		$resp->notify('Request for datalist', $_GET['datalist']);
	} elseif (array_key_exists('load_menu', $_GET)) {
		$menu = COMPONENTS . 'menus' . DIRECTORY_SEPARATOR . $_GET['load_menu'] . '.html';
		if (@file_exists($menu)) {
			$resp->append('body', file_get_contents($menu));
		} else {
			$resp->notify('Request for menu', $_GET['load_menu']);
		}
	} elseif(array_key_exists('filename', $_POST)) {
		$resp->notify('Success', "{$_POST['filename']} uploaded.");
		$uploads = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads';
		$date = new \DateTime();
		$format = 'Y' . DIRECTORY_SEPARATOR . 'm' . DIRECTORY_SEPARATOR . 'j' . DIRECTORY_SEPARATOR;
		$uploads .= DIRECTORY_SEPARATOR . $date->format($format);
		file_put_contents("{$uploads}{$_POST['filename']}", $_POST['data']);
		$resp->append('body', "<img src=\"/images/uploads/{$date->format($format)}{$_POST['filename']}\"/>");
		Core\Console::getInstance()->log($_REQUEST);
	} else {
		$resp->notify('Invalid request', 'See console for details.', DOMAIN . 'images/sun-icons/128.png');
		Core\Console::getInstance()->info($_REQUEST);
	}
	$resp->send();
	exit();
}  elseif(array_key_exists('url', $_GET)) {
	$url = new Core\URL($_GET['url']);
	if ($url->host === $_SERVER['SERVER_NAME']) {
		$header->location = "{$url}";
		http_response_code(Status\SEE_OTHER);
	} else {
		http_response_code(Status\BAD_REQUEST);
	}
}  else {
	http_response_code(Status\BAD_REQUEST);
	$header->content_type = 'application/json';
	exit('{}');
}
