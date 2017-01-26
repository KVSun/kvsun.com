<?php
namespace KVSun\Components\Home;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page, $kvs)
{
	$section_template = $dom->getElementById('section-template');
	$main = $dom->getElementsByTagName('main')->item(0);
	$date = new \shgysk8zer0\Core\DateTime('last week');
	$date->format = 'Y-m-d H:j:s';
	$console = \shgysk8zer0\Core\Console::getInstance();

	$xpath = new \DOMXPath($dom);
	foreach (get_object_vars($kvs->sections) as $name => $section) {
		if (empty($section)) {
			continue;
		}

		foreach ($section_template->childNodes as $name => $node) {
			if (isset($node->tagName)) {
				$container = $main->appendChild($node->cloneNode(true));
				$container->id = $name;
				$container->class = 'category';
			} else {
				continue;
			}
		}

		$title = $xpath->query('.//h2', $container)->item(0);
		$link = $title->appendChild($dom->createElement('a'));
		$title->class = 'center';
		$link->href = \KVSun\DOMAIN . "{$name}/";
		$link->textContent = $section[0]->category;

		foreach ($section as $article) {
			$div = $container->appendChild($dom->createElement('div'));
			$a = $div->appendChild($dom->createElement('a'));
			$a->href = \KVSun\DOMAIN . "{$article->catURL}/{$article->url}";
			$a->textContent = $article->title;
			$a->class = 'currentColor';
		}
	}
};
