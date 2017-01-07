<?php
namespace KVSun\Components\CategoryLoop;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$main = $dom->getElementsByTagName('main')->item(0);
	$categories = $page->getCategories();
	$date = new \shgysk8zer0\Core\DateTime('last week');
	$date->format = 'Y-m-d H:j:s';
	$console = \shgysk8zer0\Core\Console::getInstance();

	foreach ($categories as $cat) {
		$section = $main->append('section', null, [
			'id' => $cat->url,
			'class' => 'category',
			// 'data-scroll-snap' => 'vertical',
		]);
		$header = $section->append('header');
		$header->append('h2', null, ['class' => 'center'])->append('a', $cat->name, [
			'href' => \KVSun\DOMAIN . $cat->url,
		]);
		$stm = $pdo->prepare('SELECT `title`,
				`author`,
				`posted`,
				`url`
			FROM `posts`
			WHERE `cat-id` = :cat
			ORDER BY `posted` DESC
			LIMIT 15;
		');
		$stm->cat = $cat->id;
		$posts = $stm->execute()->getResults();
		foreach($posts as $post) {
			$container = $section->append('div');
			$container->append('a', $post->title, [
				'href' => \KVSun\DOMAIN . "{$cat->url}/{$post->url}",
				'class' => 'currentColor',
			]);
		}

	}
};
