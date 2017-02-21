<?php
namespace KVSun\Components\Nav;

use function \KVSun\Functions\{use_icon, restore_login};

use const \KVSun\Consts\{DOMAIN};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO, Gravatar};
use \KVsun\KVSAPI\{Abstracts\Content as KVSAPI};

const ATTRS = array(
	'class' => 'cat-link',
	'role' => 'button',
);

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$nav = $dom->body->append('nav', null, [
		'role' => 'navigation',
	]);
	$nav->class = 'flex sticky';
	$home = $nav->append('a', null, array_merge(ATTRS, [
		'href'  => DOMAIN,
		'rel'   => 'bookmark',
		'title' => 'Home'
	]));
	use_icon('home', $home, [
		'class' => 'icon'
	]);

	$pages = $pdo('SELECT `name`, `url`, `icon` FROM `pages`');
	$categories = $pdo('SELECT `name`, `icon`, `url-name` AS `url` FROM `categories`');
	foreach(array_merge($categories, $pages) as $cat) {
		if (isset($cat->icon)) {
			$add = $nav->append('a', null, array_merge(ATTRS, [
				'href'  => DOMAIN . $cat->url,
				'title' => $cat->name,
			]));
			use_icon($cat->icon, $add, [
				'class' => 'icon',
			]);
		} else {
			$nav->append('a', $cat->name, array_merge(ATTRS, [
				'href' => DOMAIN . $cat->url,
			]));
		}
	}
	$avatar = $nav->append('img', null, [
		'id'     => 'user-avatar',
		'width'  => 64,
		'height' => 64,
		'class'  => 'round',
		'role'   => 'button',
	]);
	$user = restore_login();
	if (isset($user->email)) {
		$avatar->src = new Gravatar($user->email);
		$avatar->data_load_form = 'update-user';
	} else {
		$avatar->src = DOMAIN . '/images/octicons/lib/svg/sign-in.svg';
		$avatar->data_show_modal = '#login-dialog';
	}
};
