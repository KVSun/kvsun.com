<?php
namespace KVSun\Components\Main;
use function \KVSun\Functions\{load, user_can};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVsun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$main = $dom->body->append('main', null, [
		'role' => 'main',
	]);
	if (user_can('createPosts', 'editPosts')) {
		$main->contextmenu = 'admin_menu';
	}
	load('sections' . DIRECTORY_SEPARATOR . $kvs::TYPE);
};
