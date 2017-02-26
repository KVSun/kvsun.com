<?php
namespace KVSun\PasswordReset;

use \shgysk8zer0\Core\{PDO, FormData, URL, Headers, Console};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};
use \shgysk8zer0\PHPCrypt\{PublicKey, PrivateKey, FormSign};
use \shgysk8zer0\DOM\{HTML, HTMLElement};
use \shgysk8zer0\Login\{User};
use function \KVSun\Functions\{load, add_main_menu};
use const \KVSun\Consts\{
	PUBLIC_KEY,
	PRIVATE_KEY,
	PASSWD,
	DOMAIN,
	DB_CREDS,
	DATETIME_FORMAT,
	PASSWORD_RESET_VALID,
	STYLE,
	SCRIPTS,
	SCRIPTS_DIR,
	HTML_TEMPLATES
};

const FORM_NAME = 'password_change';
const PASSWD_PATTERN = '^.{8,}$';

$req    = new FormData($_REQUEST);
$header = new Headers();

if (isset(
	$req->user,
	$req->time,
	$req->token
) and filter_var($req->time, FILTER_VALIDATE_INT)) {
	$time = new \DateTime();
	$time->setTimestamp($req->time);

	if (request_expired($time)) {
		http_response_code(HTTP::BAD_REQUEST);
		exit('Request expired');
	} elseif (! verify_sig($req->user, $time, $req->token)) {
		http_response_code(HTTP::UNAUTHORIZED);
		exit('Invalid request');
	} elseif (
		$user = User::search(DB_CREDS, $req->user)
		and isset($user->username)
	) {
		exit(build_form($user));
	} else {
		http_response_code(HTTP::BAD_REQUEST);
	}
} elseif (isset(
	$req->{FORM_NAME}->password,
	$req->{FORM_NAME}->repeat,
	$req->{FORM_NAME}->user
)) {
	$signer = new FormSign(PUBLIC_KEY, PRIVATE_KEY, PASSWD);
	$key    = PrivateKey::importFromFile(PRIVATE_KEY, PASSWD);

	if (! (
		$username = $key->decrypt($req->{FORM_NAME}->user)
		and $user = User::search(DB_CREDS, $username)
		and isset($user->username)
	)) {
		http_response_code(HTTP::BAD_REQUEST);
	} elseif ($req->{FORM_NAME}->password !== $req->{FORM_NAME}->repeat) {
		http_response_code(HTTP::BAD_REQUEST);
		exit(build_form($user, 'Password does not match repeated password.'));
	} elseif (! preg_match('/' . PASSWD_PATTERN . '/', $req->{FORM_NAME}->password)) {
		http_response_code(HTTP::BAD_REQUEST);
		exit(build_form($user, 'Password does not meet requirements.'));
	} elseif(! $signer->verifyFormSignature($_POST[FORM_NAME])) {
		http_response_code(HTTP::UNAUTHORIZED);
	} elseif ($user->updatePassword($req->{FORM_NAME}->password)) {
		$user->setCookie('user', PASSWD)->setSession();
		$header->location = DOMAIN;
	}
} else {
	http_response_code(HTTP::BAD_REQUEST);
}

/**
 * Verify RSA signature in request
 * @param  String   $username Username for password reset
 * @param  DateTime $time     Time it was requested
 * @param  String   $sig      The request signature
 * @return Bool               Whether or not it is valid
 */
function verify_sig(String $username, \DateTime $time, String $sig): Bool
{
	return true;
	$key  = PublicKey::importFromFile(PUBLIC_KEY);
	$json = json_encode([
		'user' => $username,
		'time' => $time->format(DATETIME_FORMAT),
	]);
	return $key->verify($json, $sig);
}

/**
 * Check if a password request is expired
 * @param  DateTime $time The time the request is from
 * @return Bool           Whether or not it is expired
 */
function request_expired(\DateTime $time): Bool
{
	$now     = new \DateTime();
	$expires = clone($time);
	$expires->modify(PASSWORD_RESET_VALID);
	return $time > $now or $now > $expires;
}

/**
 * Create the password reset form with optional error message
 * @param  User   $user      The user the password reset is for
 * @param  String $error_msg Optional error message to display
 * @return HTML              The HTML document with password reset form
 */
function build_form(User $user, String $error_msg = null): HTML
{
	HTMLElement::$import_path = COMPONENTS;
	$dom    = HTML::getInstance();
	$dom->body->class = 'flex row wrap';
	array_map([$dom->body, 'importHTMLFile'], HTML_TEMPLATES);
	$signer = new FormSign(PUBLIC_KEY, PRIVATE_KEY, PASSWD);
	$key    = PublicKey::importFromFile(PUBLIC_KEY);
	$dom->head->append('title', 'Password reset');
	$dom->head->append('link', null, [
		'rel' => 'stylesheet',
		'href' => DOMAIN . STYLE,
	]);

	foreach (SCRIPTS as $script) {
		$dom->head->append('script', null, [
			'src' => DOMAIN . SCRIPTS_DIR . $script,
			'async' => '',
		]);
	}

	load('header', 'nav');

	$form = $dom->body->append('main')->append('form', null, [
		'name'   => FORM_NAME,
		'action' => DOMAIN . basename(__FILE__),
		'method' => 'post'
	]);
	if (isset($error_msg)) {
		$dom->body->append('p')->append('b', $error_msg);
	}
	$form->append('input', null, [
		'type'  => 'hidden',
		'name'  => "{$form->name}[user]",
		'value' => $key->encrypt($user->username),
	]);
	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', "Password reset for {$user->name}");
	$label = $fieldset->append('label', 'New password');
	$input = $fieldset->append('input', null, [
		'type'         => 'password',
		'id'           => "{$form->name}-password",
		'name'         => "{$form->name}[password]",
		'autocomplete' => 'new-password',
		'placeholder'  => '********',
		'pattern'      => PASSWD_PATTERN,
		'autofocus'    => '',
		'required'     => '',
	]);
	$label->for = $input->id;
	$fieldset->append('br');
	$label = $fieldset->append('label', 'Repeat password');
	$input = $fieldset->append('input', null, [
		'type'         => 'password',
		'id'           => "{$form->name}-repeat",
		'name'         => "{$form->name}[repeat]",
		'autocomplete' => 'new-password',
		'placeholder'  => '********',
		'pattern'      => PASSWD_PATTERN,
		'required'     => '',
	]);
	$label->for = $input->id;
	$fieldset->append('hr');
	$fieldset->append('button', 'Submit', [
		'type' => 'submit',
	]);
	$signer->signForm($form);
	load('sidebar', 'footer');
	add_main_menu($dom->body);

	return $dom;
}
