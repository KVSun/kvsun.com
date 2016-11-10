<?php
namespace KVSun\Components\Nav;

const CATEGORIES = array(
	'News'          => 'news',
	'Sports'        => 'sports',
	'Life'          => 'life',
	'Obituaries'    => 'obituaries',
	'Entertainment' => 'entertainment',
	'Opinion'       => 'opinion',
);
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	\shgysk8zer0\Core\Console::getInstance()->log($_SERVER);
	$nav = $dom->body->append('nav');
	foreach(CATEGORIES as $cat => $link) {
		$nav->append('a', $cat, [
			'href' => \KVSun\DOMAIN . $link,
			'class' => 'cat-link',
		]);
	}
};
