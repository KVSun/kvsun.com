<?php
namespace KVSun\Components\Sidebar;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$sidebar = $dom->body->append('aside');
	$search = $sidebar->append('form', null, [
		'name' => 'search',
		'action' => \KVSun\DOMAIN . 'api.php',
		'method' => 'post',
	]);
	$search->append('input', null, [
		'type' => 'search',
		'name' => 'search[query]',
		'pattern' => '[\w\- ]+',
		'placeholder' => 'Search for...',
		// 'list' => 'search-suggestions',
		'required' => '',
	]);

	$search->append('button', null, ['type' => 'submit', 'data-icon' => ' L']);
	$list = $sidebar->append('ul');
	foreach($page->getCategories() as $category) {
		$list->append('li')->append('a', $category->name, ['href' => $category->url]);
	}
};
