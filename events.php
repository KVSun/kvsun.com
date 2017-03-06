<?php
namespace KVSun\Events;

use \shgysk8zer0\Core\{
	Console,
	PDO,
	Listener,
	FormData,
	Gravatar,
	Timer,
	JSON_Response as Resp
};

use \shgysk8zer0\Login\{User};

use \shgysk8zer0\DOM\{
	HTML,
	HTMLElement,
	RSS
};

use \shgysk8zer0\Authorize\{
	ShippingAddress
};

use const \KVSun\Consts\{
	ICONS,
	DOMAIN,
	PASSWD,
	LOGGED_IN_ONLY,
	LOGGED_OUT_ONLY,
	DEBUG,
	ERROR_LOG
};

use function \KVSun\Functions\{
	user_can,
	email,
	html_email
};

/**
 * Responds to login events
 * @param  User    $user     The user that just logged in
 * @param  boolean $remember Whether or not to set the login cookie
 * @return Resp              JSON_Response instance with notification, etc.
 */
function login_handler(User $user, Bool $remember = true): Resp
{
	try {
		if ($remember) {
			$user->setCookie('user', PASSWD);
		}
		$resp = Resp::getInstance();
		$grav = new Gravatar($user->email, 64);
		$user->setSession('user');
		$resp->notify('Login Successful', "Welcome back, {$user->name}", "{$grav}");
		$resp->close('#login-dialog');
		$resp->clear('login');
		$resp->enable(join(', ', LOGGED_IN_ONLY));
		$resp->disable(join(', ', LOGGED_OUT_ONLY));
		$resp->attributes('#user-avatar', 'src', "$grav");
		if (user_can('createPosts', 'editPosts')) {
			$resp->attributes('main', 'contextmenu', 'admin_menu');
		}
		//$avatar->data_load_form = 'update-user';
		$resp->attributes('#user-avatar', 'data-load-form', 'update-user');
		$resp->attributes('#user-avatar', 'data-show-modal', false);
	} catch (\Throwable $e) {
		trigger_error($e);
	} finally {
		return $resp;
	}
}
/**
 * Handles logout events
 * @param  User $user The user logging out
 * @return Resp       JSON_Response instance with notification, etc.
 */
function logout_handler(User $user): Resp
{
	try {
		$user->logout();
		$resp = Resp::getInstance();
		$resp->notify('Success', 'You have been logged out.', DOMAIN . ICONS['sign-out']);
		$resp->close('dialog[open]');
		$resp->remove('#update-user-dialog, #admin_menu');
		$resp->attributes('#user-avatar', 'src', DOMAIN . ICONS['sign-in']);
		$resp->attributes('#user-avatar', 'data-load-form', false);
		$resp->attributes('#user-avatar', 'data-show-modal', '#login-dialog');
		$resp->attributes('[contextmenu="admin_menu"]', 'contextmenu', false);
		$resp->enable(join(', ', LOGGED_OUT_ONLY));
		$resp->disable(join(', ', LOGGED_IN_ONLY));
	} catch (\Throwable $e) {
		trigger_error($e);
	} finally {
		return $resp;
	}
}
/**
 * Handles error events
 * @param  Int    $severity E_* error level
 * @param  String $message  Error message
 * @param  String $file     File the error occured in
 * @param  Int    $line     Line the error occured on
 * @return Bool             True to prevent default error handling
 */
function error_handler(Int $severity, String $message, String $file, Int $line): Bool
{
	$err = new \ErrorException($message, 0, $severity, $file, $line);
	error_log($err . PHP_EOL, 3,ERROR_LOG);
	return true;
}

/**
 * Handles exception events
 * @param  Throwable $e Exception or Error
 * @return void
 */
function exception_handler(\Throwable $e)
{
	error_log($e . PHP_EOL, 3, ERROR_LOG);
}

/**
 * Handles error events with debugging info for developers
 * @param  Int    $severity E_* error level
 * @param  String $message  Error message
 * @param  String $file     File the error occured in
 * @param  Int    $line     Line the error occured on
 * @return Bool             True to prevent default error handling
 */
function dev_error_handler(Int $severity, String $message, String $file, Int $line): Bool
{
	Console::error([
		'message' => $message,
		'file'    => $file,
		'line'    => $line,
		'trace'   => debug_backtrace(),
	]);
	return true;
}

/**
 * Handles exception events with debugging for developers
 * @param  Throwable $e Exception or Error
 * @return void
 */
function dev_exception_handler(\Throwable $e)
{
	Console::error([
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'trace'   => $e->getTrace(),
	]);
}

/**
 * Event triggered to notify circulation of a new print subscription by email
 * @param  User            $user    User that made the transaction
 * @param  stdClass        $sub     Subscription info from database
 * @param  ShippingAddress $address Address entered in form
 * @return Bool                     Whether or not the email was sent
 */
function print_subscription(User $user, \stdClass $sub, ShippingAddress $address): Bool
{
	$to      = ['circulation@kvsun.com'];
	$subject = 'New print subscription made online';
	$headers = ['From' => 'notifications@kvsun.com'];
	$message = new HTML();
	$expires = new \DateTime($sub->term);

	$body = $message->body;
	$body->append('h3', "New {$sub->name} subscription for {$user->name}");
	$info = $body->append('ul');
	$info->append('li', "Subscriber: {$address->first_name} {$address->last_name}");
	$info->append('li', "Expires: {$expires->format('D. M j, Y')}");
	$info->append('li', "Address: {$address->address}, {$address->city}, {$address->state} {$address->zip}");

	return html_email($to, $subject, $message, $headers);
}

new Listener('error', __NAMESPACE__ . '\error_handler');

new Listener('exception', __NAMESPACE__ . '\exception_handler');

new Listener('login', __NAMESPACE__ . '\login_handler');

new Listener('logout', __NAMESPACE__ . '\logout_handler');

new Listener('printSubscription', __NAMESPACE__ . '\print_subscription');

if (user_can('debug') or DEBUG) {
	$timer = new Timer();
	new Listener('error', __NAMESPACE__ . '\dev_error_handler');

	new Console('exception', __NAMESPACE__ . '\dev_exception_handler');

	new Listener('load', function() use ($timer)
	{
		Console::info([
			'system' => [
				'time'   => "{$timer} s",
				'memory' => (memory_get_peak_usage(true) / 1024) . ' kb',
			],
			'resources' => [
				'files'          => get_included_files(),
				'path'           => explode(PATH_SEPARATOR, get_include_path()),
				'functions'      => get_defined_functions(true)['user'],
				'constansts'     => get_defined_constants(true)['user'],
				'classes' => [
					'classes'    => get_declared_classes(),
					'interfaces' => get_declared_interfaces(),
					'traits'     => get_declared_traits(),
				],
			],
			'globals' => [
				'_SERVER'  => $_SERVER,
				'_REQUEST' => $_REQUEST,
				'_SESSION' => $_SESSION ?? [],
				'_COOKIE'  => $_COOKIE,
			],
		])->sendLogHeader();
	});
	unset($timer);
}
