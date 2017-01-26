<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

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
	$dom = DOM\HTML::getInstance();
	// If IE, show update and hide rest of document
	$dom->body->ifIE(
		file_get_contents(COMPONENTS . 'update.html')
		. '<div style="display:none !important;">'
	);

	$dom->body->class = 'flex row wrap';

	array_map(
		[$dom->body, 'importHTMLFile'],
		HTML_TEMPLATES
	);

	add_main_menu($dom->body);
	load('head', 'header', 'nav', 'main', 'sidebar', 'footer');

	// Close `</div>` created in [if IE]
	$dom->body->ifIE('</div>');

} else {
	require_once COMPONENTS . 'install.html';
}

Core\Listener::load();
exit(DOM\HTML::getInstance());
