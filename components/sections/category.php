<?php
namespace KVSun\Components\Category;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page, $kvs)
{
	$section_template = $dom->getElementById('section-template');
	$main = $dom->getElementsByTagName('main')->item(0);
	$date = new \shgysk8zer0\Core\DateTime('last week');
	$date->format = 'Y-m-d H:j:s';
	$console = \shgysk8zer0\Core\Console::getInstance();

	$xpath = new \DOMXPath($dom);

	foreach ($section_template->childNodes as $node) {
		if (isset($node->tagName)) {
			$main->appendChild($node->cloneNode(true));
		} else {
			continue;
		}
	}

	$container = $main->firstChild;
	$container->id = $kvs->category;
	$container->class = 'category';
	$title = $xpath->query('.//h2', $container)->item(0);
	$title->class = 'center';
	$title->href = \KVSun\DOMAIN . "{$name}/";
	$title->textContent = $kvs->title;

	foreach ($kvs->articles as $article) {
		$div = $container->appendChild($dom->createElement('div'));
		$a = $div->appendChild($dom->createElement('a'));
		$a->href = \KVSun\DOMAIN . "{$article->catURL}/{$article->url}";
		$a->textContent = $article->title;
		$a->class = 'currentColor';
	}
};
