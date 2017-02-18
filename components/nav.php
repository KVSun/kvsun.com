<?php
namespace KVSun\Components\Nav;

use function \KVSun\use_icon;
const ATTRS = array(
	'class' => 'cat-link',
	'role' => 'button',
);

return function (
	\shgysk8zer0\DOM\HTML $dom,
	\shgysk8zer0\Core\PDO $pdo,
	\KVSun\KVSAPI\Abstracts\Content $kvs
)
{
	$nav = $dom->body->append('nav', null, [
		'role' => 'navigation',
	]);
	$nav->class = 'flex sticky';
	$home = $nav->append('a', null, array_merge(ATTRS, [
		'href'  => \KVSun\DOMAIN,
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
				'href'  => \KVSun\DOMAIN . $cat->url,
				'title' => $cat->name,
			]));
			use_icon($cat->icon, $add, [
				'class' => 'icon',
			]);
		} else {
			$nav->append('a', $cat->name, array_merge(ATTRS, ['href' => \KVSun\DOMAIN . $cat->url]));
		}
	}
	$avatar = $nav->append('img', null, [
		'id' => 'user-avatar',
		'src' => \KVSun\DOMAIN . '/images/octicons/lib/svg/sign-in.svg',
		'width' => 64,
		'height' => 64,
		'class' => 'round',
		'role' => 'button',
	]);
	$user = \KVSun\restore_login();
	if (isset($user->email)) {
		$grav = new \shgysk8zer0\Core\Gravatar($user->email);
		$avatar->src = $grav;
		$avatar->data_load_form = 'update-user';
	} else {
		$avatar->data_show_modal = '#login-dialog';
	}
};
