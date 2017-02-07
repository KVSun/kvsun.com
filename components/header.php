<?php
namespace KVSun\Components\Header;
return function (
	\shgysk8zer0\DOM\HTML $dom,
	\shgysk8zer0\Core\PDO $pdo,
	\KVSun\KVSAPI\Abstracts\Content $kvs
)
{
	$dom->body->append('header')->append('h1', $kvs->getHead()->title, [
		'class' => 'site-title',
		'role'  => 'banner',
	]);

};
