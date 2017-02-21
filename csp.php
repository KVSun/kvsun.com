<?php
use \shgysk8zer0\Core\{Headers, Console};
$headers = new Headers();
if (in_array($headers->content_type, ['application/json', 'application/csp-report'])) {
	$report = json_decode(file_get_contents('php://input'));
	Console::log($report);
}
