<?php
namespace KVSun\Components\Footer;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$footer = $dom->body->append('footer');
	$contact = \KVSun\load('contact-dialog');
	\shgysk8zer0\Core\Console::getInstance()->log($contact);
	$footer->append('button', 'Contact-us', [
		'data-show-modal' => "#{$contact[0]}",
		'type' => 'button'
	]);
};
