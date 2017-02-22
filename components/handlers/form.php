<?php
namespace KVSun\Components\Handlers\Form;

use \shgysk8zer0\Core\{
	PDO,
	JSON_Response as Resp,
	Console,
	FormData,
	Listener,
	Headers
};
use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\Authorize\{
	Item,
	Items,
	BillingAddress,
	ShippingAddress,
	Credentials,
	CreditCard,
	ChargeCard
};
use \shgysk8zer0\Login\{User};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};

use function \KVSun\Functions\{
	restore_login,
	user_can,
	post_comment,
	category_exists,
	make_category,
	get_cat_id
};

use const \KVSun\Consts\{
	DEBUG,
	DOMAIN,
	DB_INSTALLER,
	DB_CREDS,
	CONFIG,
	AUTHORIZE,
	LOCAL_ZIPS
};

function is_tel(String $input)
{
	return preg_match('/^\d\\-\d{3}-\d{3}\-\d{4}$/', $input) ? $input : null;
}

$resp = Resp::getInstance();
if (
	array_key_exists('form', $_REQUEST) and is_string($_REQUEST['form'])
	and array_key_exists($_REQUEST['form'], $_REQUEST)
	and is_array($_REQUEST[$_REQUEST['form']])
) {
	$req = new FormData($_REQUEST);
} else {
	$resp->notify(
		'Error submitting form',
		'Form name does not match submitted data.'
	)->send();
}

switch($req->form) {
	case 'install':
		$install = $req->install;
		try {
			$db = new PDO([
				'user'     => $install->db->user,
				'password' => $install->db->pass,
				'host'     => $install->db->host,
			]);
			if ($db->connected) {
				$db->beginTransaction();
				$db(file_get_contents(DB_INSTALLER));
				$user = $db->prepare(
					'INSERT INTO `users` (
						`email`,
						`username`,
						`password`
					) VALUES (
						:email,
						:username,
						:password
					);'
				);
				$user_data = $db->prepare(
					'INSERT INTO `user_data` (
						`id`,
						`name`
					) VALUES (
						LAST_INSERT_ID(),
						:name
					);'
				);
				$subscribers = $db->prepare(
					'INSERT INTO `subscribers` (
						`id`,
						`status`,
						`sub_expires`
					) VALUES (
						LAST_INSERT_ID(),
						:status,
						null
					);'
				);

				$head = $db->prepare(
					'INSERT INTO `head` (
						`name`,
						`value`
					) VALUES(
						:name,
						:value
					);'
				);

				$user->email = $install->user->email;
				$user->username = $install->user->username;
				$user->password = password_hash($install->user->password, PASSWORD_DEFAULT);
				$user->execute();

				$user_data->name = $install->user->name;
				$user_data->execute();

				$subscribers->status = array_search('god', \KVSun\Consts\USER_ROLES);
				$subscribers->execute();

				$head->execute([
					'name' => 'title',
					'value' => $install->site->title
				]);
				$head->execute([
					'name' => 'referrer',
					'value' => 'origin-when-cross-origin'
				]);
				$head->execute([
					'name' => 'robots',
					'value' => 'follow, index'
				]);
				$head->execute([
					'name' => 'viewport',
					'value' => 'width=device-width'
				]);

				if (
					$user->allSuccessful()
					and $user_data->allSuccessful()
					and $subscribers->allSuccessful()
					and $head->allSuccessful()
				) {
					if (file_put_contents(
						DB_CREDS,
						json_encode([
							'user'     => $install->db->user,
							'password' => $install->db->pass,
							'host'     => $install->db->host,
						], JSON_PRETTY_PRINT
					))) {
						$db->commit();
						$user_login = User::load(DB_CREDS);
						$user_login($user->email, $user->password);
						Listener::login($user_login);
						$resp->notify(
							'Installation successful',
							'Reloading'
						)->reload();
					} else {
						$resp->notify(
							'Could not save credentials. Check permissions',
							"`# chmod -R '{$_SERVER['DOCUMENT_ROOT']}'`" . PHP_EOL
							. sprintf(
								"`# chgrp -R %s '%s'`",
								posix_getpwuid(posix_geteuid())['name'],
								$_SERVER{'DOCUMENT_ROOT'}
							)
						);
					}
				} else {
					$resp->notify(
						'There was an error installing',
						'Database connection was successfully made, but there
						was an error setting data.'
					);
				}
			} else {
				$resp->notify(
					'Error installing',
					'Double check your database credentials and make sure that
					the use is created and has access to the existing database
					on the server. <https://dev.mysql.com/doc/refman/5.7/en/grant.html>'
				)->focus('#install-db-user');
			}
		} catch(\Exception $e) {
			Console::error([
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
				'trace'   => $e->getTrace(),
			]);
		} finally {
			Console::getInstance()->sendLogHeader();
			$resp->send();
		}
		exit;
		break;

	case 'install-form':
		if (!is_dir(CONFIG)) {
			$resp->notify('Config dir does not exist', CONFIG);
		} elseif (! is_writable(CONFIG)) {
			$resp->notify('Cannot write to config directory', CONFIG);
		} elseif (file_exists(DB_CREDS)) {
			$resp->notify('Already installed', 'Database config file exists.');
		} elseif (! file_exists(DB_INSTALLER)) {
			$resp->notify('SQL file not found', 'Please restore "default.sql" using Git.');
		} else {
			$installer = $_POST['install-form'];
			Console::log($installer['db']);
			if (array_key_exists('db', $installer) and is_array($installer['db'])) {
				try {
					file_put_contents(
						DB_CREDS,
						json_encode($installer['db'], JSON_PRETTY_PRINT)
					);
					$pdo = new PDO();
					if ($pdo->connected) {
						if (empty($pdo->showTables())) {
							$pdo->restore(DB_INSTALLER);
							if (! $pdo->showTables()) {
								$resp->notify('Error', 'Could not restore database.');
							} else {
								$resp->notify('Installed', 'Created new default database.');
								//$resp->reload();
								$resp->remove('form');
								$dom = HTML::getInstance();
								$form = load('registration-form');
								$resp->append(['body' => "{$form[0]}"]);
							}
						} else {
							$resp->notify('Installed', 'Using existing database.');
							$resp->reload();
						}
					} else {
						$resp->notify('Error', 'Could not connect to database using given credentials.');
						unlink(DB_CREDS);
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
		$user = User::load(DB_CREDS);
		$user::$check_wp_pass = true;
		if ($user($req->login->email, $req->login->password)) {
			Listener::login($user, isset($req->login->remember));
		} else {
			$resp->notify('Login Rejected');
			$resp->focus('#login-email');
		}
		break;

	case 'register':
		if (
			isset(
				$req->register,
				$req->register->username,
				$req->register->email,
				$req->register->name,
				$req->register->password
			)
			and filter_var($req->register->email, \FILTER_VALIDATE_EMAIL)
		) {
			try {
				$pdo = PDO::load(DB_CREDS);
				$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				$pdo->beginTransaction();
				$users = $pdo->prepare('INSERT INTO `users` (
					`email`,
					`username`,
					`password`
				) VALUES (
					:email,
					:username,
					:password
				);');
				$user_data = $pdo->prepare('INSERT INTO `user_data` (
					`id`,
					`name`
				) VALUES (
					LAST_INSERT_ID(),
					:name
				);');
				$subscribers = $pdo->prepare('INSERT INTO `subscribers` (
					`id`,
					`status`,
					`sub_expires`
				) VALUES (
					LAST_INSERT_ID(),
					:status,
					NULL
				);');
				$users->execute([
					'email' => $req->register->email,
					'username' => $req->register->username,
					'password' => password_hash($req->register->password, \PASSWORD_DEFAULT)
				]);
				$user_data->execute(['name' => $req->register->name]);
				$subscribers->execute(['status' => array_search('guest', \KVSun\Consts\USER_ROLES)]);
				$pdo->commit();
				$user = User::load(DB_CREDS);
				if ($user($req->register->email, $req->register->password)) {
					Listener::login($user);
				} else {
					$resp->notify('Error registering', 'There was an error saving your user info');
				}
				$resp->send();
			} catch(\Exception $e) {
				Console::error($e);
			}
		} else {
			$resp->notify('Invalid registration entered', 'Please check your inputs');
			$resp->focus('register[username]');
			$resp->send();
		}

		break;

	case 'user-update':
		$resp->notify('Form received', 'Check console.');
		// $data = new FormData($_POST['user-update']);
		$data = filter_var_array(
			$_POST['user-update'],
			[
				'email' => [
					'filter' => FILTER_VALIDATE_EMAIL,
					'flags' => FILTER_NULL_ON_FAILURE
				],
				'tel' => [
					'filter' => FILTER_CALLBACK,
					'flags' => FILTER_NULL_ON_FAILURE,
					'options' => __NAMESPACE__ . '\is_tel'
				],
				'g+' => [
					'filter' => FILTER_VALIDATE_URL,
					'flags' => FILTER_NULL_ON_FAILURE
				],
				'twitter' => [
					'filter' => FILTER_VALIDATE_URL,
					'flags' => FILTER_NULL_ON_FAILURE
				],
			], true
		);
		$data = new FormData($data);

		$pdo = PDO::load(DB_CREDS);

		$pdo->beginTransaction();
		$user = restore_login();
		$user_stm = $pdo->prepare('UPDATE `users`
			SET `email` = :email
			WHERE `id` = :id
			LIMIT 1;'
		);
		$user_data_stm = $pdo->prepare('UPDATE `user_data`
			SET tel = :tel,
			`g+` = :gplus,
			`twitter` = :twitter
			WHERE `id` = :id
			LIMIT 1;'
		);

		$user_stm->id = $user->id;
		$user_stm->email = isset($data->email) ? $data->email : $user->email;

		$user_data_stm->tel = isset($data->tel) ? $data->tel : $user->tel;
		$user_data_stm->gplus = isset($data->{'g+'}) ? $data->{'g+'} : $user->{'g+'};
		$user_data_stm->twitter = isset($data->twitter) ? $data->twitter : $user->twitter;
		$user_data_stm->id = $user->id;

		if ($user_stm->execute() and $user_data_stm->execute()) {
			$pdo->commit();
			$resp->notify('Success', 'Data has been updated.');
			$resp->remove('#update-user-dialog');
		} else {
			$resp->notify('Failed', 'Failed to update user data');
		}

		Console::info($data);
		$resp->send();
		break;

	case 'search':
		$resp->notify('Search results', 'Check console for more info');
		$pdo = PDO::load(DB_CREDS);
		try {
			$stm = $pdo->prepare('SELECT * FROM `posts` WHERE `title` LIKE :query');
			// $stm->query = "%{$_REQUEST['search']['query']}%";
			$stm->query = str_replace(' ', '%', "%{$req->query}%");
			$results = $stm->execute()->getResults();
			Console::table($results);
		} catch (\Exception $e) {
			Console::error($e);
		}
		break;

	case 'comments':
		if (user_can('comment')) {
			$headers = Headers::getInstance();
			if (!isset($headers->referer)) {
				$resp->notify(
					'Cannot post comment',
					'You seem to have your privacy settings blocking us from knowing which post you are trying to post a comment on.',
					'/images/octicons/lib/svg/comment-discussion.svg'
				)->send();
			} else {
				$url = $headers->referer;
				$comment = new FormData($_POST['comments']);
				if (!filter_var($url, FILTER_VALIDATE_URL, [
					'flags' => FILTER_FLAG_PATH_REQUIRED
				])) {
					$resp->notify(
						'You cannot post on this page',
						'You seem to by trying to comment on the home page.',
						'/images/octicons/lib/svg/comment-discussion.svg'
					)->send();
				} elseif (!isset($comment->text)) {
					$resp->notify(
						'We seem to be missing the comment',
						'Double check that you\'ve filled out the comment box and try again.',
						'/images/octicons/lib/svg/comment-discussion.svg'
					)->send();
				}
				$user = restore_login();
				if (post_comment(
					$url,
					$user,
					$comment->text,
					user_can('skipApproval')
				)) {
					$resp->notify(
						'Comment submitted',
						'Comments are not displayed until approval by an editor.',
						'/images/octicons/lib/svg/comment-discussion.svg'
					);
					$resp->clear('comments');
				} else {
					$resp->notify('There was an error posting your comment.');
				}
			}
		} else {
			$resp->notify('You must be logged in to comment.');
			$resp->showModal('#login-dialog');
		}
		break;

	case 'comment-moderator-form':
		if (! user_can('moderateComments')) {
			$resp->notify(
				"I'm afraid I can't let you do that, Dave",
				'You are not authorized to moderate comments.',
				'/images/octicons/lib/svg/alert.svg'
			);
		} else {
			$comments = new FormData($_POST['comment-moderator-form']);
			try {
				$pdo = PDO::load(DB_CREDS);
				$pdo->beginTransaction();
				$stm = $pdo->prepare(
					'UPDATE `post_comments`
					SET `approved` = :approved
					WHERE `id` = :id
					LIMIT 1;'
				);
				foreach ($comments->approved as $id => $approved) {
					$approved = $approved === '1';
					$stm->bindParam(':id', $id);
					$stm->bindParam(':approved', $approved);
					$stm->execute();
				}
				$pdo->commit();
				$resp->notify(
					'Comments have been updated',
					'You may now close moderator form or make more changes',
					'/images/octicons/lib/svg/comment-discussion.svg'
				);
			} catch (\Throwable $e) {
				trigger_error($e->getMessage());
				$resp->notify(
					'Error updating comments',
					"{$e->getMessage()} on {$e->getFile()}:{$e->getLine()}",
					'/images/octicons/lib/svg/bug.svg'
				 );
			}
		}
		break;
	case 'new-post':
		if (! user_can('createPosts')) {
			http_response_code(HTTP::UNAUTHORIZED);
			$resp->notify('Error', 'You must be logged in for that.')->send();
		}

		$post = new FormData($_POST['new-post']);

		if (! isset($post->author, $post->title, $post->content, $post->category)) {
			$resp->notify(
				'Missing info for post',
				'Please make sure it has a title, author, and content.'
			)->send();
		}

		$pdo = PDO::load(DB_CREDS);
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
		try {
			if (! category_exists($post->category)) {
				if (! make_category($post->category)) {
					$resp->notify('Error creating category', 'Try an existing category or contact an admin.');
				}
			}
			$stm = $pdo->prepare($sql);
			$user = restore_login();
			$stm->title = strip_tags($post->title);
			$stm->cat = get_cat_id($post->category);
			$stm->author = strip_tags($post->author);
			$stm->content = $post->content;
			$stm->draft = isset($post->draft) or ! user_can('skipApproval');
			$stm->url = strtolower(str_replace(' ', '-', strip_tags($post->title)));
			$stm->posted = $user->id;
			$stm->keywords = $post->keywords ?? null;
			$stm->description = $post->description ?? null;

			$article_dom = new \DOMDocument();
			$article_dom->loadHTML($post->content);
			$imgs = $article_dom->getElementsByTagName('img');

			$stm->img = ($imgs = $article_dom->getElementsByTagName('img') and $imgs->length !== 0)
			? $imgs->item(0)->getAttribute('src') : null;

			unset($article_dom, $imgs);

			if (intval($stm->errorCode()) !== 0) {
				throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
			}
			if ($stm->execute()) {
				$resp->notify('Received post', $post->title);
			} else {
				trigger_error('Error posting article.');
				$resp->notify('Error', 'There was an error creating the post');
			}
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
			$resp->notify('There was an error creating post', $e->getMessage());
		}
		break;

	case 'update-post':
		if (! user_can('editPosts')) {
			http_response_code(HTTP::UNAUTHORIZED);
			$resp->notify('Error', 'You must be logged in for that.')->send();
		}

		$post = new FormData($_POST['update-post']);
		Console::info($post);

		if (
			!isset($post->category, $post->title, $post->author, $post->content, $post->url)
			or !filter_var($post->url, FILTER_VALIDATE_URL, [
				'flags' => FILTER_FLAG_PATH_REQUIRED,
			])
		) {
			$resp->notify(
				'Missing info for post',
				'Please make sure it has a title, author, and content.'
			)->send();
		}

		$stm = PDO::load(DB_CREDS)->prepare(
			'UPDATE `posts` SET
				`cat-id` = :cat,
				`title` = :title,
				`author` = :author,
				`content` = :content,
				`draft` = :draft,
				`img` = :img,
				`keywords` = :keywords,
				`description` = :description
			WHERE `url` = :url
			LIMIT 1;'
		);

		try {
			if (! category_exists($post->category)) {
				if (! make_category($post->category)) {
					$resp->notify('Error creating category', 'Try an existing category or contact an admin.');
				}
			}
			$url = explode('/', rtrim($post->url, '/'));
			$stm->cat = get_cat_id($post->category);
			$stm->title = strip_tags($post->title);
			$stm->author = strip_tags($post->author);
			$stm->content = $post->content;
			$stm->draft = isset($post->draft);
			$stm->keywords = strip_tags($post->keywords) ?? null;
			$stm->description = strip_tags($post->description) ?? null;
			$stm->url = end($url);
			$article_dom = new \DOMDocument();
			$article_dom->loadHTML($post->content);
			$imgs = $article_dom->getElementsByTagName('img');

			$stm->img = ($imgs = $article_dom->getElementsByTagName('img') and $imgs->length !== 0)
				? $imgs->item(0)->getAttribute('src') : null;

			unset($article_dom, $imgs);
			$stm->execute();
			if (intval($stm->errorCode()) !== 0) {
				throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
			}
			$resp->notify('Update submitted', "Updated '{$post->title}'");
			$resp->reload();
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
			$resp->notify('Error updating post', $e->getMessage());
		}
		break;

	case 'ccform':
		$user = restore_login();
		$billing = new BillingAddress($req->ccform->billing->getArrayCopy());

		if (!$billing->validate()) {
			$resp->notify(
				'Double check your address',
				'Looks like you missed some info when entering your address',
				'/images/octicons/lib/svg/credit-card.svg'
			)->focus('#ccform-billing-first-name')->send();
		}
		$pdo = PDO::load(DB_CREDS);

		$stm = $pdo->prepare('SELECT
				`id`,
				`name`,
				`description`,
				`length`,
				`price`,
				`media`,
				`isLocal`,
				`includes`
			FROM `subscription_rates`
			WHERE `id` = :id
			LIMIT 1;'
		);
		$stm->id = $req->ccform->subscription;
		$stm->execute();

		$sub = $stm->fetchObject();

		if (
			$sub->media === 'print'
			and $sub->isLocal
			and ! in_array(intval($req->ccform->billing->zip), LOCAL_ZIPS)
		) {
			$resp->notify(
				'You do not qualify for this subscription',
				'Please select from our out of Valley print subscriptions',
				'/images/octicons/lib/svg/credit-card.svg'
			)->focus('#ccform-subscription')->send();
		} elseif (
			$sub->media === 'print'
			and !$sub->isLocal
			and in_array(intval($req->ccform->billing->zip), LOCAL_ZIPS)
		) {
			$resp->notify(
				'You do not qualify for this subscription',
				'Please select from our local print subscriptions',
				'/images/octicons/lib/svg/credit-card.svg'
			)->focus('#ccform-subscription')->send();
		}

		$creds = Credentials::loadFromIniFile(AUTHORIZE, DEBUG);
		$expires = new \DateTime(
			"{$req->ccform->card->expires->year}-{$req->ccform->card->expires->month}"
		);

		$card = new CreditCard(
			$req->ccform->card->name,
			$req->ccform->card->num,
			$expires,
			$req->ccform->card->csc
		);

		$request = new ChargeCard($creds, $card);
		$request->setInvoice(rand(pow(10, 7), pow(10, 15) - 1));
		$shipping = new ShippingAddress();
		$shipping->fromAddress($billing);
		$request->setShippingAddress($shipping);
		$request->setBillingAddress($billing);

		$item = new Item(get_object_vars($sub));
		if (! $item->validate()) {
			$resp->notify(
				'Something went wrong',
				'We seem to be missing information about that subscription.' .
				PHP_EOL . 'Please contact us about this issue.',
				'/images/octicons/lib/svg/credit-card.svg'
			)->send();
		}
		$items = new Items();
		$items->addItem($item);

		try {
			if (!empty($sub->includes)) {
				$includes = explode(',', $sub->includes);
				$includes = array_map('intval', $includes);
				foreach (array_filter($includes) as $include) {
					if ($include == $sub->id) {
						throw new \Exception("Recursive subscription for {$sub->name}.");
					} else {
						$stm->id = $include;
						$stm->execute();
						$included = $stm->fetchObject();
						$included->price = 0;
						$item = new Item(get_object_vars($included));
						$items->addItem($item);
					}
				}
			}
		} catch(\Exception $e) {
			$resp->notify(
				'We are sorry, but there was an error',
				'Please contact us for help with your subscription.'
			)->send();
		}

		$request->addItems($items);
		$response = $request();
		if ($response->code == '1') {
			$record = $pdo->prepare('INSERT INTO `transactions` (
					`transactionID`,
					`authCode`,
					`userID`,
					`subscriptionID`
				) VALUES (
					:transactionID,
					:authCode,
					:userID,
					:subscription
				);'
			);

			$record->execute([
				'transactionID' => $response->transactionID,
				'authCode'      => $response->authCode,
				'userID'        => $user->id,
				'subscription'  => $sub->id,
			]);

			$subscribe = $pdo->prepare(
				'INSERT INTO `subscribers` (
					`id`,
					`status`,
					`sub_expires`
				) VALUES (
					:id,
					:status,
					:expires
				) ON DUPLICATE KEY
				UPDATE `status` = :status, `sub_expires` = :expires;'
			);

			foreach ($request->getItems() as $item) {
				if (
					isset($item->length, $item->media)
					and $item->media === 'online'
				) {
					$expires = new \DateTime("+ {$item->length}");
					Console::info($expires);
					$subscribe->id = $user->id;
					$subscribe->status = array_search('subscriber', \KVSun\Consts\USER_ROLES);
					$subscribe->expires = $expires->format('Y-m-d H:i:s');
					$subscribe->execute();
				}
			}

			$resp->notify(
				'Subscription successful',
				$response,
				'/images/octicons/lib/svg/credit-card.svg'
			);
			if (DEBUG) {
				Console::log([
					'respCode'      => $response->code,
					'authCode'      => $response->authCode,
					'transactionID' => $response->transactionID,
					'messages'      => $response->messages,
					'errors'        => $response->errors,
				]);
			}

			$resp->remove('#ccform-dialog');
		} else {
			$resp->notify(
				'There was an error processing your subscription',
				$response,
				'/images/octicons/lib/svg/credit-card.svg'
			);

			if (DEBUG) {
				Console::log([
					'respCode'      => $response->code,
					'authCode'      => $response->authCode,
					'transactionID' => $response->transactionID,
					'messages'      => $response->messages,
					'errors'        => $response->errors,
				]);
			}
		}
		break;

	default:
		trigger_error('Unhandled form submission.');
		header('Content-Type: application/json');
		if (DEBUG) {
			Console::info($req);
		}
		exit('{}');
}
