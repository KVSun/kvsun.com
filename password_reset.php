<?php
namespace KVSun\PasswordReset;

use \shgysk8zer0\Core\{PDO, FormData, URL, Headers, Console};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};
use \shgysk8zer0\PHPCrypt\{PublicKey, FormSign};
use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Login\{User};
use const \KVSun\Consts\{
	PUBLIC_KEY,
	PRIVATE_KEY,
	PASSWD,
	DOMAIN,
	DB_CREDS,
	DATETIME_FORMAT,
	PASSWORD_RESET_VALID
};

$req = new FormData($_GET);
if (isset(
	$req->user,
	$req->time,
	$req->token
) and filter_var($req->time, FILTER_VALIDATE_INT)) {
	$time = new \DateTime();
	$time->setTimestamp($req->time);
	if (request_expired($time)) {
		http_response_code(HTTP::BAD_REQUEST);
	} elseif (! verify_sig($req->user, $time, $req->token)) {
		http_response_code(HTTP::UNAUTHORIZED);
	} elseif ($user = User::search(DB_CREDS, $req->user) and isset($user->username)) {
		exit(build_form($user));
	} else {
		http_response_code(HTTP::BAD_REQUEST);
	}
} else {
	http_response_code(HTTP::BAD_REQUEST);
}

function verify_sig(String $username, \DateTime $time, String $sig): Bool
{
	return true;
	$key = PublicKey::importFromFile(PUBLIC_KEY);
	$json = json_encode([
		'user' => $username,
		'time' => $time->format(DATETIME_FORMAT),
	]);
	return $key->verify($json, str_replace(' ', '+', $sig));
}

function request_expired(\DateTime $time): Bool
{
	$now = new \DateTime();
	$expires = clone($time);
	$expires->modify(PASSWORD_RESET_VALID);
	return $time > $now or $now > $expires;
}

function build_form(User $user): HTML
{
	$dom = new HTML();
	$signer = new FormSign(PUBLIC_KEY, PRIVATE_KEY, PASSWD);
	$dom->head->append('title', 'Password reset');
	$form = $dom->body->append('form', null, [
		'name' => 'password_change',
		'action' => DOMAIN . basename(__FILE__),
		'method' => 'post'
	]);
	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', "Password reset for {$user->name}");
	$label = $fieldset->append('label', 'New password');
	$input = $fieldset->append('input', null, [
		'type' => 'password',
		'id' => "{$form->name}-password",
		'name' => "{$form->name}[password]",
		'autocomplete' => 'new-password',
		'placeholder' => '********',
		'autofocus' => '',
		'required' => '',
	]);
	$label->for = $input->id;
	$fieldset->append('br');
	$label = $fieldset->append('label', 'Repeat password');
	$input = $fieldset->append('input', null, [
		'type' => 'repeat',
		'id' => "{$form->name}-repeat",
		'name' => "{$form->name}[repeat]",
		'autocomplete' => 'new-password',
		'placeholder' => '********',
		'required' => '',
	]);
	$label->for = $input->id;
	$fieldset->append('hr');
	$fieldset->append('button', 'Submit', [
		'type' => 'submit',
	]);
	$signer->signForm($form);

	return $dom;
}
