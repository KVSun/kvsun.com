<?php
namespace KVSun;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\DOM as DOM;

/**
 * Create `<dialog>` & `<form>` for updating user data
 * @param  shgysk8zer0\Login\User          $user User data to update from
 * @return shgysk8zer0\DOM\HTMLElement     `<dialog><form>...</dialog>`
 */
function user_update_form(\shgysk8zer0\Login\User $user)
{
	$dom = new DOM\HTML();
	$dialog = $dom->body->append('dialog', null, [
		'id' => 'update-user-dialog',
	]);
	$dialog->append('br');
	$logout = $dialog->append('button', null, [
		'type' => 'buton',
		'title' => 'Logout',
		'data-request' => 'action=logout',
		'data-confirm' => 'Are you sure you want to logout?',
	]);
	use_icon('sign-out', $logout, ['height' => 32, 'width' => 32]);
	$dialog->append('button', null, [
		'data-delete' => "#{$dialog->id}",
	]);

	$form = $dialog->append('form', null, [
		'name' => 'user-update',
		'action' => '/api.php',
		'method' => 'POST',
	]);

	$form->append('h4', 'Change avatar');
	$form->append('a', null, [
		'href' => 'https://gravatar.com/',
		'target' => '_blank',
	])->append('img', null, [
		'src' => new Core\Gravatar($user->email, 128),
		'height' => 128,
		'width' => 128,
		'alt' => 'Update user image on Gravatar',
		'title' => 'Update user image on Gravatar',
	]);

	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', 'User info');

	$label = $fieldset->append('label', 'Email');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[email]",
		'id' => "{$form->name}-email",
		'type' => 'email',
		'value' => $user->email,
		'placeholder' => 'user@example.com',
		'required' => '',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Name');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[name]",
		'id' => "{$form->name}-name",
		'type' => 'text',
		'value' => isset($user->name) ? $user->name : null,
		'placeholder' => 'Firstname Lastname',
		'pattern' => '^[A-z]+ [A-z]+$',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Phone number');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[tel]",
		'id' => "{$form->name}-tel",
		'type' => 'tel',
		'value' => isset($user->tel) ? $user->tel : null,
		'placeholder' => '1-760-379-1234',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'Twitter URL');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[twitter]",
		'id' => "{$form->name}-twitter",
		'type' => 'url',
		'value' => isset($user->twitter) ? $user->twitter : null,
		'placeholder' => 'https://twitter.com/{USER}',
		'pattern' => '^https://twitter\.com/[\w]{1,32}$',
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$label = $fieldset->append('label', 'G+ URL');
	$input = $fieldset->append('input', null, [
		'name' => "{$form->name}[g+]",
		'id' => "{$form->name}-gplus",
		'type' => 'url',
		'value' => isset($user->{'g+'}) ? $user->{'g+'} : null,
		'placeholder' => 'https://plus.google.com/u/0/+{USER}',
		'pattern' => '^https://plus.google.com/u/0/\+[\w]{1,35}$'
	]);
	$label->for = $input->id;
	$fieldset->append('br');

	$form->append('hr');

	$form->append('button', 'Submit', ['type' => 'submit']);
	$form->append('button', 'reset', ['type' => 'reset']);
	return $dialog;
}

function make_cc_form(DOM\HTMLElement $parent = null, $name = 'ccform')
{
	if (is_null($parent)) {
		$dom = new DOM\HTML();
		$parent = $dom->body;
	}

	$form = $parent->append('form', null, [
		'method' => 'POST',
		'action' => '/api.php',
		'name' => $name,
	]);

	$fieldset = $form->append('fieldset');
	$fieldset->append('legend', 'Choose your subscription');
	$label = $fieldset->append('label', 'Subscription');
	$input = $fieldset->append('select', null, [
		'name' => "{$form->name}[subscription]",
		'id' => "{$form->name}-subscription",
	]);

	$online = $input->append('optgroup', null, ['label' => 'Online subscriptions']);
	$print_local = $input->append('optgroup', null, ['label' => 'Print subscriptions']);
	$e_edition = $input->append('optgroup', null, ['label' => 'E-Edition subscriptions']);
	$print_oov = $input->append('optgroup', null, ['label' => 'Out of Valley print subscriptions']);

	$pdo = Core\PDO::load(\KVSun\DB_CREDS);
	$subs = $pdo(
		'SELECT
			`id`,
			`name`,
			`media`,
			`price`,
			`isLocal`
		FROM `subscription_rates`
		ORDER BY `price` DESC;'
	);

	array_map(function(\stdClass $sub) use (
		$print_local,
		$print_oov,
		$e_edition,
		$online
	)
	{
		$option = new \DOMElement('option', "{$sub->name} [\${$sub->price}]");
		switch($sub->media) {
			case 'online':
				$online->appendChild($option);
				break;

			case 'e-edition':
				$e_edition->appendChild($option);
				break;

			case 'print':
				if ($sub->isLocal) {
					$print_local->appendChild($option);
				} else {
					$print_oov->appendChild($option);
				}
				break;
		}
		$option->setAttribute('value', $sub->id);
	}, $subs);
	$label->for = $input->id;

	$form->importHTMLFile('components/forms/ccform.html');
	$form->importHTMLFile('components/forms/billing.html');

	$form->append('button', 'Submit', ['type' => 'submit']);
	return $form;
}

/**
 * [exception_error_handler description]
 * @param  Int    $severity [description]
 * @param  String $message  [description]
 * @param  String $file     [description]
 * @param  Int    $line     [description]
 * @return Bool             [description]
 */
function exception_error_handler(
	$severity,
	$message,
	$file,
	$line
)
{
	$e = new \Throwable($message, 0, $severity, $file, $line);
	Core\Console::getInstance()->error(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'code'    => $e->getCode(),
		'trace'   => $e->getTrace(),
	]]);
	return true;
}

/**
 * Gets login user from cookie or session
 * @param void
 * @return shgysk8zer0\Login\User [description]
 */
function restore_login()
{
	if (@file_exists(DB_CREDS) and Core\PDO::load(DB_CREDS)->connected) {
		return \shgysk8zer0\Login\User::restore('user', DB_CREDS);
	} else {
		$user = new \stdClass();
		$user->status = array_search('guest', USER_ROLES);
		return $user;
	}
}

function check_role($role = 'admin')
{
	$user = restore_login();
	if (! in_array($role, USER_ROLES)) {
		throw new \InvalidArgumentException("$role is not a valid user role.");
	}
	return isset($user->status) and array_search($role, USER_ROLES) >= $user->status;
}

/**
 * Get transactions for a user by name, email, or username
 * @param  String $user Name, email, or username of user
 * @return Array        Matching transactions + user & subscription info
 */
function get_transactions_for($user)
{
	$transaction = Core\PDO::load(\KVSun\DB_CREDS)->prepare(
		'SELECT
			`user_data`.`name`,
			`users`.`username`,
			`users`.`email`,
			`user_data`.`tel`,
			`transactions`.`date`,
			`subscription_rates`.`name` as `subscription`,
			`subscribers`.`sub_expires` AS `expires`,
			`transactions`.`authCode`,
			`transactions`.`transactionID`
		FROM `transactions`
		JOIN `subscription_rates` ON `subscription_rates`.`id` = `transactions`.`subscriptionID`
		JOIN `user_data` ON `user_data`.`id` = `transactions`.`userID`
		JOIN `users` ON `users`.`id` = `transactions`.`userID`
		JOIN `subscribers` ON `subscribers`.`id` = `transactions`.`userID`
		WHERE `users`.`email` = :user
		OR `users`.`username` = :user
		OR `user_data`.`name` = :user;'
	);

	$transaction->bindParam(':user', $user);
	$transaction->execute();

	return $transaction->fetchAll(\PDO::FETCH_CLASS);
}

function setcookie(
	$name,
	$value,
	$httpOnly = true,
	$path = '/'
)
{
	return \setcookie(
		$name,
		$value,
		strtotime('+1 month'),
		$path,
		$_SERVER['HTTP_HOST'],
		array_key_exists('HTTPS', $_SERVER),
		$httpOnly
	);
}

function make_datalist($name, Array $items, $return_string = true)
{
	$tmp = new \DOMDocument();
	$datalist = $tmp->appendChild($tmp->createElement('datalist'));
	$datalist->setAttribute('id', $name);
	foreach ($items as $item) {
		$option = $datalist->appendChild($tmp->createElement('option'));
		$option->setAttribute('value', $item);
	}

	return $return_string ? $tmp->saveHTML($datalist) : $datalist;
}

/**
 * [use_icon description]
 * @param  String         $icon   [description]
 * @param  DOMHTMLElement $parent [description]
 * @param  array          $attrs  [description]
 * @return [type]                 [description]
 */
function use_icon(
	$icon,
	DOM\HTMLElement $parent,
	Array $attrs = array()
)
{
	$attrs = array_merge([
		'xmlns'       => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
		'version'     => 1.1,
		'height'      => 64,
		'width'       => 64,
	], $attrs);
	$svg = $parent->append('svg', null, $attrs);
	$svg->append('use', null, [
		'xlink:href' => DOMAIN . SPRITES . "#{$icon}"
	]);

	return $svg;
}

function add_main_menu(\DOMElement $parent)
{
	$menu = $parent->append('menu', null, [
		'id' => 'main-menu',
		'type' => 'context',
	]);

	$parent->contextmenu = $menu->id;

	$login = $menu->append('menuitem', null, [
		'label' => 'Login',
		'icon' => DOMAIN . '/images/octicons/lib/svg/sign-in.svg',
		'data-show-modal' => '#login-dialog',
	]);

	$register = $menu->append('menuitem', null, [
		'label' => 'Register',
		'icon' => DOMAIN . '/images/octicons/lib/svg/sign-in.svg',
		'data-show-modal' => '#registration-dialog',
	]);

	$logout = $menu->append('menuitem', null, [
		'label' => 'Sign out',
		'icon' => DOMAIN . '/images/octicons/lib/svg/sign-out.svg',
		'data-request' => 'action=logout',
		'data-confirm' => 'Are you sure you want to logout?'
	]);

	if (check_role('guest')) {
		$login->disabled = '';
		$register->disabled = '';
	} else {
		$logout->disabled = '';
	}

	add_share_menu($menu);
	add_nav_menu($menu);
	return $menu;
}

/**
 * Creates a basic navigation menu for $parent
 * @param  DOMElement   $parent The element to append it to
 * @return DOMElement   The newly created menu
 */
function add_nav_menu(\DOMElement $parent)
{
	return $parent->append('menu', null, [
		'label' => 'Page navigation',
		'id' => 'nav-menu',
	],  [
		['menuitem', null, [
			'label' => 'Top',
			'icon' => DOMAIN . '/images/octicons/lib/svg/arrow-up.svg',
			'data-scroll-to' => 'body > header',
		]],
		['menuitem', null, [
			'label' => 'Bottom',
			'icon' => DOMAIN . '/images/octicons/lib/svg/arrow-down.svg',
			'data-scroll-to' => 'body > footer',
		]],
	]);

	return $menu;
}

function add_share_menu(\DOMElement $parent)
{
	return $parent->append('menu', null, [
		'label' => 'Share page...',
		'type' => 'context',
		'id' => 'share-menu',
	], [
		['menuitem', null, [
			'label' => 'Facebook',
			'icon' => DOMAIN . '/images/logos/Facebook.svg',
			'data-share' => 'facebook',
		]],
		['menuitem', null, [
			'label' => 'Twitter',
			'icon' => DOMAIN . '/images/logos/twitter.svg',
			'data-share' => 'twitter',
		]],
		['menuitem', null, [
			'label' => 'Google+',
			'icon' => DOMAIN . '/images/logos/Google_plus.svg',
			'data-share' => 'g+',
		]],
		['menuitem', null, [
			'label' => 'Linkedin',
			'icon' => DOMAIN . '/images/logos/linkedin.svg',
			'data-share' => 'linkedin',
		]],
		['menuitem', null, [
			'label' => 'Reddit',
			'icon' => DOMAIN . '/images/logos/Reddit.svg',
			'data-share' => 'reddit',
		]],
	]);
}

/**
 * [load description]
 * @param  Array $files  file1, file2, ...
 * @return Array         [description]
 */
function load(...$files)
{
	return array_map(__NAMESPACE__ . '\load_file', $files);
}

/**
 * [load_file description]
 * @param  String $file [description]
 * @param  String $ext  [description]
 * @return mixed        [description]
 */
function load_file($file, $ext = EXT)
{
	static $args = null;
	$url = Core\URL::getInstance();
	$path = explode('/', trim($url->path));
	$path = array_filter($path);
	$path = array_values($path);

	if (empty($path)) {
		// This would be a request for home
		$kvs = new \KVSun\KVSAPI\Home(Core\PDO::load(DB_CREDS), "$url", [
			'news',
			'sports',
			'valley-life',
		]);
	} elseif (count($path) === 1) {
		$kvs = new \KVSun\KVSAPI\Category(Core\PDO::load(DB_CREDS), "$url");
	} else {
		$kvs = new \KVSun\KVSAPI\Article(Core\PDO::load(DB_CREDS), "$url");
	}
	if (is_null($args)) {
		$args = array(
			DOM\HTML::getInstance(),
			Core\PDO::load(DB_CREDS),
			$kvs
		);
	}
	$ret = require_once(COMPONENTS . $file . $ext);

	if (is_callable($ret)) {
		return call_user_func_array($ret, $args);
	} elseif (is_string($ret)) {
		return $ret;
	} else {
		trigger_error("$file did not return a function or string.");
	}
}

/**
 * [append_to_dom description]
 * @param  String          $fname   [description]
 * @param  DOM\HTML\Element  $el    [description]
 * @return DOM\HTML\Element         [description]
 */
function append_to_dom($fname, DOM\HTMLElement $el)
{
	$ext = pathinfo($fname, PATHINFO_EXTENSION);
	if (empty($ext)) {
		$fname .= '.html';
	}
	$html = file_get_contents(COMPONENTS . $fname);
	return $el->importHTML($html);
}

/**
 * [get_path description]
 * @return Array [description]
 */
function get_path()
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}
