<?php
namespace KVSun\Components\Nav;

const ATTRS = array(
	'class' => 'cat-link',
	'role' => 'button',
);

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$nav = $dom->body->append('nav');
	$nav->class = 'flex sticky';
	$nav->append('a', 'Home', array_merge(ATTRS, ['href' => \KVSun\DOMAIN]));

	$categories = $page->getCategories();
	// $categories = $pdo('SELECT `name`, `url-name` as `url` FROM `categories` ORDER BY `sort`');
	$pages = $pdo('SELECT `name`, `url` FROM `pages`');

	foreach(array_merge($categories, $pages) as $cat) {
		$nav->append('a', $cat->name, array_merge(ATTRS, ['href' => \KVSun\DOMAIN . $cat->url]));
	}
};
