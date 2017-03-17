<?php
namespace KVSun\Components\Nav;

use function \KVSun\Functions\{use_icon, restore_login, add_e_edition};

use const \KVSun\Consts\{DOMAIN, ICONS};

use \shgysk8zer0\DOM\{HTML, HTMLElement};
use \shgysk8zer0\Core\{PDO, Gravatar, URL};
use \KVsun\KVSAPI\{Abstracts\Content as KVSAPI};

const ATTRS = array(
	'class' => 'cat-link',
	'role'  => 'button',
);

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$nav = $dom->body->append('nav', null, [
		'role'  => 'navigation',
		'class' => 'flex sticky',
		'id'    => 'main-nav',
	]);
	$home = $nav->append('a', null, array_merge(ATTRS, [
		'href'  => DOMAIN,
		'rel'   => 'bookmark',
		'title' => 'Home',
	]));
	use_icon('home', $home, [
		'class' => 'icon'
	]);

	$pages = $pdo('SELECT `name`, `url`, `icon` FROM `pages`');
	$categories = $pdo('SELECT `name`, `icon`, `url-name` AS `url` FROM `categories`');

	foreach($categories as $cat) {
		if (isset($cat->icon)) {
			$add = $nav->append('a', null, array_merge(ATTRS, [
				'href'  => DOMAIN . $cat->url,
				'title' => $cat->name,
			]));
			$add->append('span', "{$cat->name}&nbsp;", ['class' => 'desktop-only']);
			use_icon($cat->icon, $add, [
				'class' => 'icon',
			]);
		} else {
			$nav->append('a', $cat->name, array_merge(ATTRS, [
				'href' => DOMAIN . $cat->url,
			]));
		}
	}

	foreach($pages as $page) {
		if (isset($page->icon)) {
			$url = new URL($page->url);
			$add = $nav->append('a', null, array_merge(ATTRS, [
				'href'  => $url,
				'title' => $page->name,
			]));
			$add->append('span', "{$page->name}&nbsp;", ['class' => 'desktop-only']);
			use_icon($page->icon, $add, [
				'class' => 'icon',
			]);
			// if ($url->host !== $_SERVER['HTTP_HOST']) {
			// 	$add->target = '_blank';
			// }
		} else {
			$nav->append('a', $page->name, array_merge(ATTRS, [
				'href' => DOMAIN . $page->url,
			]));
		}
	}

	$user = restore_login();

	if ($user->hasPermission('eEdition')) {
		add_e_edition($nav, ATTRS);
	}

	$avatar = $nav->append('img', null, [
		'id'     => 'user-avatar',
		'width'  => 64,
		'height' => 64,
		'class'  => 'round',
		'role'   => 'button',
	]);

	if (isset($user->email)) {
		$avatar->src = new Gravatar($user->email);
		$avatar->data_load_form = 'update-user';
	} else {
		$avatar->src = DOMAIN . ICONS['sign-in'];
		$avatar->data_show_modal = '#login-dialog';
	}
};
