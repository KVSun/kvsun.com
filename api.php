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
	if (array_key_exists('form', $_REQUEST) and is_array($_REQUEST[$_REQUEST['form']])) {
		require_once COMPONENTS . 'handlers' . DIRECTORY_SEPARATOR . 'form.php';
	} else {
		$resp->notify('Invalid request', null, DOMAIN . 'images/sun-icons/128.png');
	}
	Core\JSON_Response::getInstance()->send();
	exit();
} elseif(array_key_exists('url', $_GET)) {
	$url = new Core\URL($_GET['url']);
	if ($url->host === $_SERVER['SERVER_NAME']) {
		$header->location = "{$url}";
		http_response_code(Status\SEE_OTHER);
	} else {
		http_response_code(Status\BAD_REQUEST);
	}
	exit();
} else {
	http_response_code(Status\BAD_REQUEST);
	exit();
}
