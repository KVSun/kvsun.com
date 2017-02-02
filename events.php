<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

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
