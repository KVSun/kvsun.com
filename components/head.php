<?php
namespace KVSun\Components\Head;

const DEV_STYLE  = 'stylesheets/styles/import.css';
const STYLE      = 'stylesheets/styles/styles.css';

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$head = $dom->head;
	$head->append('title', __NAMESPACE__);
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => \KVSun\DEBUG ? \KVSun\DOMAIN . DEV_STYLE : \KVSun\DOMAIN . STYLE,
	]);
	$head->append('meta', null, [
		'name' => 'viewport',
		'content' => 'width=device-width'
	]);
	$head->append('meta', null, [
		'name' => 'referrer',
		'content' => 'origin-when-cross-origin'
	]);
};
