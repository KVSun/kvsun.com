<?php
namespace KVSun\Index;

use const \KVSun\{CSP as CSP_POLICY, DEBUG};

use function \KVSun\{build_dom, get_path};

use \shgysk8zer0\Core\CSP;
use \shgysk8zer0\DOM;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}
if (defined('KVSun\CSP')) {
	(new CSP(CSP_POLICY))(DEBUG);
}

exit(build_dom(get_path())->saveHTML());
