<?php
namespace ImportUsers;
use function \KVSun\Functions\{import_users};
if (PHP_SAPI !== 'cli') {
	http_response_code(404);
} else {
	require __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
	$imported = import_users('./members_list.csv', './users.csv');
	echo "Imported {$imported} users." . PHP_EOL;
}
