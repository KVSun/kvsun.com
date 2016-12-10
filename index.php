<?php
namespace KVSun;
error_reporting(0);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

if (DEBUG) {
	\shgysk8zer0\Core\Console::getInstance()->asExceptionHandler();
	set_error_handler(__NAMESPACE__ . '\exception_error_handler');
}

define('URL', \shgysk8zer0\Core\URL::getInstance());
\shgysk8zer0\DOM\HTMLElement::$import_path = COMPONENTS;
$csp = new \shgysk8zer0\Core\CSP([
	'default-src'  => "'self'",
	'img-src'      => '*',
	'script-src'   => "'self'",
	'style-src'    => ["'self'", "'unsafe-inline'"],
	'media-src'    => '*',
]);
$csp();
unset($csp);

$path = get_path();
if (@file_exists(CONFIG . DB_CREDS)) {
	if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
		require \KVSun\PAGES_DIR . "{$path[0]}.php";
		exit();
	}
	unset($path);
	load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
	\shgysk8zer0\DOM\HTML::getInstance()->body->class = 'flex row wrap';
	\shgysk8zer0\DOM\HTML::getInstance()->body->contextmenu = 'wysiwyg_menu';
} else {
	require_once COMPONENTS . 'install-form.php';
}
if (DEBUG) {
	\shgysk8zer0\Core\Console::getInstance()->sendLogHeader();
}
exit(\shgysk8zer0\DOM\HTML::getInstance());
