<?php
namespace KVSun\Components\Sections\BusinessDirectory;

use const \KVSun\Consts\{DOMAIN};
use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$main = $dom->getElementsByTagName('main')->item(0);
	$main->append('h3', $kvs->title, ['class' => 'center']);
	$template = $dom->getElementById('business-listing');
	$container = $main->append('div', null, ['data-cols' => 'auto']);
	$xpath = new \DOMXPath($dom);

	foreach ($kvs->categories as $category => $list) {
		$details = $container->append('details', null, ['open' => null]);
		$details->append('summary', $category);

		foreach ($list as $item) {
			$org = $details->append('div');
			foreach ($template->childNodes as $node) {
				$org->appendChild($node->cloneNode(true));
			}
			$name = $xpath->query('.//*[@itemprop="legalName"]', $org)->item(0);
			$img = $xpath->query('.//*[@itemprop="image"]', $org)->item(0);
			$desc = $xpath->query('.//*[@itemprop="description"]', $org)->item(0);
			$name->textContent = $item->name;
			if (isset($item->image)) {
				$img->src = DOMAIN . ltrim($item->image, '/');
				$img->content = $img->src;
			} else {
				$img->parentNode->removeChild($img);
			}

			if (isset($item->text)) {
				$desc->textContent = $item->text;
			} else {
				$desc->parentNode->removeChild($desc);
			}
		}
	}
};
