<?php
namespace KVSun\Components\Sections\BusinessDirectory;

use function \KVSun\Functions\{use_icon};

use const \KVSun\Consts\{DOMAIN, DATETIME_FORMAT};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$main = $dom->getElementsByTagName('main')->item(0);

	foreach ($kvs->categories as $category => $list) {
		$details = $main->append('details', null, ['open' => null]);
		$details->append('summary', $category);

		foreach ($list as $item) {
			$details->append('img', null, [
				'src' => $item->image,
				'alt' => $item->name,
			]);
		}
	}
};
