<?php
namespace KVSun\Components\Header;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$header = $dom->body->append('header');
	$logo = $header->append('img', null, [
		'src' => \KVSun\DOMAIN . '/images/sun-icons/sun-rise.svg',
	]);
	if ($_SERVER['REQUEST_URI'] !== '/') {
		$logo->hidden = '';
	}
};
