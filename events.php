<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

set_exception_handler('\shgysk8zer0\Core\Listener::exception');
set_error_handler('\shgysk8zer0\Core\Listener::error');

new Core\Listener('error', function($severity, $message, $file, $line)
{
	file_put_contents(
		'errors.log',
		"Error: '{$message}' in {$file}:{$line}" . PHP_EOL,
		FILE_APPEND |  LOCK_EX
	);
});

new Core\Listener('exception', function(\Exception $e)
{
	file_put_contents('exceptions.log', $e . PHP_EOL, FILE_APPEND | LOCK_EX);
});

if (check_role('admin') or DEBUG) {
	$timer = new Core\Timer();
	new Core\Listener('load', function() use ($timer)
	{
		Core\Console::getInstance()->info("Loaded in $timer seconds.");
		Core\Console::getInstance()->sendLogHeader();
	});
	new Core\Listener('error', [Core\Console::getInstance(), 'error']);
	new Core\Listener('exception', [Core\Console::getInstance(), 'error']);
	unset($timer);
}
