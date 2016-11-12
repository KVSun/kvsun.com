<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;

ob_start();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

if (DEBUG) {
	Core\Console::getInstance()->asErrorHandler()->asExceptionHandler();
}

$header = Core\Headers::getInstance();
if ($header->accept === 'application/json') {
	$resp = new Core\JSON_Response();
	$resp->notify('Title', $_REQUEST['url'], DOMAIN . 'images/sun-icons/128.png');
	$resp->send();
} elseif(array_key_exists('url', $_GET)) {
	$header->location = $_GET['url'];
	http_response_code(303);
	exit();
} else {
	http_response_code(404);
	exit();
}
