<?php
namespace KVSun\Components\Header;

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Abstracts\Content as KVSAPI};
use function \KVSun\Functions\{use_icon};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$header = $dom->body->append('header')->append('h1', $kvs->getHead()->title, [
		'class' => 'site-title',
		'role'  => 'banner',
	]);
	$mobile_toggle = $header->append('button', null, [
		'data-toggle-class' => '{"#main-nav": "menu-open"}',
		'class'             => 'mobile-only',
		'id'                => 'mobile-menu-btn',
	]);
	use_icon('hamburger', $mobile_toggle, [
		'class' => 'icon'
	]);
};
