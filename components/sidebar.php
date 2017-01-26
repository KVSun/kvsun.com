<?php
namespace KVSun\Components\Sidebar;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
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

	make_rail($sidebar->append('div', null, ['class' => 'center']), $pdo);

	$submit = $search->append('button', null, ['type' => 'submit', 'class' => 'icon']);
	\KVSun\use_icon('search', $submit, ['class' => 'icon']);
	$list = $sidebar->append('ul');
	foreach($pdo('SELECT `name`, `url-name` AS `url` FROM `categories`') as $category) {
		$list->append('li')->append('a', $category->name, ['href' => $category->url]);
	}
};

function make_rail(\DOMElement $parent, \shgysk8zer0\Core\PDO $pdo)
{
	foreach($pdo(
		'SELECT
			`categories`.`name` AS `category`,
			`categories`.`url-name` as `catURL`,
			`posts`.`title`,
			`posts`.`url`,
			`posts`.`img`
		FROM `posts`
		JOIN `categories` on `categories`.`id` = `posts`.`cat-id`
		WHERE `posts`.`img` IS NOT NULL
		ORDER BY `posts`.`posted` DESC
		LIMIT 7;'
	) as $post) {
		$link = $parent->append('a', null, [
			'href' => \KVSun\DOMAIN . "{$post->catURL}/{$post->url}",
		]);
		$link->append('img', null, [
			'src' => $post->img,
			'width' => 256,
			'height' => 256,
		]);
		$link->append('p', "{$post->category} &gt; {$post->title}");
		$parent->append('hr');
	};
}
