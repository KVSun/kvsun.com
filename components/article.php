<?php
namespace KVSun\Components\Article;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$article = $dom->getElementsByTagName('main')->item(0)->append('article');
	if (is_object($page) and isset($page->content, $page->posted, $page->title)) {
		$header = $article->append('header');
		$header->append('h2', $page->title);
		$header->append('span', "By {$page->author}");
		$header->append('span', " on {$page->posted}");
		$article->importHTML($page->content);
	} else {
		trigger_error('Invalid page contents given.');
	}
};
