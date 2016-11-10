<?php
namespace KVSun\Components\Head;

const DEV_STYLE = 'stylesheets/styles/import.css';
const STYLE     = 'stylesheets/styles/style.css';

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$head = $dom->head;
	$head->append('title', __NAMESPACE__);
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => \KVSun\DEBUG ? DEV_STYLE : STYLE,
	]);
};
