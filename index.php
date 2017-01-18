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

if (@file_exists(DB_CREDS) or !Core\PDO::load(DB_CREDS)->connected) {
	$path = get_path();
	if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
		require \KVSun\PAGES_DIR . "{$path[0]}.php";
		exit();
	}
	unset($path);
	// If IE, show update and hide rest of document
	DOM\HTML::getInstance()->body->ifIE(
		file_get_contents(\KVSun\COMPONENTS . 'update.html')
		. '<div style="display:none !important;">'
	);

	load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
	DOM\HTML::getInstance()->body->class = 'flex row wrap';

	// Close `<div>` created in [if IE]
	DOM\HTML::getInstance()->body->ifIE('</div>');

} else {
	require_once COMPONENTS . 'install.html';
}

Core\Listener::load();
exit(DOM\HTML::getInstance());
