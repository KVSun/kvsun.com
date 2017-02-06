<?php
namespace KVSun\Components\Home;

return function (
	\shgysk8zer0\DOM\HTML $dom,
	\shgysk8zer0\Core\PDO $pdo,
	\KVSun\KVSAPI\Abstracts\Content $kvs
)
{
	$section_template = $dom->getElementById('section-template');
	$main = $dom->getElementsByTagName('main')->item(0);
	$date = new \shgysk8zer0\Core\DateTime('last week');
	$date->format = 'Y-m-d H:j:s';
	$console = \shgysk8zer0\Core\Console::getInstance();

	$xpath = new \DOMXPath($dom);
	foreach ($kvs->sections as $name => $section) {
		if (empty($section)) {
			continue;
		}

		foreach ($section_template->childNodes as $node) {
			if (isset($node->tagName)) {
				$container = $main->appendChild($node->cloneNode(true));
				$container->id = $name;
				$container->class = 'category';
			} else {
				continue;
			}
		}

		try {
			$title = $xpath->query('.//h2', $container)->item(0);
			$link = $title->appendChild($dom->createElement('a'));
			$title->class = 'center';
			$link->href = \KVSun\DOMAIN . "{$name}/";
			$link->textContent = $section[0]->category;
			$feed = $xpath->query('.//h2', $container)->item(0)->append('a', null, [
				'href'   => \KVSun\DOMAIN . "rss/{$name}.rss",
				'target' => '_blank',
				'style'  => 'float:right;',
				'class'  => 'feed',
			]);
			\KVSun\use_icon('rss', $feed, ['class' => 'icon currentColor']);

			foreach ($section as $article) {
				$div = $container->appendChild($dom->createElement('div'));
				$a = $div->appendChild($dom->createElement('a'));
				$a->href = \KVSun\DOMAIN . "{$article->catURL}/{$article->url}";
				$a->textContent = $article->title;
				$a->class = 'currentColor';
			}
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		}

	}
};
