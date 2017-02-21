<?php
namespace KVSun\Components\ContactDialog;

use \shgysk8zer0\DOM\HTML;
use \shgysk8zer0\Core\{PDO, Gravatar};

return function (HTML $dom, PDO $pdo)
{
	$dialog = $dom->body->append('dialog');
	$dialog->id = basename(__FILE__, '.php');
	$dialog->append('button', null, [
		'data-close' => "#{$dialog->id}",
		'type'       => 'button',
	]);

	$users = $pdo('SELECT `user` as `email`, `name`, `position`, `tel` FROM `users`');
	foreach ($users as $user) {
		$container = $dialog->append('div');
		$container->append('img', null, [
			'src'    => new Gravatar($user->email),
			'height' => 80,
			'width'  => 80,
			'alt'    => $user->name,
		]);
		$container->append('span', $user->name);
		$container->append('b', $user->position);
		$container->append('br');
		$container->append('a', "<{$user->email}>", [
			'href'  => "mailto:{$user->email}",
			'title' => "Send email to {$user->name} | {$user->position}",
		]);
		$container->append('br');
		$container->append('a', $user->tel, [
			'href' => "tel:{$user->tel}",
		]);
		$container->append('hr');
	}
	return $dialog;
};
