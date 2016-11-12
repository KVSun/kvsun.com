<?php
namespace KVSun\Components\Head;

const DEV_STYLE   = 'stylesheets/styles/import.css';
const STYLE       = 'stylesheets/styles/styles.css';
const SCRIPTS_DIR = 'scripts/';
const SCRIPTS     = array('custom.js');

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$head = $dom->head;
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => \KVSun\DEBUG ? \KVSun\DOMAIN . DEV_STYLE : \KVSun\DOMAIN . STYLE,
	]);

	foreach(SCRIPTS as $script) {
		$head->append('script', null, [
			'src' => \KVSun\DOMAIN . SCRIPTS_DIR . $script,
			'async' => '',
			'type' => 'application/javascript',
		]);
	}

	if ($pdo->connected) {
		$data = $pdo->nameValue('head');
		$head->append('title', $data->title);
		$head->append('meta', null, [
			'name' => 'viewport',
			'content' => $data->viewport
		]);
		$head->append('meta', null, [
			'name' => 'referrer',
			'content' => $data->referrer
		]);
	} else {
		$head->append('title', "{$_SERVER['SERVER_NAME']} Installation");
		$head->append('meta', null, [
			'name' => 'viewport',
			'content' => 'width=device-width'
		]);
		$head->append('meta', null, [
			'name' => 'referrer',
			'content' => 'origin-when-cross-origin'
		]);
	}
};
