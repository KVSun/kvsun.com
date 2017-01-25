<?php
namespace KVSun\Components\Main;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page, $kvs)
{
	$main = $dom->body->append('main');

	switch($kvs::TYPE) {
		case 'home':
			\KVSun\load('category-loop');
			break;

		case 'category':
			break;

		case 'article':
			\KVSun\load('article');
			break;

		default:
			/*if (file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
				$page = require \KVSun\PAGES_DIR . "{$path[0]}.php";
				call_user_func_array($page, func_get_args());
			}*/
			trigger_error('Request for unknown page.');
	}
};
