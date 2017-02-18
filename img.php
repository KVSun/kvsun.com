<?php

namespace KVSun\ImageTest;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

DOM\HTMLElement::$import_path = \KVSun\COMPONENTS;
$dom = DOM\HTML::getInstance();
\KVSun\add_main_menu($dom->body);
$dom->body->class = 'flex row wrap';
$dom->head->append('title', __NAMESPACE__);

$dom->head->append('link', null, [
	'rel' => 'stylesheet',
	'type' => 'text/css',
	'href' => \KVSun\DOMAIN . \KVSun\DEV_STYLE,
]);
\KVSun\load('header', 'nav');
$main = $dom->body->append('main', null, [
	'role' => 'main',
]);
if (\KVSun\check_role('editor')) {
	$main->contextmenu = 'admin_menu';
}
\KVSun\load('sidebar', 'footer');

if (array_key_exists('upload', $_FILES)) {
	$imgs = Core\Image::responsiveImagesFromUpload(
		'upload',
		['images', 'uploads', date('Y'), date('m')],
		[1200, 800, 600, 300],
		['image/webp', 'image/jpeg']
	);
	Core\Console::info($imgs);
	\KVSun\make_picture($imgs, $main, 'Chris Zuber', 'Testing 1 2 3');
}
$form = $main->append('form', null, [
	'action'  => $_SERVER['PHP_SELF'],
	'method'  => 'post',
	'enctype' => 'multipart/form-data',
], [
	['input', null, [
		'type'      => 'file',
		'name'      => 'upload[]',
		'accept'    => join(',', array_keys(Core\Image::EXTS)),
		'required'  => null,
		'autofocus' => null,
	]],
	['br'],
	['button', 'Upload', ['type' => 'submit']]
]);
exit($dom);
