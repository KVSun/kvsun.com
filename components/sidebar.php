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

	$sidebar->append('a', null, [
		'href' => 'http://www.kvhd.org/',
	], [
		['img', null, [
			'src'    => DOMAIN . sprintf('images/ads/kvhd%d.jpg', rand(1,4)),
			'alt'    => 'KVHD',
			'height' => 250,
			'width'  => 300,
		]]
	]);

	$sidebar->append('br');

	$sidebar->append('a', null, [
		'href' => 'http://www.frandy.net/',
	], [
		['img', null, [
			'src'    => DOMAIN . 'images/ads/frandy.jpg',
			'alt'    => 'Frandy Park Campground',
			'height' => 521,
			'width'  => 625,
		]]
	]);
	$sidebar->append('br');

	$sidebar->append('a', null, [
		'href' => 'http://zapquote4.appspot.com/home?agentpath=hpthal',
	], [
		['img', null, [
			'src'    => DOMAIN . 'images/ads/thal.jpg',
			'alt'    => 'Harry P. Thal Insurance Agency',
			'height' => 521,
			'width'  => 625,
		]]
	]);
	$sidebar->append('br');

	// $search = $sidebar->append('form', null, [
	// 	'name'   => 'search',
	// 	'action' => DOMAIN . 'api.php',
	// 	'role'   => 'search',
	// 	'method' => 'post',
	// ]);
	//
	// $search->append('input', null, [
	// 	'type' => 'search',
	// 	'name' => 'search[query]',
	// 	'pattern' => '[\w\- ]+',
	// 	'placeholder' => 'Search for...',
	// 	// 'list' => 'search-suggestions',
	// 	'required' => '',
	// ]);
	//
	// $search->append('button', null, [
	// 	'type' => 'submit',
	// 	'class' => 'icon',
	// ]);
	// use_icon('search', $submit, [
	// 	'class' => 'icon',
	// ]);

	// make_rail($sidebar->append('div', null, ['class' => 'center']), $pdo, 7);



	// $list = $sidebar->append('ul');
	// foreach($pdo('SELECT `name`, `url-name` AS `url` FROM `categories`') as $category) {
	// 	$list->append('li')->append('a', $category->name, [
	// 		'href' => $category->url,
	// 	]);
	// }
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
			$picture->getPicture(
				$post->img,
				$link,
				[
					'(max-width: 800px) 100%',
					'30vw'
				],
				false
			);
			$link->append('p', "{$post->category} &raquo; {$post->title}");
			$parent->append('hr');
		};
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
	}
}
