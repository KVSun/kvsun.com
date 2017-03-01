<?php
namespace KVSun\Components\Sidebar;

use function \KVSun\Functions\{use_icon};

use const \KVSun\Consts\{DOMAIN};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Picture};

return function (HTML $dom, PDO $pdo)
{
	$sidebar = $dom->body->append('aside', null, [
		'role' => 'sidebar',
	]);

	$search = $sidebar->append('form', null, [
		'name'   => 'search',
		'action' => DOMAIN . 'api.php',
		'role'   => 'search',
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

	make_rail($sidebar->append('div', null, ['class' => 'center']), $pdo, 7);

	$submit = $search->append('button', null, [
		'type' => 'submit',
		'class' => 'icon',
	]);

	use_icon('search', $submit, [
		'class' => 'icon',
	]);

	$list = $sidebar->append('ul');
	foreach($pdo('SELECT `name`, `url-name` AS `url` FROM `categories`') as $category) {
		$list->append('li')->append('a', $category->name, [
			'href' => $category->url,
		]);
	}
};

function make_rail(\DOMElement $parent, PDO $pdo, Int $limit = 7, Int $size = 256)
{
	try {
		$picture = new Picture($pdo);
		foreach($pdo(
			"SELECT
				`categories`.`name` AS `category`,
				`categories`.`url-name` as `catURL`,
				`posts`.`title`,
				`posts`.`url`,
				`posts`.`img`
			FROM `posts`
			JOIN `categories` ON `categories`.`id` = `posts`.`cat-id`
			WHERE `posts`.`img` IS NOT NULL
			ORDER BY `posts`.`posted` DESC
			LIMIT $limit;"
		) as $post) {
			$link = $parent->append('a', null, [
				'href' => DOMAIN . "{$post->catURL}/{$post->url}",
			]);
			$picture->getPicture($post->img, $link);
			$link->append('p', "{$post->category} &raquo; {$post->title}");
			$parent->append('hr');
		};
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
	}
}
