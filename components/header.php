<?php
namespace KVSun\Components\Header;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page, $kvs)
{
	$dom->body->append('header')->append('h1', $kvs->getHead()->title, ['class' => 'site-title']);

};
