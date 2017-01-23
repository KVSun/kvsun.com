<?php
namespace KVSun\Components\Nav;

const ATTRS = array(
	'class' => 'cat-link',
	'role' => 'button',
);

return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	$nav = $dom->body->append('nav');
	$nav->class = 'flex sticky';
	$nav->append('a', 'Home', array_merge(ATTRS, ['href' => \KVSun\DOMAIN]));

	$categories = $page->getCategories();
	$pages = $pdo('SELECT `name`, `url` FROM `pages`');

	foreach(array_merge($categories, $pages) as $cat) {
		$nav->append('a', $cat->name, array_merge(ATTRS, ['href' => \KVSun\DOMAIN . $cat->url]));
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
