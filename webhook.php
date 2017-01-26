<?php
namespace WebHook;

const CONFIG = 'config/github.json';
error_reporting(0);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
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
	$webhook = new \shgysk8zer0\Core\GitHubWebhook(CONFIG);
	$email = new \shgysk8zer0\Core\Email(
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
					echo `git pull`;
					$status = `git status`;
					echo $status . PHP_EOL;
					`npm install`;
					$email->message = $status;
					$email->send();
				}
				break;

			default:
				file_put_contents(__DIR__ . $webhook->event . '_' . date('Y-m-d\TH:i:s') . '.json', json_encode($webhook->parsed, JSON_PRETTY_PRINT));
				throw new \Exception("Unhandled event: {$webhook->event}", 501);
		}
	} else {
		throw new \Exception('Authorization required', 401);
	}
} catch(\Exception $e) {
	handler_exception($e);
}
