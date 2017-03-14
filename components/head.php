<?php
namespace KVSun\Components\Head;

use const \KVSun\Consts\{DOMAIN, DEBUG, STYLE, DEV_STYLE, SCRIPTS, SCRIPTS_DIR};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVsun\KVSAPI\{Abstracts\Content as KVSAPI};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	http_response_code($kvs->getStatus() ?? 200);
	$dom->documentElement->itemscope = '';
	$dom->documentElement->itemtype = 'https://schema.org/WebPage';
	$head = $dom->head;
	$head->ifIE('<script type="text/javascript">
	var html5=new Array(\'header\',\'hgroup\',\'nav\',\'menu\',\'main\',\'section\',\'article\',\'footer\',\'aside\',\'mark\', \'details\', \'summary\', \'dialog\', \'figure\', \'figcaption\', \'picture\', \'source\');
	for(var i=0;i<html5.length;i++){document.createElement(html5[i]);}
</script>', 8, 'lte');

	if ($pdo->connected) {
		$data = $kvs->getHead();

		$head->append(
			'title',
			isset($kvs->title) ? "{$data->title} | {$kvs->title}" : $data->title, [
				'itemprop' => 'name',
		]);
		$head->append('base', null, ['href' => DOMAIN]);
		$head->append('link', null, [
			'rel' => 'canonical',
			'href' =>DOMAIN . ltrim($_SERVER['REQUEST_URI'], '/'),
			'itemprop' => 'url',
		]);
		$head->append('meta', null, [
			'content' => DOMAIN . ltrim($_SERVER['REQUEST_URI'], '/'),
			'itemprop' => 'url',
		]);
		$head->append('meta', null, [
			'name' => 'viewport',
			'content' => $data->viewport
		]);
		$head->append('meta', null, [
			'name' => 'referrer',
			'content' => $data->referrer
		]);

		if (isset($kvs->description)) {
			$head->append('meta', null, [
				'name' => 'description',
				'content' => $kvs->description,
			]);
			$head->append('meta', null, [
				'itemprop' => 'description',
				'content' => $kvs->description,
			]);
		}
		if (isset($kvs->keywords)) {
			$head->append('meta', null, [
				'name' => 'keywords',
				'content' => is_array($kvs->keywords)
					? join(', ', $kvs->keywords)
					: $kvs->keywords,
			]);
			$head->append('meta', null, [
				'itemprop' => 'keywords',
				'content' => is_array($kvs->keywords)
					? join(', ', $kvs->keywords)
					: $kvs->keywords,
			]);
		}
	} else {
		$head->append('title', "{$_SERVER['SERVER_NAME']} Installation");
		$head->append('meta', null, [
			'name' => 'viewport',
			'content' => 'width=device-width'
		]);
		$head->append('meta', null, [
			'name' => 'referrer',
			'content' => 'origin-when-cross-origin'
		]);
	}
	if (@file_exists('manifest.json')) {
		$manifest = json_decode(file_get_contents('manifest.json'));
		$head->append('link', null, [
			'rel' => 'manifest',
			'href' => DOMAIN . '/manifest.json'
		]);
		$head->append('meta', null, [
			'name' => 'mobile-web-app-capable',
			'content' => 'yes'
		]);
		$head->append('meta', null, [
			'name' => 'theme-color',
			'content' => $manifest->theme_color
		]);

		foreach ($manifest->icons as $icon) {
			$head->append('link', null, [
				'rel' => 'icon',
				'href' => DOMAIN . $icon->src,
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
			$head->append('link', null, [
				'rel' => 'apple-touch-icon',
				'href' => DOMAIN . $icon->src,
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
		}
	} else {
		$head->append('link', null, [
			'rel' => 'icon',
			'href' => DOMAIN . LOGO_VECTOR,
			'type' => 'image/svg+xml',
			'sizes' => 'any',
		]);
	}

	foreach(SCRIPTS as $script) {
		$head->append('script', null, [
			'src' => DOMAIN . SCRIPTS_DIR . $script,
			'async' => '',
			'type' => 'application/javascript',
		]);
	}

	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => DEBUG ? DOMAIN . DEV_STYLE : DOMAIN . STYLE,
	]);
};
