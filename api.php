<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API\Abstracts\HTTPStatusCodes as Status;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

$header  = Core\Headers::getInstance();

if ($header->accept === 'application/json') {
	$resp = Core\JSON_Response::getInstance();
	if (array_key_exists('url', $_GET)) {
		$url = new Core\URL($_GET['url']);
		$page = new Page($url);
		$header->content_type = 'application/json';
		Core\Console::getInstance()->log($page);
		exit(json_encode($page));
	} elseif (array_key_exists('form', $_REQUEST) and is_array($_REQUEST[$_REQUEST['form']])) {
		require_once COMPONENTS . 'handlers' . DIRECTORY_SEPARATOR . 'form.php';
	} elseif (array_key_exists('datalist', $_GET)) {
		switch($_GET['datalist']) {
			case 'author_list':
				$pdo = Core\PDO::load(DB_CREDS);
				$stm = $pdo->prepare('SELECT `name`
					FROM `user_data`
					JOIN `subscribers` ON `subscribers`.`id` = `user_data`.`id`
					WHERE `subscribers`.`status` <= :role;'
				);
				$stm->role = array_search('freelancer', USER_ROLES);
				$authors = $stm->execute()->getResults();
				$authors = array_map(function(\stdClass $author) {
					return $author->name;
				}, $authors);

				$datalist = make_datalist('author_list', $authors);

				$resp->append('body', $datalist)->send();
				break;

			default:
				trigger_error("Request for unhandled list: {$_GET['list']}");
				header('Content-Type: application/json');
				exit('{}');
		}
	} elseif (array_key_exists('load_menu', $_GET)) {
		$menu = COMPONENTS . 'menus' . DIRECTORY_SEPARATOR . $_GET['load_menu'] . '.html';
		if (@file_exists($menu)) {
			$resp->append('body', file_get_contents($menu));
		} else {
			$resp->notify('Request for menu', $_GET['load_menu']);
		}
	} elseif(array_key_exists('upload', $_FILES)) {
		if (! check_role('editor')) {
			trigger_error('Unauthorized upload attempted');
			http_response_code(Status::UNAUTHORIZED);
			exit('{}');
		}
		$file = new \shgysk8zer0\Core\UploadFile('upload');
		if (in_array($file->type, ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'])) {
			if ($file->saveTo('images', 'uploads', date('Y'), date('m'))) {
				header('Content-Type: application/json');
				exit($file);
			} else {
				$resp->notify('Failed', 'Could not save uploaded file.');
			}
		} else {
			throw new \Exception("{$file->name} has a type of {$file->type}, which is not allowed.");
		}
	} else {
		$resp->notify('Invalid request', 'See console for details.', DOMAIN . 'images/sun-icons/128.png');
		Core\Console::getInstance()->info($_REQUEST);
	}
	$resp->send();
	exit();
}  elseif(array_key_exists('url', $_GET)) {
	$url = new Core\URL($_GET['url']);
	if ($url->host === $_SERVER['SERVER_NAME']) {
		$header->location = "{$url}";
		http_response_code(Status\SEE_OTHER);
	} else {
		http_response_code(Status\BAD_REQUEST);
	}
}  else {
	http_response_code(Status\BAD_REQUEST);
	$header->content_type = 'application/json';
	exit('{}');
}
