<?php
namespace KVSun\Index;

use const \KVSun\Consts\{CSP as CSP_POLICY, DEBUG};

use function \KVSun\Functions\{build_dom, get_path};
use function \KVSun\Consts\{defined};

use \shgysk8zer0\Core\{CSP};

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}
if (defined('CSP')) {
	(new CSP(CSP_POLICY))();
}

exit(build_dom(get_path())->saveHTML());
