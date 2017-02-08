<?php
namespace KVSun\Components\Footer;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$footer = $dom->body->append('footer');
	$package = json_decode(file_get_contents('package.json'));

	$login = $footer->append('dialog', null, [
		'id'   => 'login-dialog',
		'role' => 'contentinfo',
	]);

	$login->append('button', null, [
		'type' => 'button',
		'data-close' => "#{$login->id}",
	]);

	$register = $footer->append('dialog', null, [
		'id' => 'registration-dialog'
	]);
	$register->append('button', null, [
		'type' => 'button',
		'data-close' => "#{$register->id}",
	]);
	$register->append('br');

	$register->importHTMLFile('components/register.html');
	$login->importHTMLFile('components/forms/login.html');

	$register->getElementById('registration-form')->action = \KVSun\DOMAIN . 'api.php';
	$login->append('br');
	$login->getElementById('login-form')->action = \KVSun\DOMAIN . 'api.php';


	\KVSun\use_icon('sign-in', $footer->append('span', null, [
		'data-show-modal' => "#{$login->id}",
		'class' => 'logo',
		'title' => 'Sign in',
	]));

	\KVSun\use_icon('server', $footer->append('span', null, [
		'data-show-modal' => "#{$register->id}",
		'class' => 'logo',
	]));

	\KVSun\use_icon('credit-card', $footer->append('span', null, [
		'data-load-form' => 'ccform',
		'class' => 'logo',
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

	$footer->append('hr');

	\KVSun\use_icon('twitter', $footer->append('a', null, [
		'href' => 'https://twitter.com/kvsun',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Follow us on Twitter',
	]));

	\KVSun\use_icon('facebook', $footer->append('a', null, [
		'href' => 'https://www.facebook.com/KernValleySun',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Follow us on Facebook',
	]));

	\KVSun\use_icon('youtube', $footer->append('a', null, [
		'href' => 'https://www.youtube.com/user/kernvalleysun1959',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Subscribe to our YouTube channel',
	]));
};
