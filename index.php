<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

if (version_compare(PHP_VERSION, '5.6', '<')) {
	http_response_code(500);
	exit('PHP 5.6 or greater is required.');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';


DOM\HTMLElement::$import_path = COMPONENTS;

if (defined(__NAMESPACE__ . '\CSP')) {
	$csp = new Core\CSP(CSP);
	$csp(false);
	unset($csp);
}

if (@file_exists(CONFIG . DB_CREDS)) {
	$path = get_path();
	if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
		require \KVSun\PAGES_DIR . "{$path[0]}.php";
		exit();
	}
	unset($path);
	load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
	DOM\HTML::getInstance()->body->class = 'flex row wrap';
} else {
	require_once COMPONENTS . 'install-form.php';
}

Core\Listener::load();
exit(DOM\HTML::getInstance());
