<?php

namespace KVSun\Components\Sections\Home;

use function \KVSun\Functions\{use_icon};

use const \KVSun\Consts\{DOMAIN, DATE_FORMAT, DATETIME_FORMAT};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO, DateTime};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI, Picture};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$pictures = new Picture($pdo);
	$section_template = $dom->getElementById('section-template');
	$main = $dom->getElementsByTagName('main')->item(0);
	$date = new DateTime('last week');
	$date->format = DATETIME_FORMAT;

	$xpath = new \DOMXPath($dom);
	foreach (get_object_vars($kvs->categories) as $name => $section) {
		if (empty($section)) {
			continue;
		}

		foreach ($section_template->childNodes as $node) {
			if (isset($node->tagName)) {
				$container = $main->appendChild($node->cloneNode(true));
				$container->id = $section->catURL;
				$container->class = 'category flex row wrap';
			} else {
				continue;
			}
		}

		try {
			$title = $xpath->query('.//h2', $container)->item(0);
			$link = $title->appendChild($dom->createElement('a'));
			$title->class = 'center';
			$link->href = DOMAIN . "{$section->catURL}/";
			$link->textContent = $name;
			$feed = $xpath->query('.//h2', $container)->item(0)->append('a', null, [
				'href'   => DOMAIN . "rss/{$section->catURL}.rss",
				'target' => '_blank',
				'style'  => 'float:right;',
				'class'  => 'feed',
			]);
			use_icon('rss', $feed, ['class' => 'icon currentColor']);

			foreach ($section->posts as $article) {
				$el = $container->append('span', null, ['class' => 'article-thumb']);
				$a = $el->appendChild($dom->createElement('a'));
				$a->href = DOMAIN . ltrim($article->url, '/');
				$a->append('h4', $article->title);
				$a->append('h6', "Posted on {$article->posted} by {$article->author}");
				$a->class = 'currentColor';
				if (isset($article->img)) {
					$pictures->getPicture(
						$article->img,
						$a,
						[
							'(max-width: 800px) 96%',
							'14vw',
						],
						false
					);
				}
			}
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		}

	}
};
