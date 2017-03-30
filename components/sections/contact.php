<?php

namespace KVSun\Components\Sections\Contact;

use function \KVSun\Functions\{use_icon};

use const \KVSun\Consts\{DOMAIN, DATE_FORMAT, DATETIME_FORMAT};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO, DateTime};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI, Picture};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$main = $dom->getElementsByTagName('main')->item(0);
	if ($template = $dom->getElementById('itemtype-Organization')) {
		foreach ($template->childNodes as $node) {
			$main->appendChild($node->cloneNode(true));
		}
	}
};
