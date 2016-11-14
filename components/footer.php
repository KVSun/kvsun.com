<?php
namespace KVSun\Components\Footer;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$footer = $dom->body->append('footer');
	$contact = \KVSun\load('contact-dialog');
	$footer->append('button', 'Contact-us', [
		'data-show-modal' => "#{$contact[0]->id}",
		'type' => 'button'
	]);
};
