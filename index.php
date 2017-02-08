<?php
namespace KVSun\Index;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

function build_dom(Array $path, Bool $csp_report_only = false): \DOMDocument
{
	if (defined('KVSun\CSP')) {
		(new Core\CSP(\KVSun\CSP))($csp_report_only);
	}
	if (@file_exists(\KVSun\DB_CREDS) and Core\PDO::load(\KVSun\DB_CREDS)->connected) {
		DOM\HTMLElement::$import_path = \KVSun\COMPONENTS;
		$dom = DOM\HTML::getInstance();
		if (!empty($path) and file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
			require \KVSun\PAGES_DIR . "{$path[0]}.php";
			exit();
		}
		// If IE, show update and hide rest of document
		$dom->body->ifIE(
			file_get_contents(\KVSun\COMPONENTS . 'update.html')
			. '<div style="display:none !important;">'
		);

		$dom->body->class = 'flex row wrap';

		array_map([$dom->body, 'importHTMLFile'], \KVSun\HTML_TEMPLATES);

		\KVSun\add_main_menu($dom->body);
		\KVSun\load(...\KVSun\PAGE_COMPONENTS);

		// Close `</div>` created in [if IE]
		$dom->body->ifIE('</div>');

	} else {
		$dom = new \DOMDocument();
		$dom->loadHTMLFile(\KVSun\COMPONENTS . 'install.html');
	}
	Core\Listener::load();
	return $dom;
}

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

exit(build_dom(\KVSun\get_path())->saveHTML());
