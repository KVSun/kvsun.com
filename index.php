<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}
if (defined('KVSun\CSP')) {
	(new Core\CSP(\KVSun\CSP))(\KVSun\DEBUG);
}

exit(build_dom(get_path())->saveHTML());
