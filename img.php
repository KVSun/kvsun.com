<?php

namespace KVSun\ImageTest;

use \shgysk8zer0\Core\{Console, Image};
use \shgysk8zer0\DOM\{HTML, HTMLElement};
use \shgysk8zer0\Login\{User};

use const \KVSun\Consts\{COMPONENTS, DEV_STYLE, DB_CREDS, DOMAIN};

use function \KVSun\{load, add_main_menu, make_picture};

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

HTMLElement::$import_path = COMPONENTS;
$dom = HTML::getInstance();
add_main_menu($dom->body);
$dom->body->class = 'flex row wrap';
$dom->head->append('title', __NAMESPACE__);

$dom->head->append('link', null, [
	'rel' => 'stylesheet',
	'type' => 'text/css',
	'href' => DOMAIN . DEV_STYLE,
]);
load('header', 'nav');
$main = $dom->body->append('main', null, [
	'role' => 'main',
]);
if (User::load(DB_CREDS)->hasPermission('uploadMedia')) {
	$main->contextmenu = 'admin_menu';
}
load('sidebar', 'footer');

if (array_key_exists('upload', $_FILES)) {
	$imgs = Image::responsiveImagesFromUpload(
		'upload',
		['images', 'uploads', date('Y'), date('m')],
		[1200, 800, 600, 300],
		['image/webp', 'image/jpeg']
	);
	Console::info($imgs);
	make_picture($imgs, $main, 'Chris Zuber', 'Testing 1 2 3');
}
$form = $main->append('form', null, [
	'action'  => $_SERVER['PHP_SELF'],
	'method'  => 'post',
	'enctype' => 'multipart/form-data',
], [
	['input', null, [
		'type'      => 'file',
		'name'      => 'upload[]',
		'accept'    => join(',', array_keys(Image::EXTS)),
		'required'  => null,
		'autofocus' => null,
	]],
	['br'],
	['button', 'Upload', ['type' => 'submit']]
]);
exit($dom);
