<?php
namespace KVSun;

use \shgysk8zer0\Core\{Console, Listener, Gravatar, Timer, JSON_Response as Resp};
use \shgysk8zer0\DOM;
use \shgysk8zer0\Login\User;

function login_handler(User $user, Bool $remember = true): Resp
{
	try {
		if ($remember) {
			$user->setCookie('user', \KVSun\PASSWD);
		}
		$resp = Resp::getInstance();
		$grav = new Gravatar($user->email, 64);
		$user->setSession('user');
		$resp->notify('Login Successful', "Welcome back, {$user->name}", "{$grav}");
		$resp->close('#login-dialog');
		$resp->clear('login');
		$resp->enable(join(', ', \KVSun\LOGGED_IN_ONLY));
		$resp->disable(join(', ', \KVSun\LOGGED_OUT_ONLY));
		$resp->attributes('#user-avatar', 'src', "$grav");
		if (\KVSun\check_role('editor')) {
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

function logout_handler(User $user): Resp
{
	try {
		$user->logout();
		$resp = Resp::getInstance();
		$resp->notify('Success', 'You have been logged out.');
		$resp->close('dialog[open]');
		$resp->remove('#update-user-dialog, #admin_menu');
		$resp->attributes('#user-avatar', 'src', '/images/octicons/lib/svg/sign-in.svg');
		$resp->attributes('#user-avatar', 'data-load-form', false);
		$resp->attributes('#user-avatar', 'data-show-modal', '#login-dialog');
		$resp->attributes('[contextmenu="admin_menu"]', 'contextmenu', false);
		$resp->enable(join(', ', LOGGED_OUT_ONLY));
		$resp->disable(join(', ', LOGGED_IN_ONLY));
		$resp->send();
	} catch (\Throwable $e) {
		trigger_error($e);
	} finally {
		return $resp;
	}
}

new Listener('error', function(Int $severity, String $message, String $file, Int $line): Bool
{
	$err = new \ErrorException($message, 0, $severity, $file, $line);
	error_log($err . PHP_EOL, 3,ERROR_LOG);
	return true;
});

new Listener('exception', function(\Throwable $e)
{
	error_log($e . PHP_EOL, 3, ERROR_LOG);
});

new Listener('login', __NAMESPACE__ . '\login_handler');

new Listener('logout', __NAMESPACE__ . '\logout_handler');

if (check_role('admin') or DEBUG) {
	$timer = new Timer();
	new Listener('error', function(Int $severity, String $message, String $file, Int $line): Bool
	{
		Console::error([
			'message' => $message,
			'file'    => $file,
			'line'    => $line,
			'trace'   => debug_backtrace(),
		]);
		return true;
	});

	new Console('exception', function(\Throwable $e)
	{
		Console::error([
			'message' => $e->getMessage(),
			'file'    => $e->getFile(),
			'line'    => $e->getLine(),
			'trace'   => $e->getTrace(),
		]);
	});

	new Listener('load', function() use ($timer)
	{
		Console::log(get_included_files());
		Console::info("Loaded in $timer seconds.")->sendLogHeader();
	});
	unset($timer);
}
