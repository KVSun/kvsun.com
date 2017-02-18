<?php
$headers = new \shgysk8zer0\Core\Headers();
if (in_array($headers->content_type, ['application/json', 'application/csp-report'])) {
	$report = json_decode(file_get_contents('php://input'));
	\shgysk8zer0\Core\Console::log($report);
}
