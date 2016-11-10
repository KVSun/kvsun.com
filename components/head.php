<?php
namespace KVSun\Components\Head;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$head = $dom->head;
	$head->append('title', __NAMESPACE__);
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => 'stylesheets/styles/import.css',
	]);
};
