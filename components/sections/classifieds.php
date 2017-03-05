<?php
namespace KVSun\Components\Sections\Classifieds;

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Comments, Abstracts\Content as KVSAPI};

use const \KVSun\Consts\{DOMAIN, ICONS};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$main = $dom->body->getElementsByTagName('main')->item(0);
	$container = $main->append('div', null, [
		'class'     => 'classifieds',
		'data-cols' => 'auto',
	]);
	$details = [];

	foreach ($kvs->categories as $id => $category) {
		if (array_key_exists($id, $kvs->content)) {
			$details[$id] = $container->append('details', null, [
				'open' => '',
			]);
			$details[$id]->append('summary')->append('b', $category);
			$details[$id]->importHTML($kvs->content[$id]);
		}
	}
	foreach($kvs->ads as $id => $cat) {
		if (! array_key_exists($id, $kvs->categories)) {
			continue;
		} elseif (! array_key_exists($id, $details)) {
			$details[$id] = $container->append('details', null, [
				'open' => '',
			]);
			$details[$id]->append('summary')->append('b', $kvs->categories[$id]);
		}
		foreach ($cat as $ad) {
			$details[$id]->append('img', null, [
				'src'    => DOMAIN . $ad['image'],
				'alt'    => $ad['text'],
				'title'  => $ad['text'],
			]);
			$details[$id]->append('br');
		}
	}
};
