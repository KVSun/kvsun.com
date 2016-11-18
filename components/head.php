<?php
namespace KVSun\Components\Head;

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$head = $dom->head;

	if ($pdo->connected) {
		$data = $pdo->nameValue('head');
		$head->append('title', isset($page->title) ? "{$data->title} | {$page->title}" : $data->title);
		$head->append('meta', null, [
			'name' => 'viewport',
			'content' => $data->viewport
		]);
		$head->append('meta', null, [
			'name' => 'referrer',
			'content' => $data->referrer
		]);

		if (isset($page->description)) {
			$head->append('meta', null, [
				'name' => 'description',
				'content' => $page->description,
			]);
		}
		if (isset($page->keywords)) {
			$head->append('meta', null, [
				'name' => 'keywords',
				'content' => $page->keywords,
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
	$head->append('link', null, [
		'rel' => 'icon',
		'href' => \KVSun\DOMAIN . 'images/sun-icons/any.svg',
		'type' => 'image/svg+xml',
		'sizes' => 'any',
	]);
	$head->append('link', null, [
		'rel' => 'stylesheet',
		'type' => 'text/css',
		'href' => \KVSun\DEBUG ? \KVSun\DOMAIN . \KVSun\DEV_STYLE : \KVSun\DOMAIN . \KVSun\STYLE,
	]);

	// foreach(\KVSun\SCRIPTS as $script) {
	// 	$head->append('script', null, [
	// 		'src' => \KVSun\DOMAIN . \KVSun\SCRIPTS_DIR . $script,
	// 		'async' => '',
	// 		'type' => 'application/javascript',
	// 	]);
	// }
};
