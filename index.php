<?php
namespace KVSun;
error_reporting(0);

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
	http_response_code(500);
	exit('PHP 7 or greater is required.');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

if (DEBUG) {
	\shgysk8zer0\Core\Console::getInstance()->asExceptionHandler();
	set_error_handler(__NAMESPACE__ . '\exception_error_handler');
}

if (defined(__NAMESPACE__ . '\CSP')) {
	(new \shgysk8zer0\Core\CSP(CSP))();
}

define('URL', \shgysk8zer0\Core\URL::getInstance());
\shgysk8zer0\DOM\HTMLElement::$import_path = COMPONENTS;

if (@file_exists(CONFIG . DB_CREDS)) {
	$path = get_path();
	if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
		require \KVSun\PAGES_DIR . "{$path[0]}.php";
		exit();
	}
	unset($path);
	load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
	\shgysk8zer0\DOM\HTML::getInstance()->body->class = 'flex row wrap';
} else {
	require_once COMPONENTS . 'install-form.php';
}

if (DEBUG) {
	\shgysk8zer0\Core\Console::getInstance()->sendLogHeader();
}

exit(\shgysk8zer0\DOM\HTML::getInstance());
