<?php
namespace KVSun\WebHook;
use \shgysk8zer0\Core\{GitHubWebhook, Email};
const CONFIG = 'config/github.json';
error_reporting(0);
if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}
header('Content-Type: text/plain');

function handler_exception(\Exception $e)
{
	http_response_code($e->getCode());
	exit(sprintf(
		'%s responded with: "%s" on %s:%d' . PHP_EOL,
		$_SERVER['SERVER_NAME'],
		$e->getMessage(),
		trim(preg_replace('/^' . preg_quote(__DIR__, DIRECTORY_SEPARATOR) . '/', null, $e->getFile()), DIRECTORY_SEPARATOR),
		$e->getLine()
	));
}
set_exception_handler(__NAMESPACE__ . '\handler_exception');

echo 'Connection successful.' . PHP_EOL;
try {
	$webhook = new GitHubWebhook(CONFIG);
	$email = new Email(
		$_SERVER['SERVER_ADMIN'],
		sprintf('%s event on %s', $webhook->event, $_SERVER['SERVER_NAME'])
	);

	if ($webhook->validate()) {
		echo 'Request validated.' . PHP_EOL;
		switch(trim(strtolower($webhook->event))) {
			case 'ping':
				echo 'PING' . PHP_EOL;
				break;
			case 'push':
				echo "Push to {$webhook->parsed->ref}" . PHP_EOL;
				if ($webhook->parsed->ref === 'refs/heads/master') {
					set_time_limit(60);
					$pull = `git pull`;
					$status = `git status`;
					echo $pull . PHP_EOL;
					$email->message = $pull . PHP_EOL . PHP_EOL . $status;
					// $email->send();
				}
				break;

			default:
				throw new \Exception("Unhandled event: {$webhook->event}", 501);
		}
	} else {
		throw new \Exception('Authorization required', 401);
	}
} catch(\Exception $e) {
	handler_exception($e);
}
