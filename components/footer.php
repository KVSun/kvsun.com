<?php
namespace KVSun\Components\Footer;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$footer = $dom->body->append('footer');
	$package = json_decode(file_get_contents('package.json'));

	$login = $footer->append('dialog', null, ['id' => 'login-dialog']);

	$login->append('button', null, [
		'type' => 'button',
		'data-close' => "#{$login->id}",
	]);

	$register = $footer->append('dialog', null, ['id' => 'registration-dialog']);
	$register->append('button', null, [
		'type' => 'button',
		'data-close' => "#{$register->id}",
	]);
	$register->append('br');

	\KVSun\append_to_dom('register', $register);
	\KVSun\append_to_dom('forms/login', $login);

	$register->getElementById('registration-form')->action = \KVSun\DOMAIN . 'api.php';
	$login->append('br');
	$login->getElementById('login-form')->action = \KVSun\DOMAIN . 'api.php';

	$footer->append('button', 'Register', [
		'data-show-modal' => "#{$register->id}",
		'type' => 'button'
	]);

	\KVSun\use_icon('sign-in', $footer, ['data-show-modal' => "#{$login->id}"]);
	\KVSun\use_icon('credit-card', $footer->append('button', null, [
		'type' => 'button',
		'data-load-form' => 'ccform'
	]));

	\KVSun\use_icon('mark-github', $footer->append('a', null, [
		'href' => $package->repository->url,
		'target' => '_blank',
		'class' => 'logo',
	]));
	\KVSun\use_icon('issue-opened', $footer->append('a', null, [
		'href' => $package->bugs->url,
		'target' => '_blank',
		'class' => 'logo',
	]));
};
