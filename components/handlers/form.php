<?php
namespace KVSun\Components\Handlers\Form;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\Core_API\Abstracts\HTTPStatusCodes as Status;

$resp = Core\JSON_Response::getInstance();

switch($_REQUEST['form']) {
	case 'install-form':
		if (!is_dir(\KVSun\CONFIG)) {
			$resp->notify('Config dir does not exist', \KVSun\CONFIG);
		} elseif (! is_writable(\KVSun\CONFIG)) {
			$resp->notify('Cannot write to config directory', \KVSun\CONFIG);
		} elseif (file_exists(\KVSun\CONFIG . \KVSun\DB_CREDS)) {
			$resp->notify('Already installed', 'Database config file exists.');
		} elseif (! file_exists(\KVSun\DB_INSTALLER)) {
			$resp->notify('SQL file not found', 'Please restore "default.sql" using Git.');
		} else {
			$installer = $_POST['install-form'];
			Core\Console::getInstance()->log($installer['db']);
			if (array_key_exists('db', $installer) and is_array($installer['db'])) {
				try {
					file_put_contents(
						\KVSun\CONFIG . \KVSun\DB_CREDS,
						json_encode($installer['db'], JSON_PRETTY_PRINT)
					);
					$pdo = new Core\PDO();
					if ($pdo->connected) {
						if (empty($pdo->showTables())) {
							$pdo->restore(\KVSun\DB_INSTALLER);
							if (! $pdo->showTables()) {
								$resp->notify('Error', 'Could not restore database.');
							} else {
								$resp->notify('Installed', 'Created new default database.');
								//$resp->reload();
								$resp->remove('form');
								$dom = DOM\HTML::getInstance();
								$form = load('registration-form');
								$resp->append(['body' => "{$form[0]}"]);
							}
						} else {
							$resp->notify('Installed', 'Using existing database.');
							$resp->reload();
						}
					} else {
						$resp->notify('Error', 'Could not connect to database using given credentials.');
						unlink(\KVSun\DB_CREDS);
					}
				} catch(\Exception $e) {
					$resp->notify('Error', $e->getMessage());
				}
			} else {
				$resp->notify('Missing input', 'Please fill out the form correctly.');
			}
		}
		break;

	case 'login':
		$user = \shgysk8zer0\Login\User::load(\KVSun\DB_CREDS);
		$user::$check_wp_pass = true;
		if ($user($_POST['login']['email'], $_POST['login']['password'])) {
			if (array_key_exists('remember', $_POST['login'])) {
				$user->setCookie();
			}
			$user->setSession();
			$resp->notify('Login Successful', "Welcome back, $user");
			$resp->close('#login-dialog');
			$resp->clear('login');
			unset($ser);
		} else {
			$resp->notify('Login Rejected');
			$resp->focus('#login-email');
		}
		break;

	case 'registration-form':
		$users = $pdo('SELECT count(*) FROM `users`;');

		break;

	case 'search':
		$resp->notify('Search results', 'Check console for more info');
		$pdo = Core\PDO::load();
		try {
			$stm = $pdo->prepare('SELECT * FROM `posts` WHERE `title` LIKE :query');
			// $stm->query = "%{$_REQUEST['search']['query']}%";
			$stm->query = str_replace(' ', '%', "%{$_REQUEST['search']['query']}%");
			$results = $stm->execute()->getResults();
			Core\Console::getInstance()->table($results);
		} catch (\Exception $e) {
			Core\Console::getInstance()->error($e);
		}
		break;

	case 'new-post':
		if (! \KVSun\check_role('editor')) {
			http_response_code(Status::UNAUTHORIZED);
			$resp->notify('Error', 'You must be logged in for that.')->send();
		}

		$pdo = Core\PDO::load(\KVSun\DB_CREDS);
		$sql = 'INSERT INTO `posts` (
			`cat-id`,
			`title`,
			`author`,
			`content`,
			`posted`,
			`updated`,
			`draft`,
			`url`,
			`img`,
			`posted_by`,
			`keywords`,
			`description`
		) VALUES (
			:cat,
			:title,
			:author,
			:content,
			CURRENT_TIMESTAMP,
			CURRENT_TIMESTAMP,
			:draft,
			:url,
			:img,
			:posted,
			:keywords,
			:description
		);';
		$stm = $pdo->prepare($sql);
		$user = \KVSun\restore_login();
		$data = [];
		$data['title'] = strip_tags($_POST['new-post']['title']);
		$data['cat'] = 1;
		$data['author'] = strip_tags($_POST['new-post']['author']);
		$data['content'] = $_POST['new-post']['content'];
		$data['draft'] = array_key_exists('draft', $_POST['new-post']);
		$data['url'] = strtolower(str_replace(' ', '-', strip_tags($_POST['new-post']['title'])));
		$data['posted'] = $user->id;
		$data['keywords'] = array_key_exists('keywords', $_POST['new-post'])
			? $_POST['new-post']['keywords']
			: null;
		$data['description'] = array_key_exists('description', $_POST['new-post'])
			? $_POST['new-post']['description']
			: null;

		$article_dom = new \DOMDocument();
		$article_dom->loadHTML($data['content']);
		$imgs = $article_dom->getElementsByTagName('img');

		$data['img'] = isset($imgs) ? $imgs->item(0)->getAttribute('src') : null;

		unset($article_dom, $imgs);
		Core\Console::getInstance()->info($data);

		if ($stm->execute($data)) {
			$resp->notify('Received post', $_POST['new-post']['title'])->send();
		} else {
			trigger_error('Error posting article.');
			$resp->notify('Error', 'There was an error creating the post');
		}
		break;

	default:
		trigger_error('Unhandled form submission.');
		header('Content-Type: application/json');
		if (\KVSun\DEBUG) {
			Core\Console::getInstance()->info($_REQUEST);
		}
		exit('{}');
}
