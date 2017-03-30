<?php
namespace KVSun\Components\Footer;

use const \KVSun\Consts\{DOMAIN, DEBUG};
use function \KVSun\Functions\{use_icon, make_dialog};

use \shgysk8zer0\DOM\HTML;
use \shgysk8zer0\Core\PDO;
use \KVsun\KVSAPI\Abstracts\Content as KVSAPI;

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	$footer = $dom->body->append('footer');
	$package = json_decode(file_get_contents('package.json'));

	$login = make_dialog('login-dialog', $footer);
	$register = make_dialog('registration-dialog', $footer);

	$register->importHTMLFile('components/forms/register.html');
	$login->importHTMLFile('components/forms/login.html');

	$register->getElementById('registration-form')->action = DOMAIN . 'api.php';
	$login->append('br');
	$login->getElementById('login-form')->action = DOMAIN . 'api.php';

	if (DEBUG) {
		use_icon('sign-in', $footer->append('span', null, [
			'data-show-modal' => "#{$login->id}",
			'class' => 'logo',
			'title' => 'Sign in',
		]));

		use_icon('server', $footer->append('span', null, [
			'data-show-modal' => "#{$register->id}",
			'class' => 'logo',
		]));

		use_icon('credit-card', $footer->append('span', null, [
			'data-load-form' => 'ccform',
			'class' => 'logo',
		]));

		use_icon('mark-github', $footer->append('a', null, [
			'href' => $package->repository->url,
			'target' => '_blank',
			'class' => 'logo',
		]));
		use_icon('issue-opened', $footer->append('a', null, [
			'href' => $package->bugs->url,
			'target' => '_blank',
			'class' => 'logo',
		]));

		$footer->append('hr');
	}

	use_icon('twitter', $footer->append('a', null, [
		'href' => 'https://twitter.com/kvsun',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Follow us on Twitter',
	]));

	use_icon('facebook', $footer->append('a', null, [
		'href' => 'https://www.facebook.com/KernValleySun',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Follow us on Facebook',
	]));

	use_icon('youtube', $footer->append('a', null, [
		'href' => 'https://www.youtube.com/user/kernvalleysun1959',
		'target' => '_blank',
		'class' => 'logo',
		'title' => 'Subscribe to our YouTube channel',
	]));
	$footer->append('a', 'Contact us', [
		'href' => DOMAIN . 'contacting-us',
	]);
	$footer->append('b', sprintf('&copy;&nbsp;%d&nbsp;%s', date('Y'), $kvs->getHead()->title));
};
