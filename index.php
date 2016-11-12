<?php
namespace KVSun;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

if (DEBUG) {
	\shgysk8zer0\Core\Console::getInstance()->asErrorHandler()->asExceptionHandler();
}
define('URL', \shgysk8zer0\Core\URL::getInstance());

$path = get_path();
if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
	require \KVSun\PAGES_DIR . "{$path[0]}.php";
	exit();
}
unset($path);
load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
exit(\shgysk8zer0\DOM\HTML::getInstance());
