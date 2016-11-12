<?php
namespace KVSun\Components\Main;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$main = $dom->body->append('main');
	$path = \KVSun\get_path();

	if (count($path) === 0) {
		\KVSun\load('category-loop');
	} else {
		switch(count($path)) {
			case 1:
				if (file_exists(\KVSun\PAGES_DIR . "{$path[0]}.php")) {
					$page = require \KVSun\PAGES_DIR . "{$path[0]}.php";
					call_user_func_array($page, func_get_args());
				}
				break;
			case 2:
				\KVSun\load('article');
				break;
		}
	}
};
