<?php
namespace KVSun\Components\CategoryLoop;

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
		try {
			foreach ($section_template->childNodes as $node) {
				$node = $main->appendChild($node->cloneNode(true));
				$node->id = $name;
				$node->class = 'category';
			}

			\shgysk8zer0\Core\Console::log([$name => $section]);
			$container = $main->getElementById($name);
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
		} catch (\Throwable $e) {
			\shgysk8zer0\Core\Console::error([
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTrace(),
			]);
		}
	}
};
