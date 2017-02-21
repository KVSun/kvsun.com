<?php
namespace KVSun\Components\InstallForm;

use \shgysk8zer0\{Core};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as Status};
use \shgysk8zer0\{DOM};

use const \KVSun\Consts\{DOMAIN, DEBUG, DB_CREDS, STYLE, DEV_STYLE, SCRIPTS_DIR, SCRIPTS};

$dom = DOM\HTML::getInstance();
if (@file_exists(DB_CREDS)) {
	http_response_code(Status::FORBIDDEN);
	exit('Already installed.');
}

$dom->head->append('title', "Installer for {$_SERVER['SERVER_NAME']}");
$dom->head->append('link', null, [
	'rel' => 'stylesheet',
	'type' => 'text/css',
	'href' => DEBUG ? DOMAIN . DEV_STYLE : DOMAIN . STYLE,
]);
foreach(SCRIPTS as $script) {
	$dom->head->append('script', null, [
		'src' => DOMAIN . SCRIPTS_DIR . $script,
		'async' => '',
		'type' => 'application/javascript',
	]);
	unset($script);
}
$form = $dom->body->append('form', null, [
	'name' =>   basename(__FILE__, '.php'),
	'action' => DOMAIN . 'api.php',
	'method' => 'POST',
]);
$db_creds = $form->append('fieldset');
$db_creds->append('legend', 'Database Credentials');
$db_creds->append('label', 'DB user', ['for' => "{$form->name}-db-user"]);
$db_creds->append('input', null, [
	'type'        => 'text',
	'name'        => "{$form->name}[db][user]",
	'id'          => "{$form->name}-db-user",
	'placeholder' => 'db user',
	'autofocus'   => '',
	'required'    => '',
]);
$db_creds->append('br');
$db_creds->append('label', 'DB password', ['for' => "{$form->name}-db-password"]);
$db_creds->append('input', null, [
	'type'        => 'password',
	'name'        => "{$form->name}[db][password]",
	'id'          => "{$form->name}-db-password",
	'placeholder' => '********',
	'required'    => '',
]);
$db_creds->append('br');
$db_creds->append('label', 'DB name', ['for' => "{$form->name}-db-name"]);
$db_creds->append('input', null, [
	'type'        => 'text',
	'name'        => "{$form->name}[db][database]",
	'id'          => "{$form->name}-db-name",
	'placeholder' => 'Name of database',
	'required'    => '',
]);
$form->append('button', 'Submit', ['type' => 'submit']);
$form->append('button', 'Reset', ['type' => 'reset']);

return $form;
