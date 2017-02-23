<?php
namespace KVSun\Mail;

use \shgysk8zer0\PHPCrypt\{PublicKey};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};

use const \KVSun\Consts\{PUBLIC_KEY, ERROR_LOG};

try {
	$email = new \ArrayObject($_POST, \ArrayObject::ARRAY_AS_PROPS);
	if (isset(
		$email,
		$email->to,
		$email->subject,
		$email->message,
		$email->headers,
		$email->params,
		$email->sent,
		$email->sig
	)) {
		$public = PublicKey::importFromFile(PUBLIC_KEY);

		if ($public->verify(json_encode([
			'to'      => $email->to,
			'subject' => $email->subject,
			'message' => $email->message,
			'headers' => $email->headers,
			'params'  => $email->params,
			'sent'    => $email->sent,
		]), $email->sig)) {
			$sent = strtotime($email->sent);
			if ($sent > strtotime('+5 seconds') or $sent < strtotime('-5 seconds')) {
				throw new \Exception('Valid signature but time window is invalid', HTTP::REQUEST_TIMEOUT);
			} else {
				if (mail($email->to, $email->subject, $email->message, $email->headers, $email->params)) {
					http_response_code(HTTP::OK);
				} else {
					http_response_code(HTTP::INTERNAL_SERVER_ERROR);
				}
			}
		} else {
			throw new \Exception('Invalid signature', HTTP::UNAUTHORIZED);
		}
	} else {
		throw new \Exception('Invalid request', HTTP::BAD_REQUEST);
	}
} catch (\Throwable $e) {
	$err = sprintf(
		'<%s>: "%s"%s',
		$_SERVER['REMOTE_ADDR'],
		$e->getMessage(),
		PHP_EOL . json_encode(['Request' => $_POST], JSON_PRETTY_PRINT) . PHP_EOL
	);
	error_log($err . PHP_EOL, 3, ERROR_LOG);
	http_response_code($e->getCode() ?? HTTP::INTERNAL_SERVER_ERROR);
}
