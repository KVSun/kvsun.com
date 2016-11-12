<?php
namespace KVSun\Components\Article;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$article = $dom->getElementsByTagName('main')->item(0)->append('article');
	$post = $pdo('SELECT
			`id`,
			`title`,
			`author`,
			`content`,
			`posted`,
			`updated`,
			`keywords`,
			`description`
		FROM `posts`;'
	)[0];
	$header = $article->append('header');
	$header->append('h2', $post->title);
	$header->append('span', "By {$post->author}");
	$header->append('span', " on {$post->posted}");
	$article->importHTML($post->content);
};
