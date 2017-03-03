<?php
namespace KVSun\Components\Handlers\Form;

use \shgysk8zer0\Core\{
	PDO,
	JSON_Response as Resp,
	Console,
	FormData,
	Listener,
	Headers,
	URL,
	Gravatar
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
use \shgysk8zer0\PHPCrypt\{PublicKey, PrivateKey, FormSign};
use \KVSun\KVSAPI\{Picture};

use function \KVSun\Functions\{
	restore_login,
	user_can,
	get_role_id,
	email,
	password_reset_email,
	post_comment,
	category_exists,
	make_category,
	get_cat_id,
	get_img_id
};

use const \KVSun\Consts\{
	DEBUG,
	DOMAIN,
	ICONS,
	DB_INSTALLER,
	DB_CREDS,
	CONFIG,
	AUTHORIZE,
	PRIVATE_KEY,
	PUBLIC_KEY,
	PASSWD,
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

				$user->email = strtolower($install->user->email);
				$user->username = $install->user->username;
				$user->password = password_hash($install->user->password, PASSWORD_DEFAULT);
				$user->execute();

				$user_data->name = $install->user->name;
				$user_data->execute();

				$subscribers->status = get_role_id('dev');
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
						was an error setting data.',
						ICONS['bug'],
						true
					);
				}
			} else {
				$resp->notify(
					'Error installing',
					'Double check your database credentials and make sure that
					the use is created and has access to the existing database
					on the server. <https://dev.mysql.com/doc/refman/5.7/en/grant.html>',
					ICONS['bug'],
					true
				)->focus('#install-db-user');
			}
		} catch(\Exception $e) {
			trigger_error($e->getMessage());
		} finally {
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
								$resp->notify(
									'Error',
									'Could not restore database.',
									ICONS['bug'],
									true
								);
							} else {
								$resp->notify(
									'Installed',
									'Created new default database.',
									ICONS['server']
								);
								//$resp->reload();
								$resp->remove('form');
								$dom = HTML::getInstance();
								$form = load('registration-form');
								$resp->append(['body' => "{$form[0]}"]);
							}
						} else {
							$resp->notify(
								'Installed',
								'Using existing database.',
								ICONS['server']
							);
							$resp->reload();
						}
					} else {
						$resp->notify(
							'Error',
							'Could not connect to database using given credentials.',
							ICONS['bug'],
							true
						);
						unlink(DB_CREDS);
					}
				} catch(\Exception $e) {
					$resp->notify(
						'Error',
						$e->getMessage(),
						ICONS['bug'],
						true
					);
				}
			} else {
				$resp->notify('Missing input', 'Please fill out the form correctly.');
			}
		}
		break;

	case 'login':
		USER::$check_wp_pass = true;
		$user = User::load(DB_CREDS);
		if ($user($req->login->email, $req->login->password)) {
			Listener::login($user, isset($req->login->remember));
		} else {
			$resp->notify(
				'Login Rejected',
				'Double check your username & password',
				DOMAIN . ICONS['alert'],
				true
			);
			$resp->focus('#login-email');
			$resp->animate('#login-dialog', [
				['transform' => 'none'],
				['transform' => 'translateX(5em)scale(0.9)'],
				['transform' => 'translateX(-5em)scale(1.1)'],
				['transform' => 'none']
			], [
				'duration'   => 100,
				'iterations' => 3,
			]);
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
					'email'    => strtolower($req->register->email),
					'username' => strtolower($req->register->username),
					'password' => password_hash($req->register->password, PASSWORD_DEFAULT)
				]);
				$user_data->execute(['name' => $req->register->name]);
				$subscribers->execute(['status' => get_role_id('guest')]);
				$pdo->commit();
				$user = User::load(DB_CREDS);
				if ($user($req->register->email, $req->register->password)) {
					Listener::login($user);
				} else {
					$resp->notify(
						'Error registering',
						'There was an error saving your user info',
						ICONS['bug'],
						true
					);
				}
			} catch(\Exception $e) {
				Console::error($e);
			}
		} else {
			$resp->notify(
				'Invalid registration entered',
				'Please check your inputs',
				ICONS['thmbsdown'],
				true
			);
			$resp->focus('register[username]');
		}
		break;

	case 'forgot_password':
		if (isset($req->forgot_password->user)) {
			password_reset_email(User::search(DB_CREDS, $req->forgot_password->user));
			$resp->notify(
				'Request received',
				'If a matching user exists, an email has been sent. Check your email.',
				ICONS['inbox'],
				true
			)->remove('#forgot_password_dialog');
		} else {
			$resp->notify(
				'Missing user info',
				'Double check your input and try again',
				ICONS['alert'],
				true
			)->focus(
				'#forgot_password-user'
			)->animate('#forgot_password_dialog', [
				['transform' => 'none'],
				['transform' => 'translateX(5em)scale(0.9)'],
				['transform' => 'translateX(-5em)scale(1.1)'],
				['transform' => 'none']
			], [
				'duration'   => 100,
				'iterations' => 3,
			]);
		}
		break;

	case 'password-reset':
		$reset  = $req->{'password-reset'};
		$signer = new FormSign(PUBLIC_KEY, PRIVATE_KEY, PASSWD);
		$key    = PrivateKey::importFromFile(PRIVATE_KEY, PASSWD);
		if (! (
			$username = $key->decrypt($reset->user)
			and $user = User::search(DB_CREDS, $username)
			and isset($user->username, $user->email, $user->name)
		)) {
			$resp->notify(
				'Invalid request',
				'Could not find that user',
				ICONS['bug'],
				true
			);
		}
		if (! isset(
			$reset->password,
			$reset->repeat
		) or ($reset->password !== $reset->repeat)
			or ! preg_match('/^.{8,}$/', $reset->password)
		) {
			$resp->notify(
				'Password mismatch or too short',
				'Double check your inputs',
				ICONS['alert'],
				true
			)->animate('dialog[open]', [
				['transform' => 'none'],
				['transform' => 'translateX(-5em) scale(0.9)'],
				['transform' => 'translateX(5em) scale(1.1)'],
				['transform' => 'none']
			], [
				'duration' => 100,
				'iterations' => 3,
			])->focus('#password-reset-password');
		} elseif(! $signer->verifyFormSignature($_POST['password-reset'])) {
			$resp->notify(
				'Something went wrong :(',
				'Please contact us for support',
				ICONS['bug'],
				true
			);
		} elseif ($user->updatePassword($reset->password)) {
			$resp->notify(
				"Password changed for {$user->name}",
				'Your password has been updated and you are now signed in.',
				new Gravatar($user->email),
				true
			)->remove('dialog[open]');
		} else {
			$resp->notify(
				'There was an error updating your password',
				'Either try again or contact us for support.',
				ICONS['bug'],
				true
			);
		}
		break;

	case 'user-update':
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
		$user_stm->email = isset($data->email) ? strtolower($data->email) : $user->email;

		$user_data_stm->tel = isset($data->tel) ? $data->tel : $user->tel;
		$user_data_stm->gplus = isset($data->{'g+'}) ? $data->{'g+'} : $user->{'g+'};
		$user_data_stm->twitter = isset($data->twitter) ? $data->twitter : $user->twitter;
		$user_data_stm->id = $user->id;

		if ($user_stm->execute() and $user_data_stm->execute()) {
			$pdo->commit();
			$resp->notify(
				'Success',
				'Data has been updated.',
				ICONS['thumbsup']
			);
			$resp->remove('#update-user-dialog');
		} else {
			$resp->notify(
				'Failed',
				'Failed to update user data',
				ICONS['bug'],
				true
			);
		}
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
					ICONS['comment-discussion'],
					true
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
						ICONS['comment-discussion'],
						true
					)->send();
				} elseif (!isset($comment->text)) {
					$resp->notify(
						'We seem to be missing the comment',
						'Double check that you\'ve filled out the comment box and try again.',
						ICONS['comment-discussion'],
						true
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
						ICONS['comment-discussion']
					);
					Listener::commentPosted($user, $url, $comment);
					$resp->clear('comments');
				} else {
					$resp->notify(
						'There was an error posting your comment.',
						'Something seems to have gone wrong.',
						ICONS['bug'],
						true
					);
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
				ICONS['alert']
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
					ICONS['comment-discussion']
				);
			} catch (\Throwable $e) {
				trigger_error($e->getMessage());
				$resp->notify(
					'Error updating comments',
					"{$e->getMessage()} on {$e->getFile()}:{$e->getLine()}",
					ICONS['bug']
				 );
			}
		}
		break;

	# TODO Use images / srcset from database instead of from post content
	case 'new-post':
		if (! user_can('createPosts')) {
			http_response_code(HTTP::UNAUTHORIZED);
			$resp->notify('Error', 'You must be logged in for that.')->send();
		}

		$post = new FormData($_POST['new-post']);

		if (! isset($post->author, $post->title, $post->content, $post->category)) {
			$resp->notify(
				'Missing info for post',
				'Please make sure it has a title, author, and content.',
				ICONS['thumbsdown']
			)->send();
		}

		$pdo = PDO::load(DB_CREDS);
		$pdo->beginTransaction();
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
			if (! (category_exists($post->category) or make_category($post->category))) {
				$resp->notify(
					'Error creating category',
					'Try an existing category or contact an admin.',
					ICONS['bug']
				);
			}
			$stm = $pdo->prepare($sql);
			$user = restore_login();
			$stm->title = strip_tags($post->title);
			$stm->cat = get_cat_id($post->category);
			$stm->author = strip_tags($post->author);
			$stm->draft = isset($post->draft) or ! user_can('skipApproval');
			$stm->url = urlencode(strtolower(str_replace(' ', '-', strip_tags($post->title))));
			$stm->posted = $user->id;
			$stm->keywords = $post->keywords ?? null;
			$stm->description = $post->description ?? null;

			$article_dom = new \DOMDocument();
			libxml_use_internal_errors(true);
			$article_dom->loadHTML("<div>$post->content</div>");
			libxml_clear_errors();

			if ($figures = $article_dom->getElementsByTagName('figure')) {
				$picture = new Picture($pdo);
				$user = restore_login();
				$main_img = null;
				foreach ($figures as $figure) {
					if ($figure->hasAttribute('data-image-id')) {
						if (is_null($main_img)) {
							$stm->img = $figure->getAttribute('data-image-id');
						}
						$microdata = $picture->parseFigure($figure);
						if (! empty($microdata)) {
							try {
								$picture->addImage($microdata, $user);
							} catch (\Exception $e) {
							trigger_error($e->getMessage());
							}
						}
						$figure->removeAttribute('itemprop');
						$figure->removeAttribute('itemtype');
						$figure->removeAttribute('itemscope');
						while ($figure->hasChildNodes() and $node = $figure->firstChild) {
							$figure->removeChild($node);
						}
					}
				}
			}
			# Need to get the content out of DOM structured `<html><body><div>$content...`
			$stm->content = $article_dom->saveHTML($article_dom->documentElement->firstChild->firstChild);

			unset($article_dom, $imgs, $img, $id, $url);

			if ($stm->execute() and intval($stm->errorCode()) === 0) {
				$pdo->commit();
				$resp->notify(
					'Received post',
					$post->title,
					ICONS['thumbsup']
				);
				Listener::contentPosted(get_cat_id($post->category));
				$resp->reload();
			} else {
				$err = join(PHP_EOL, $stm->errorInfo());
				trigger_error($err);
				$resp->notify(
					'Error creating post',
					$err,
					ICONS['bug']
				);
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

		$post = $req->{'update-post'};

		if (
			!isset($post->category, $post->title, $post->author, $post->content, $post->url)
			or !filter_var($post->url, FILTER_VALIDATE_URL, [
				'flags' => FILTER_FLAG_PATH_REQUIRED,
			])
		) {
			$resp->notify(
				'Missing info for post',
				'Please make sure it has a title, author, and content.',
				ICONS['thumbsdown'],
				true
			)->send();
		}

		$pdo = PDO::load(DB_CREDS);
		$pdo->beginTransaction();
		$stm = $pdo->prepare(
			'UPDATE `posts` SET
				`cat-id` = COALESCE(:cat, `cat-id`),
				`title` = COALESCE(:title, `title`),
				`author` = COALESCE(:author, `author`),
				`content` = COALESCE(:content, `content`),
				`draft` = :draft,
				`img` = COALESCE(:img, `img`),
				`keywords` = COALESCE(:keywords, `keywords`),
				`description` = COALESCE(:description, `description`)
			WHERE `url` = :url
			LIMIT 1;'
		);

		try {
			if (! (category_exists($post->category) or make_category($post->category))) {
				$resp->notify(
					'Error creating category',
					'Try an existing category or contact an admin.',
					ICONS['bug'],
					true
				);
			}
			$url = explode('/', rtrim($post->url, '/'));
			$stm->cat = get_cat_id($post->category);
			$stm->title = strip_tags($post->title);
			$stm->author = strip_tags($post->author);
			$stm->draft = isset($post->draft);
			$stm->keywords = isset($post->keywords) ? strip_tags($post->keywords) : null;
			$stm->description = isset($post->description) ? strip_tags($post->description) : null;
			$stm->url = end($url);
			$article_dom = new \DOMDocument();
			libxml_use_internal_errors(true);
			$article_dom->loadHTML("<div>{$post->content}</div>");
			libxml_clear_errors();

			if ($figures = $article_dom->getElementsByTagName('figure')) {
				$picture = new Picture($pdo);
				$user = restore_login();
				$main_img = null;
				foreach ($figures as $figure) {
					if ($figure->hasAttribute('data-image-id')) {
						if (is_null($main_img)) {
							$stm->img = $figure->getAttribute('data-image-id');
						}
						$microdata = $picture->parseFigure($figure);
						if (! empty($microdata)) {
							try {
								$picture->addImage($microdata, $user);
							} catch (\Exception $e) {
								trigger_error($e->getMessage());
							}
						}
						$figure->removeAttribute('itemprop');
						$figure->removeAttribute('itemtype');
						$figure->removeAttribute('itemscope');
						while ($figure->hasChildNodes() and $node = $figure->firstChild) {
							$figure->removeChild($node);
						}
					}
				}
			}

			# Need to get the content out of DOM structured `<html><body><div>$content...`
			$stm->content = $article_dom->saveHTML($article_dom->documentElement->firstChild->firstChild);
			unset($article_dom, $imgs, $img, $path, $id);
			if ($stm->execute() and intval($stm->errorCode()) === 0) {
				$pdo->commit();
				$resp->notify(
					'Received post',
					$post->title,
					ICONS['thumbsup']
				);
				Listener::contentPosted(get_cat_id($post->category));
				$resp->reload();
			} else {
				$err = join(PHP_EOL, $stm->errorInfo());
				throw new \RuntimeException($err);
			}
		} catch (\Throwable $e) {
			$pdo->rollBack();
			trigger_error($e->getMessage());
			$resp->notify(
				'Error updating post',
				$e->getMessage(),
				ICONS['bug'],
				true
			);
		}
		break;

	case 'ccform':
		$user = restore_login();
		$billing = new BillingAddress($req->ccform->billing->getArrayCopy());

		if (!$billing->validate()) {
			$resp->notify(
				'Double check your address',
				'Looks like you missed some info when entering your address',
				ICONS['credit-card'],
				true
			)->focus('#ccform-billing-first-name')->send();
		}

		$pdo = PDO::load(DB_CREDS);
		try {
			$stm = $pdo->prepare(
				'SELECT
					`subscription_rates`.`id`,
					`name`,
					`description`,
					`term`,
					`price`,
					`isLocal` AS `local`,
					`includesPrint` AS `print`,
					`role`,
					`permissions`.`paidArticles` AS `online`,
					`permissions`.`eEdition` AS `pdf`
				FROM `subscription_rates`
				JOIN `permissions` ON `permissions`.`id` = `subscription_rates`.`role`
				WHERE `subscription_rates`.`id` = :id
				LIMIT 1;'
			);
			$stm->id = $req->ccform->subscription;
			$stm->execute();

			$sub = $stm->fetchObject();
			$sub->print  = $sub->print  === '1';
			$sub->online = $sub->online === '1';
			$sub->pdf    = $sub->pdf    === '1';

			// if (
			// 	$sub->print
			// 	and $sub->local
			// 	and ! in_array(intval($req->ccform->billing->zip), LOCAL_ZIPS)
			// ) {
			// 	$resp->notify(
			// 		'You do not qualify for this subscription',
			// 		'Please select from our out of Valley print subscriptions',
			// 		ICONS['credit-card'],
			// 		true
			// 	)->focus('#ccform-subscription')->send();
			// } elseif (
			// 	$sub->print
			// 	and !$sub->local
			// 	and in_array(intval($req->ccform->billing->zip), LOCAL_ZIPS)
			// ) {
			// 	$resp->notify(
			// 		'You do not qualify for this subscription',
			// 		'Please select from our local print subscriptions',
			// 		ICONS['credit-card'],
			// 		true
			// 	)->focus('#ccform-subscription')->send();
			// }

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
					ICONS['credit-card'],
					true
				)->send();
			}
			$items = new Items();
			$items->addItem($item);

			$request->addItems($items);
			$pdo->beginTransaction();

			$subscribe = $pdo->prepare(
				'INSERT INTO `subscribers` (
					`id`,
					`status`,
					`sub_expires`
				) VALUES (
					:id,
					:status,
					:expires
				) ON DUPLICATE KEY UPDATE
					`status`      = :status,
					`sub_expires` = :expires;'
			);

			$expires = new \DateTime($sub->term);
			$subscribe->execute([
				'id'      => $user->id,
				'status'  => $sub->role,
				'expires' => $expires->format($expires::W3C),
			]);
			if ($subscribe->rowCount() === 1) {
				throw new \Exception('There was an error saving your subscription');
			}
			$response = $request();

			if (DEBUG) {
				Console::log([
					'respCode'      => $response->code,
					'authCode'      => $response->authCode,
					'transactionID' => $response->transactionID,
					'messages'      => $response->messages,
					'errors'        => $response->errors,
				]);
				Console::info($request);
			}

			if ($response->code == '1') {
				$pdo->commit();
				$record = $pdo->prepare(
					'INSERT INTO `transactions` (
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
				$resp->remove('#ccform-dialog');

				$resp->notify(
					'Subscription successful',
					$response,
					ICONS['credit-card'],
					true
				);

				if ($sub->print) {
					email(
						['circulation@kvsun.com'],
						"New online print subscription for {$req->ccform->card->name}",
						'',
						['From' => 'no-reply@kvsun.com']
					);
				}
			} else {
				$pdo->rollBack();
				$resp->notify(
					'There was an error processing your subscription',
					$response,
					ICONS['credit-card'],
					true
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

		} catch (\Throwable $e) {
			$pdo->rollBack();
			$resp->notify(
				'There was an error processing your payment',
				$e->getMessage(),
				ICONS['credit-card'],
				true
			);
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
