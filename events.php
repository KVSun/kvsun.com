<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;
use \shgysk8zer0\Login\User as User;
use \shgysk8zer0\Core\Console as Console;
use \shgysk8zer0\Core\JSON_Response as Resp;

new Core\Listener('error', function(Int $severity, String $message, String $file, Int $line): Bool
{
	$err = new \ErrorException($message, 0, $severity, $file, $line);
	error_log($err . PHP_EOL, 3,ERROR_LOG);
	return true;
});

new Core\Listener('exception', function(\Throwable $e)
{
	error_log($e . PHP_EOL, 3, ERROR_LOG);
});

new Core\Listener('login', function(User $user, Bool $remember = true): Resp
{
	try {
		if ($remember) {
			$user->setCookie('user', \KVSun\PASSWD);
		}
		$resp = Resp::getInstance();
		$grav = new Core\Gravatar($user->email, 64);
		$user->setSession('user');
		$resp->notify('Login Successful', "Welcome back, {$user->name}", "{$grav}");
		$resp->close('#login-dialog');
		$resp->clear('login');
		$resp->enable(join(', ', \KVSun\LOGGED_IN_ONLY));
		$resp->disable(join(', ', \KVSun\LOGGED_OUT_ONLY));
		$resp->attributes('#user-avatar', 'src', "$grav");
		//$avatar->data_load_form = 'update-user';
		$resp->attributes('#user-avatar', 'data-load-form', 'update-user');
		$resp->attributes('#user-avatar', 'data-show-modal', false);
	} catch (\Throwable $e) {
		trigger_error($e);
	} finally {
		return $resp;
	}
});

new Core\Listener('logout', function(User $user): Resp
{
	try {
		$user->logout();
		$resp = Resp::getInstance();
		$resp->notify('Success', 'You have been logged out.');
		$resp->close('dialog[open]');
		$resp->remove('#update-user-dialog');
		$resp->attributes('#user-avatar', 'src', '/images/octicons/lib/svg/sign-in.svg');
		$resp->attributes('#user-avatar', 'data-load-form', false);
		$resp->attributes('#user-avatar', 'data-show-modal', '#login-dialog');
		$resp->enable(join(', ', LOGGED_OUT_ONLY));
		$resp->disable(join(', ', LOGGED_IN_ONLY));
		$resp->send();
	} catch (\Throwable $e) {
		trigger_error($e);
	} finally {
		return $resp;
	}
});

if (check_role('admin') or DEBUG) {
	$timer = new Core\Timer();
	new Core\Listener('error', function(Int $severity, String $message, String $file, Int $line): Bool
	{
		Core\Console::error([
			'message' => $message,
			'file'    => $file,
			'line'    => $line,
			'trace'   => debug_backtrace(),
		]);
		return true;
	});

	new Core\Console('exception', function(\Throwable $e)
	{
		Core\Console::error([
			'message' => $e->getMessage(),
			'file'    => $e->getFile(),
			'line'    => $e->getLine(),
			'trace'   => $e->getTrace(),
		]);
	});

	new Core\Listener('load', function() use ($timer)
	{
		Core\Console::log(get_included_files());
		Core\Console::info("Loaded in $timer seconds.")->sendLogHeader();
	});
	unset($timer);
}
