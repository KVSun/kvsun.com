<?php
namespace KVSun\Components\RegistrationForm;

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};

return function(HTML $dom, PDO $pdo)
{
	$user_creds = $form->append('fieldset');
	$user_creds->append('legend', 'User Credentials');
	$user_creds->append('label', 'Login username', [
		'for' => "{$form->name}-login-user",
	]);
	$user_creds->append('input', null, [
		'type'        => 'text',
		'name'        => "{$form->name}[login][user]",
		'id'          => "{$form->name}-login-user",
		'placeholder' => 'username',
		'required'    => '',
	]);
	$user_creds->append('br');
	$user_creds->append('label', 'Email', [
		'for' => "{$form->name}-login-email",
	]);
	$user_creds->append('input', null, [
		'type'        => 'email',
		'name'        => "{$form->name}[login][email]",
		'id'          => "{$form->name}-login-email",
		'placeholder' => 'user@example.com',
		'required'    => '',
	]);
	$user_creds->append('br');
	$user_creds->append('label', 'Login password', [
		'for' => "{$form->name}-login-pass",
	]);
	$user_creds->append('input', null, [
		'type'        => 'password',
		'name'        => "{$form->name}[login][pass]",
		'id'          => "{$form->name}-login-pass",
		'placeholder' => '********',
		'required'    => '',
	]);
	$form->append('button', 'Submit', ['type' => 'submit']);
	$form->append('button', 'Reset', ['type' => 'reset']);
	return $fom;
};
