<?php
namespace KVSun\Components\Header;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$header = $dom->body->append('header');
	$header->append('img', null, [
		'src' => '/images/sun-icons/sun-rise.svg',
	]);
};
