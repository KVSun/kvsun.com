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
	} elseif(array_key_exists('upload', $_FILES)) {
		$file = new \shgysk8zer0\Core\UploadFile('upload');
		if (in_array($file->type, ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'])) {
			if ($file->saveTo('images', 'uploads', date('Y'), date('m'))) {
				header('Content-Type: application/json');
				exit($file);
			} else {
				$resp->notify('Failed', 'Could not save uploaded file.');
			}
		} else {
			throw new \Exception("{$file->name} has a type of {$file->type}, which is not allowed.");
		}
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
