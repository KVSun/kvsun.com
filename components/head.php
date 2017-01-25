<?php
namespace KVSun\Components\Head;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page, $kvs)
{
	$dom->documentElement->itemscope = '';
	$dom->documentElement->itemtype = 'https://schema.org/WebSite';
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
		$head->append('base', null, ['href' => \KVSun\DOMAIN]);
		$head->append('link', null, [
			'rel' => 'canonical',
			'href' => \KVSun\DOMAIN . ltrim($_SERVER['REQUEST_URI'], '/'),
			'itemprop' => 'url',
		]);
		$head->append('meta', null, [
			'content' => \KVSun\DOMAIN . ltrim($_SERVER['REQUEST_URI'], '/'),
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
				'content' => $kvs->keywords,
			]);
			$head->append('meta', null, [
				'itemprop' => 'keywords',
				'content' => $kvs->keywords,
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
				'href' => \KVSun\DOMAIN . trim($icon->src, '/'),
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
			$head->append('link', null, [
				'rel' => 'apple-touch-icon',
				'href' => \KVSun\DOMAIN . trim($icon->src, '/'),
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
		}
	}
	if (@file_exists('manifest.json')) {
		$manifest = json_decode(file_get_contents('manifest.json'));
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
				'href' => \KVSun\DOMAIN . $icon->src,
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
			$head->append('link', null, [
				'rel' => 'apple-touch-icon',
				'href' => \KVSun\DOMAIN . $icon->src,
				'sizes' => $icon->sizes,
				'type' => $icon->type
			]);
		}
	} else {
		$head->append('link', null, [
			'rel' => 'icon',
			'href' => \KVSun\DOMAIN . 'images/sun-icons/any.svg',
			'type' => 'image/svg+xml',
			'sizes' => 'any',
		]);
	}
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => \KVSun\DOMAIN . \KVSun\DEV_STYLE,
	]);

	foreach(\KVSun\SCRIPTS as $script) {
		$head->append('script', null, [
			'src' => \KVSun\DOMAIN . \KVSun\SCRIPTS_DIR . $script,
			'async' => '',
			'type' => 'application/javascript',
		]);
	}
};
