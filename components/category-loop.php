<?php
namespace KVSun\Components\CategoryLoop;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$main = $dom->getElementsByTagName('main')->item(0);
	$categories = $pdo('SELECT `name`, `url-name` as `url` FROM `categories`');

	foreach ($categories as $cat) {
		$section = $main->append('section', null, ['id' => $cat->url]);
		$section->append('header', null, [
			'class' => 'sticky',
		])->append('a', $cat->name, [
			'href' => \KVSun\DOMAIN . $cat->url,
		]);
	}
};
