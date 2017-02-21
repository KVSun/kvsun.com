<?php
namespace KVSun\Functions;

use \shgysk8zer0\Core\{PDO, Console, Listener, Gravatar, URL, Headers};
use \shgysk8zer0\DOM\{HTML, HTMLElement, RSS};
use \KVSun\KVSAPI\{Home, Category, Article};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};
use \shgysk8zer0\Login\{User};

use const \KVSun\Consts\{
	DEBUG,
	DB_CREDS,
	PASSWD,
	DOMAIN,
	COMPONENTS,
	EXT,
	PAGES_DIR,
	PAGE_COMPONENTS,
	HTML_TEMPLATES,
	SPRITES,
	LOGO,
	LOGO_SIZE
};

/**
 * Builds all the things!
 * @param  Array        $path            URL path as an array
 * @return DOMDocument                   The resulting document build
 */
function build_dom(Array $path = array()): \DOMDocument
{
	if (@file_exists(DB_CREDS) and PDO::load(DB_CREDS)->connected) {
		HTMLElement::$import_path = COMPONENTS;
		$dom = HTML::getInstance();
		if (!empty($path) and file_exists(PAGES_DIR . "{$path[0]}.php")) {
			require PAGES_DIR . "{$path[0]}.php";
			exit();
		}
		// If IE, show update and hide rest of document
		$dom->body->ifIE(
			file_get_contents(COMPONENTS . 'update.html')
			. '<div style="display:none !important;">'
		);

		$dom->body->class = 'flex row wrap';

		array_map([$dom->body, 'importHTMLFile'], HTML_TEMPLATES);

		add_main_menu($dom->body);
		load(...PAGE_COMPONENTS);

		// Close `</div>` created in [if IE]
		$dom->body->ifIE('</div>');

	} else {
		$dom = new \DOMDocument();
		$dom->loadHTMLFile(COMPONENTS . 'install.html');
	}
	Listener::load();
	return $dom;
}

/**
 * Create a `<picture>` inside of a `<figure>` from an array of sources
 * @param  Array      $imgs    Image data, as from `Core\Image::responsiveImagesFromUpload`
 * @param  DOMElement $parent  Parent element to append `<picture>` to
 * @param  String     $by      Who was the photo taken by?
 * @param  String     $caption Photo cutline
 * @return DOMHTMLElement            `<figure><picture>...`
 */
function make_picture(
	Array       $imgs,
	\DOMElement $parent,
	String      $by      = null,
	String      $caption = null
): \DOMElement
{
	$dom = $parent->ownerDocument;
	$figure = $parent->appendChild($dom->createElement('figure'));
	$picture = $figure->appendChild($dom->createElement('picture'));
	if (isset($by)) {
		$figure->appendChild($dom->createElement('cite', $by));
	}
	if (isset($caption)) {
		$cap = $figure->appendChild($dom->createElement('figcaption', $caption));
	}
	foreach($imgs as $format => $img) {
		usort($img, function(Array $src1, Array $src2): Int
		{
			return $src2['width'] <=> $src1['width'];
		});
		$source = $picture->appendChild($dom->createElement('source'));
		$source->setAttribute('type', $format);
		$source->setAttribute('srcset', join(',', array_map(function(Array $src): String
		{
			return "{$src['path']} {$src['width']}w";
		}, $img)));
	}
	$img = $picture->appendChild($dom->createElement('img'));
	$img->setAttribute('src', $imgs['image/jpeg'][0]['path']);
	$img->setAttribute('width', $imgs['image/jpeg'][0]['width']);
	$img->setAttribute('height', $imgs['image/jpeg'][0]['height']);
	$img->setAttribute('alt', '');
	return $picture;
}

/**
 * Post a comment on an article
 * @param  String  $url        URL for post
 * @param  User    $user       User making comment
 * @param  String  $comment    The comment itself
 * @param  boolean $approved   Automatically approve the comment?
 * @param  boolean $allow_html Allow HTML tags in the comment?
 * @return Bool    Whether or not the comment was added to table
 */
function post_comment(
	String  $url,
	User    $user,
	String  $comment,
	Bool    $approved   = false,
	Bool    $allow_html = false
): Bool
{
	try {
		$path = parse_url($url,  PHP_URL_PATH);
		$path = trim($path, '/');
		$path = explode('/', $path, 2);
		$path = array_filter($path);
		list($category, $post) = array_pad($path, 2, null);
		$category = get_cat_id($category);
		$post = get_post_id($post);

		$stm = PDO::load(DB_CREDS)->prepare(
			'INSERT INTO `post_comments` (
				`postID`,
				`catID`,
				`userID`,
				`approved`,
				`text`
			) VALUES (
				:post,
				:cat,
				:user,
				:approved,
				:comment
			);'
		);
		// $comment = strip_tags($comment);
		// $comment = nl2br($comment);
		// if (!$allow_html) {
		// 	$comment = strip_tags($comment);
			if (!$allow_html) {
				$comment = strip_tags($comment);
			}
			$comment = preg_replace('/\r|\n|\t/', null, nl2br($comment));
		// } else {
		// 	$comment = html_entity_decode($comment, ENT_HTML5, 'UTF-8');
		// }
		$stm->bindParam(':post', $post);
		$stm->bindParam(':cat', $category);
		$stm->bindParam(':user', $user->id);
		$stm->bindParam(':approved', $approved);
		$stm->bindParam(':comment', $comment);
		$stm->execute();
		if (intval($stm->errorCode()) !== 0) {
			throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
		} else {
			return true;
		}
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
		return false;
	}
}

/**
 * Gets all comments with associated user/post/category data
 * @return Array An Array of comments
 */
function get_comments(): Array
{
	return (PDO::load(DB_CREDS))(
		'SELECT
			`post_comments`.`id` AS `ID`,
			`post_comments`.`text` AS `comment`,
			`post_comments`.`created`,
			`post_comments`.`approved`,
			`users`.`username`,
			`users`.`email`,
			`user_data`.`name`,
			`posts`.`title` AS `Article`,
			`posts`.`url` AS `postURL`,
			`categories`.`url-name` AS `catURL`,
			`categories`.`name` AS `category`
		FROM `post_comments`
		JOIN `users` ON `users`.`id` = `post_comments`.`userID`
		JOIN `user_data` ON `user_data`.`id` = `post_comments`.`userID`
		JOIN `posts` ON `posts`.`id` = `post_comments`.`postID`
		JOIN `categories` ON `categories`.`id` = `post_comments`.`catID`;'
	);
}

/**
 * Delete comments by ID
 * @param  Int  $ids A list of IDs to delete
 * @return Bool      Whether or not they were deleted
 * @example delete_comments(1, 2, ...);
 * @example delete_comments(...$ids);
 */
function delete_comments(Int ...$ids): Bool
{
	$pdo = PDO::load(DB_CREDS);
	$pdo->beginTransaction();
	$stm = $pdo->prepare('DELETE FROM `post_comments` WHERE `id` = :id;');
	$result = true;
	try {
		foreach ($ids as $id) {
			$stm->bindParam(':id', $id);
			$stm->execute();
			if (intval($stm->errorCode()) !== 0) {
				throw new \Exception('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
			}
		}
		$pdo->commit();
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
		$result = false;
	} finally {
		return $result;
	}
}

function get_post_id(String $post): Int
{
	static $q;
	if (is_null($q)) {
		$q = PDO::load(DB_CREDS)->prepare(
			'SELECT `id` FROM `posts`
			WHERE `title` = :post
			OR `url` = :post
			LIMIT 1;'
		);
	}
	$q->bindParam(':post', $post);
	$q->execute();
	$match = $q->fetchObject();
	return $match->id ?? 0;
}

/**
 * Gets a category's ID from URL or name
 * @param  String $cat URL or name of category
 * @return Int         It's ID
 */
function get_cat_id(String $cat): Int
{
	static $q;
	if (is_null($q)) {
		$q = PDO::load(DB_CREDS)->prepare(
			'SELECT `id`
			FROM `categories`
			WHERE `name` = :cat
			OR `url-name` = :cat
			LIMIT 1;'
		);
	}
	$q->bindParam(':cat', $cat);
	$q->execute();
	$match = $q->fetchObject();
	return $match->id ?? 0;
}

/**
 * Get an array of categories with their names and URLs
 * @return Array Array of categories
 */
function get_categories(String $mapping = null): Array
{
	static $cats;
	if (!is_array($cats)) {
		try {
			$pdo = PDO::load(DB_CREDS);
			$cats = $pdo(
				'SELECT
					`url-name` AS `url`,
					`name`
				FROM `categories`
				ORDER BY `sort` ASC;'
			);
		} catch(\Throwable $e) {
			trigger_error($e->getMessage());
			$cats = [];
		}
	}
	return isset($mapping) ? array_map(function(\stdClass $cat) use ($mapping): String
	{
		return $cat->{$mapping};
	}, $cats) : $cats;
}

/**
 * Create a new category
 * @param  String  $name   Name of category
 * @param  integer $sort   Sort order
 * @param  String  $parent Optional parent category
 * @return Bool            Whether or not the category was created
 */
function make_category(String $name, Int $sort = 12, String $parent = null): Bool
{
	$stm = PDO::load(DB_CREDS)->prepare(
		'INSERT INTO `categories` (
			`name`,
			`sort`,
			`parent`,
			`url-name`
		) VALUES (
			:name,
			:sort,
			:parent,
			:url
		);'
	);

	$stm->name = $name;
	$stm->sort = $sort;
	$stm->parent = $parent;
	$stm->url = str_replace(' ', '-', strtolower($name));
	$stm->execute();
	if (intval($stm->errorCode()) !== 0) {
		trigger_error('SQL Error: '. join(PHP_EOL, $stm->errorInfo()));
		return false;
	} else {
		return true;
	}
}

/**
 * Check if a category exists according to its URL
 * @param  String $query Category URL
 * @return Bool          Whether or not it exists
 */
function category_exists(String $query): Bool
{
	$categories = get_categories();
	$exists     = false;
	$query      = trim(strtolower($query));

	foreach($categories as $category) {
		if (
			trim(strtolower($category->url)) === $query
			or trim(strtolower($category->name)) === $query
		) {
			$exists = true;
			break;
		}
	}
	return $exists;
}

/**
 * Get posts in category
 * @param  String  $cat   Category URL
 * @param  integer $limit Max number of results
 * @return Array          Array of posts. Empty array on failure
 */
function get_category(String $cat, Int $limit = 20): Array
{
	try {
		$pdo = PDO::load(DB_CREDS);
		$stm = $pdo->prepare(
			"SELECT
			`posts`.`title`,
			`posts`.`author`,
			`posts`.`content`,
			`posts`.`posted`,
			`posts`.`updated`,
			`posts`.`keywords`,
			`posts`.`description`,
			`posts`.`url`,
			`categories`.`name` AS `category`,
			`categories`.`url-name` AS `catURL`
			FROM `categories`
			JOIN `posts` ON `categories`.`id` = `posts`.`cat-id`
			WHERE `categories`.`url-name` = :cat
			ORDER BY `updated` DESC
			LIMIT {$limit};"
		);
		$stm->execute(['cat' => $cat]);
		return $stm->getResults() ?? [];
	} catch(\Throwable $e) {
		trigger_error($e->getMessage());
		return [];
	}
}
/**
 * Create `<dialog>` & `<form>` for updating user data
 * @param  shgysk8zer0\Login\User          $user User data to update from
 * @return shgysk8zer0\DOM\HTMLElement     `<dialog><form>...</dialog>`
 */
function user_update_form(User $user): \DOMElement
{
	$dom = new HTML();
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
		'src' => new Gravatar($user->email, 128),
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

function make_cc_form(HTMLElement $parent = null, String $name = 'ccform'): \DOMElement
{
	if (is_null($parent)) {
		$dom = new HTML();
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

	$pdo = PDO::load(DB_CREDS);
	try {
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
	} catch(\Throwable $e) {
		trigger_error($e->getMessage());
	}

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
	Int    $severity,
	String $message,
	String $file,
	Int    $line
): Bool
{
	$e = new \ErrorException($message, 0, $severity, $file, $line);
	Console::getInstance()->error(['error' => [
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
 * @return shgysk8zer0\Login\User Or stdClass when no Database connection
 */
function restore_login()
{
	if (@file_exists(DB_CREDS) and PDO::load(DB_CREDS)->connected) {
		return User::restore('user', DB_CREDS, PASSWD);
	} else {
		$user = new \stdClass();
		$user->status = array_search('guest', USER_ROLES);
		return $user;
	}
}

/**
 * Provides quick access to `\shgysk8zer0\Login\User::hasPermission`
 * @param  String $actions Permissions to check
 * @return Bool            Whether or not it is allowed
 */
function user_can(String ...$actions): Bool
{
	$user = restore_login();
	$allowed = true;
	foreach ($actions as $action) {
		if (! $user->hasPermission($action)) {
			$allowed = false;
			break;
		}
	}
	return $allowed;
}

/**
 * Get transactions for a user by name, email, or username
 * @param  String $user Name, email, or username of user
 * @return Array        Matching transactions + user & subscription info
 */
function get_transactions_for(String $user): \stdClass
{
	$transaction = PDO::load(DB_CREDS)->prepare(
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
	String $name,
	String $value,
	String $expires  = '+1 month',
	Bool   $httpOnly = true,
	String $path     = '/'
): Bool
{
	return \setcookie(
		$name,
		$value,
		strtotime($expires),
		$path,
		$_SERVER['HTTP_HOST'],
		array_key_exists('HTTPS', $_SERVER),
		$httpOnly
	);
}

function make_datalist(String $name, Array $items, Bool $return_string = true)
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
	String          $icon,
	HTMLElement $parent,
	Array           $attrs = array()
): \DOMElement
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

function add_main_menu(\DOMElement $parent): \DOMElement
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

	$user = User::load(DB_CREDS);
	if (isset($user->status)) {
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
function add_nav_menu(\DOMElement $parent): \DOMElement
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

function add_share_menu(\DOMElement $parent): \DOMElement
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
function load(String ...$files): Array
{
	return array_map(__NAMESPACE__ . '\load_file', $files);
}

/**
 * [load_file description]
 * @param  String $file [description]
 * @param  String $ext  [description]
 * @return mixed        [description]
 */
function load_file(String $file, String $ext = EXT)
{
	static $args = null;
	$url = URL::getInstance();
	$path = explode('/', trim($url->path));
	$path = array_filter($path);
	$path = array_values($path);

	if (empty($path)) {
		// This would be a request for home
		$kvs = new Home(PDO::load(DB_CREDS), "$url", get_categories('url'));
	} elseif (count($path) === 1) {
		$kvs = new Category(PDO::load(DB_CREDS), "$url");
	} else {
		$kvs = new Article(PDO::load(DB_CREDS), "$url");
	}
	if (is_null($args)) {
		$args = array(
			HTML::getInstance(),
			PDO::load(DB_CREDS),
			$kvs
		);
	}

	try {
		$ret = require_once(COMPONENTS . $file . $ext);

		if (is_callable($ret)) {
			return call_user_func_array($ret, $args);
		} elseif (is_string($ret)) {
			return $ret;
		} else {
			trigger_error("$file did not return a function or string.");
		}
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
	}
}

/**
 * [append_to_dom description]
 * @param  String          $fname   [description]
 * @param  DOM\HTML\Element  $el    [description]
 * @return DOM\HTML\Element         [description]
 */
function append_to_dom(String $fname, HTMLElement $el)
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
function get_path(): Array
{
	static $path = null;
	if (is_null($path)) {
		$path = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));
	}
	return $path;
}

/**
 * Create an RSS document for $category
 * @param  String $category Category URL
 * @return RSS             An RSS/XML document
 */
function build_rss(String $category): RSS
{
	try {
		$head        = PDO::load(DB_CREDS)->nameValue('head');
		$img         = new \stdClass;
		$img->url    = LOGO;
		$img->height = LOGO_SIZE;
		$img->width  = LOGO_SIZE;

		$rss = new RSS(
			ucwords("{$head->title} | {$category} Feed"),
			$head->description ?? ucwords("RSS feed for {$head->title}"),
			'Newspaper',
			$img,
			DOMAIN,
			$_SERVER['SERVER_ADMIN'],
			'editor@kvsun.com',
			RSS::LANGUAGE,
			sprintf('Copyright %d, %s', date('Y'), $head->title)
		);

		$articles = get_category($category);

		if (empty($articles)) {
			http_response_code(HTTP::NO_CONTENT);
		} else {
			foreach ($articles as $article) {
				$article->posted = new \DateTime($article->posted);
				$rss->addItem($article);
			}
		}
	} catch (\Throwable $e) {
		http_response_code(HTTP::INTERNAL_SERVER_ERROR);
		trigger_error($e->getMessage());
	} finally {
		return $rss;
	}
}
