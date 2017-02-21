<?php
namespace KVSun\Components\Main;
return function (
	\shgysk8zer0\DOM\HTML $dom,
	\shgysk8zer0\Core\PDO $pdo,
	\KVSun\KVSAPI\Abstracts\Content $kvs
)
{
	$main = $dom->body->append('main', null, [
		'role' => 'main',
	]);
	if (\KVSun\user_can('createPosts', 'editPosts')) {
		$main->contextmenu = 'admin_menu';
	}
	\KVSun\load('sections' . DIRECTORY_SEPARATOR . $kvs::TYPE);
};
