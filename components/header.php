<?php
namespace KVSun\Components\Header;

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVsun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$dom->body->append('header')->append('h1', $kvs->getHead()->title, [
		'class' => 'site-title',
		'role'  => 'banner',
	]);

};
