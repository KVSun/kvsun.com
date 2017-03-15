<?php
namespace KVSun\Components\Sections\Category;

use function \KVSun\Functions\{use_icon};

use const \KVSun\Consts\{DOMAIN, DATETIME_FORMAT};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$section_template = $dom->getElementById('section-template');
	$main = $dom->getElementsByTagName('main')->item(0);

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
	$title->textContent = $kvs->title;
	if (isset($kvs->icon)) {
		use_icon($kvs->icon, $title);
	}

	foreach ($kvs->articles as $article) {
		$div = $container->appendChild($dom->createElement('div'));
		$a = $div->appendChild($dom->createElement('a'));
		$a->href = DOMAIN . $article->url;
		$a->textContent = $article->title;
		$a->class = 'currentColor';
	}
};
