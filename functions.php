<?php
namespace KVSun\Functions;

use \shgysk8zer0\Core\{PDO, Console, Listener, Gravatar, URL, Headers, FormData};
use \shgysk8zer0\DOM\{HTML, HTMLElement, RSS};
use \KVSun\KVSAPI\{
	Home,
	Category,
	Article,
	Classifieds,
	Picture,
	Abstracts\Content as KVSAPI
};
use \shgysk8zer0\Core_API\{Abstracts\HTTPStatusCodes as HTTP};
use \shgysk8zer0\Login\{User};
use \shgysk8zer0\PHPCrypt\{PublicKey, PrivateKey, KeyPair, AES};

use \SplFileObject as File;

use const \KVSun\Consts\{
	DEBUG,
	DB_CREDS,
	PASSWD,
	PUBLIC_KEY,
	PRIVATE_KEY,
	DOMAIN,
	ICONS,
	COMPONENTS,
	EXT,
	PAGES_DIR,
	PAGE_COMPONENTS,
	HTML_TEMPLATES,
	SPRITES,
	LOGO,
	LOGO_SIZE,
	DATE_FORMAT,
	DATETIME_FORMAT,
	PASSWORD_RESET_VALID,
	CRLF
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
 * Get Page content from URL
 * @param  URL    $url Instance of URL class
 * @return KVSAPI      Article, Category, Classifieds, etc.
 */
function get_page(URL $url): KVSAPI
{
	$path = explode('/', trim($url->path));
	$path = array_filter($path);
	$path = array_values($path);
	$pdo  = PDO::load(DB_CREDS);

	if (empty($path)) {
		// This would be a request for home
		// $categories = \KVSun\get_categories();
		$page = new Home($pdo, "$url", get_categories('url'));
	} elseif (count($path) === 1) {
		switch ($path[0]) {
			case 'classifieds':
				$page = new Classifieds($pdo, '../Classifieds');
				break;

			default:
				$page = new Category($pdo, "$url");
		}
	} elseif (count($path) === 2) {
		$page = new Article($pdo, "$url");
	}
	return $page;
}

/**
 * Wrapper function for `mail` as HTML
 * @param  Array       $to      ["user1@domain.com", ...]
 * @param  String      $subject Subject of the email to be sent
 * @param  DOMDocuemnt $message Body of the email as a DOMDocuemnt
 * @param  array       $headers ['From' => 'admin@domain.com', ...]
 * @return Bool                 Whether or not the email sent
 */
function html_email(
	Array        $to,
	String       $subject,
	\DOMDocument $message,
	Array        $headers = array()
): Bool
{
	$encoding = $messsage->encoding ?? 'utf-8';
	$headers['Content-Type'] = "text/html;charset={$encoding}";
	return email($to, $subject, $message->saveHTML(), $headers);
}

/**
 * Wrapper function for `mail`
 * @param  Array  $to      ["user1@domain.com", ...]
 * @param  String $subject Subject of the email to be sent
 * @param  String $message Body of the email
 * @param  array  $headers ['From' => 'admin@domain.com', ...]
 * @return Bool            Whether or not the email sent
 */
function email(
	Array  $to,
	String $subject,
	String $message,
	Array  $headers = array()
): Bool
{
	$headers = array_map(function(String $name, String $value): String
	{
		return "{$name}: {$value}";
	}, array_keys($headers), array_values($headers));
	$message = str_replace(PHP_EOL, CRLF, $message);
	$message = wordwrap($message, 70, CRLF);
	return mail(join(', ', $to), $subject, $message, join(CRLF, $headers));
}

/**
 * Custom mail function using cURL and cryptographic signature for authentication
 * @param  String $to                    Receiver, or receivers of the mail
 * @param  String $subject               Subject of the email to be sent
 * @param  String $message               Message to be sent (use \r\n)
 * @param  string $additional_headers    Optional headers: e.g. "From: user@domain.com" use \r\n
 * @param  string $additional_paramaters Pass additional flags as command line options
 * @return Bool                          Whether or not it sent
 * @see https://secure.php.net/manual/en/function.mail.php
 * @todo Remove once email is working properly
 */
function mail(
	String $to,
	String $subject,
	String $message,
	String $additional_headers    = '',
	String $additional_paramaters = ''
): Bool
{
	try {
		$sent    = true;
		$url     = new URL('http://kvsun.com:8888/mail.php');
		$ch      = curl_init($url);
		$time    = new \DateTime();
		$private = PrivateKey::importFromFile(PRIVATE_KEY, PASSWD);
		$email   = [
			'to'      => $to,
			'subject' => $subject,
			'message' => $message,
			'headers' => $additional_headers,
			'params'  => $additional_paramaters,
			'sent'    => $time->format(\Datetime::W3C),
		];
		$email['sig'] = $private->sign(json_encode($email));
		if (DEBUG) {
			return true;
		}

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_FRESH_CONNECT  => true,
			CURLOPT_POST           => true,
			CURLOPT_PORT           => $url->port,
			CURLOPT_POSTFIELDS     => $email,
		]);

		if (curl_exec($ch)) {
			$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			if ($status !== HTTP::OK) {
				throw new \Exception("<{$url}> {$status}");
			}
		} else {
			throw new \RuntimeException(curl_error($ch));
		}
	} catch (\Throwable $e) {
		$sent = false;
		trigger_error($e->getMessage());
	} finally {
		curl_close($ch);
		return $sent;
	}
}

/**
 * Send password reset emails
 * @param  User $user The user attempting to reset password
 * @return Bool       Whether or not the email sent
 */
function password_reset_email(User $user): Bool
{
	if (isset($user->email, $user->name, $user->username)) {
		$dom    = new HTML();
		$date   = new \DateTime();
		$url    = new URL(DOMAIN);
		$key    = PrivateKey::importFromFile(PRIVATE_KEY, PASSWD);

		$url->path = 'password_reset.php';
		$url->query->user = $user->username;
		$url->query->time = $date->getTimestamp();
		$url->query->token = urlencode($key->sign(json_encode([
			'user' => $user->username,
			'time' => $date->format(DATETIME_FORMAT),
		])));
		$expires = clone($date);
		$expires->modify(PASSWORD_RESET_VALID);

		$dom->body->append(
			'h2',
			"A password reset request has been requested for {$user->username} on "
		)->append('a', DOMAIN, ['href' => DOMAIN]);

		$dom->body->append('br');
		$dom->body->append('p', 'If you did not request a password reset, simply ignore this email.');
		$dom->body->append('br');
		$p = $dom->body->append('p');
		$p->append('span', 'Otherwise, click ');
		$link = $p->append('a', 'here');
		$p->append('span', ' to reset your password');
		$dom->body->append('br');
		$dom->body->append('p', "This link will expire on {$expires->format(DATE_FORMAT)}");
		$link->href = $url;

		return html_email(
			["{$user->name} <{$user->email}>"],
			'Password reset',
			$dom,
			['From' => 'no-reply@kvsun.com']
		);
	} else {
		return false;
	}
}

/**
 * Modifies a date to be the date of the most recent publication
 * @param  DateTime $date Date to modify
 * @return DateTime       Modified date
 */
function get_pub_date(\DateTime $date = null): \DateTime
{
	if (is_null($date)) {
		$date = new \DateTime('now');
	} else {
		$date = clone($date);
	}

	$dow = intval($date->format('N'));

	// If $dow !== 3 ('Wednesday'), modify to be closest Wednesday
	if ($dow !== 3) {
		$date->modify(3 - $dow . ' days');
	}

	return $date;
}

/**
 * Appends an E-Edition link to $parent element
 * @param  HTMLElement         $parent Element to append to
 * @param  Array               $attrs  Array of attributes to set on link
 * @param  DateTime            $date   Optional date to create link for
 * @return HTMLElement         E-Edition link element
 */
function add_e_edition(
	HTMLElement $parent = null,
	Array       $attrs  = array(),
	\DateTime   $date   = null
): HTMLElement
{
	if (is_null($parent)) {
		$dom = new HTML();
		$parent = $dom->body;
	}

	if (is_null($date)) {
		$date = new \DateTime('now');
	}

	$url = new URL('https://cloud.kvsun.com/s/W3Dfy1RkyAzvRAO');
	$url->query->path = get_pub_date($date)->format('Y/m/d');

	$add = $parent->append('a', null, array_merge($attrs, [
		'href'  => $url,
		'title' => 'E-Edition',
		'id'    => 'E-Edition-link',
	]));
	$add->append('span', 'E-Edition&nbsp;', ['class' => 'desktop-only']);
	use_icon('section-e-edition', $add, ['class' => 'icon']);
	return $add;
}

/**
 * Create or update a post
 * @param  FormData $post Data for post submitted by form
 * @param  PDO      $pdo  Database instance
 * @return Bool           Whether or not the post was created / updated
 */
function add_post(FormData $post, PDO $pdo): Bool
{
	if (! isset($post->author, $post->title, $post->content, $post->category)) {
		return false;
	}

	$pdo->beginTransaction();
	$stm = $pdo->prepare(
		'INSERT INTO `posts` (
			`sort`,
			`cat-id`,
			`title`,
			`author`,
			`content`,
			`posted`,
			`updated`,
			`draft`,
			`isFree`,
			`url`,
			`img`,
			`posted_by`,
			`keywords`,
			`description`
		) VALUES (
			:sort,
			:cat,
			:title,
			:author,
			:content,
			CURRENT_TIMESTAMP,
			CURRENT_TIMESTAMP,
			:draft,
			:free,
			:url,
			:img,
			:posted,
			:keywords,
			:description
		) ON DUPLICATE KEY UPDATE
			`sort`        = COALESCE(:sort,        `sort`),
			`cat-id`      = COALESCE(:cat,         `cat-id`),
			`title`       = COALESCE(:title,       `title`),
			`author`      = COALESCE(:author,      `author`),
			`content`     = COALESCE(:content,     `content`),
			`updated`     = CURRENT_TIMESTAMP,
			`draft`       = :draft,
			`isFree`      = :free,
			`img`         = :img,
			`keywords`    = COALESCE(:keywords,    `keywords`),
			`description` = COALESCE(:description, `description`);'
	);
	try {
		if (! (category_exists($post->category) or make_category($post->category))) {
			return false;
		}
		$user = restore_login();
		if (isset($post->url) and filter_var($post->url, FILTER_VALIDATE_URL, [
			'flags' => FILTER_FLAG_PATH_REQUIRED,
		])) {
			$url = $url = explode('/', trim($post->url, '/'));
			$url = end($url);
		} else {
			$url = strtolower(preg_replace(
				'/[^A-z\d\-]/',
				null,
				str_replace([' ', '^'], ['-', null], $post->title)
			));
		}
		$stm->title       = strip_tags($post->title);
		$stm->sort        = $post->sort ?? 1;
		$stm->cat         = get_cat_id($post->category);
		$stm->author      = strip_tags($post->author);
		$stm->draft       = isset($post->draft) and $user->hasPermission('skipApproval');
		$stm->free        = isset($post->free);
		$stm->url         = trim($url, '/');
		$stm->posted      = $user->id;
		$stm->keywords    = $post->keywords ?? null;
		$stm->description = $post->description ?? null;

		$article_dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$article_dom->loadHTML("<div>$post->content</div>");
		libxml_clear_errors();

		if ($figures = $article_dom->getElementsByTagName('figure')) {
			$picture = new Picture($pdo);
			$main_img = null;
			foreach ($figures as $figure) {
				if ($figure->hasAttribute('data-image-id')) {
					if (is_null($main_img)) {
						$main_img = $figure->getAttribute('data-image-id');
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
		$stm->img = $main_img;
		# Need to get the content out of DOM structured `<html><body><div>$content...`
		$stm->content = $article_dom->saveHTML($article_dom->documentElement->firstChild->firstChild);

		unset($article_dom, $imgs, $img, $id, $url);

		if ($stm->execute() and intval($stm->errorCode()) === 0) {
			$pdo->commit();
			return true;
		} else {
			throw new \RuntimeException(join(PHP_EOL, $stm->errorInfo()));
		}
	} catch (\Throwable $e) {
		trigger_error($e->getMessage());
		return false;
	}
}

/**
 * Get an array of User role names/ids
 * @return Array [{name: $name, id: $id}, ...]
 */
function get_user_roles(): Array
{
	$pdo = PDO::load(DB_CREDS);
	return $pdo('SELECT `roleName` as `name`, `id` FROM `permissions`');
}

/**
 * Get a User role name from its ID
 * @param  Int    $id Role ID
 * @return String     Role name
 */
function get_role_name(Int $id): String
{
	$pdo = PDO::load(DB_CREDS);
	$stm = $pdo->prepare(
		'SELECT `roleName`
		FROM `permissions`
		WHERE `id` = :id
		LIMIT 1;'
	);
	$stm->bindParam('id', $id);
	$stm->execute();
	$role = $stm->fetchObject() ?? new \stdClass();
	return $role->roleName ?? '';
}

/**
 * Get a user role ID from its name
 * @param  String $role Role name
 * @return Int          Role ID
 */
function get_role_id(String $role): Int
{
	$pdo = PDO::load(DB_CREDS);
	$stm = $pdo->prepare(
		'SELECT `id`
		FROM `permissions`
		WHERE `roleName` = :role
		LIMIT 1;'
	);
	$stm->bindParam('role', $role);
	$stm->execute();
	$role = $stm->fetchObject() ?? new \stdClass();
	return $role->id ?? 0;
}

/**
 * Create or update an image using an array of data
 * @param  Array $data Image data
 * @return Int         The inserted id
 * @todo Make this actually do what it is supposed to do
 */
function set_img(Array $data): Int
{
	static $stm;
	if (is_null($stm)) {
		$stm = PDO::load(DB_CREDS)->prepare(
			'INSERT INTO `images` (
				`path`,
				`fileFormat`,
				`contentSize`,
				`height`,
				`width`,
				`creator`,
				`caption`,
				`alt`,
				`uploadedBy`
			) VALUES (
				:path,
				:format,
				:size,
				:height,
				:width,
				:creator,
				:caption,
				:alt,
				:uploader
			) ON DUPLICATE KEY UPDATE
			SET `caption` = :caption,
				`alt` = COALESCE(:alt, `alt`),
				`uploadedBy` = COALESCE(:uploader, `uploadedBy`);'
		);
	}
	return 0;
}
/**
 * Get an image id from its source / path
 * @param  String $src "/path/to/image"
 * @return Int         ID in `images` table
 */
function get_img_id(String $src): Int
{
	static $stm;
	if (is_null($stm)) {
		$stm = PDO::load(DB_CREDS)->prepare(
			'SELECT `id` FROM `images` WHERE `path` = :path LIMIT 1;'
		);
	}
	$stm->bindParam(':path', $src);
	$stm->execute();
	$img = $stm->fetchObject() ?? new \stdClass();
	return $img->id ?? 0;
}

/**
 * Get the path to an image from its ID
 * @param  Int    $id ID in `images` table
 * @return String     "/path/to/image"
 */
function get_img_path(Int $id): String
{
	static $stm;
	if (is_null($stm)) {
		$stm = PDO::load(DB_CREDS)->prepare(
			'SELECT `path` FROM `images` WHERE `id` = :id LIMIT 1;'
		);
	}
	$stm->bindParam(':id', $id);
	$stm->execute();
	$img = $stm->fetchObject() ?? new \stdClass();
	return $img->path ?? '';
}

/**
 * Gets all data from `images` table from an ID
 * @param  Int       $id Image's ID
 * @return stdClass     {"path": $path, ...}
 */
function get_img(Int $id): \stdClass
{
	static $stm;
	if (is_null($stm)) {
		$stm = PDO::load(DB_CREDS)->prepare(
			'SELECT * FROM `images` WHERE `id` = :id LIMIT 1;'
		);
	}
	$stm->bindParam('id', $id);
	$stm->execute();
	if ($img = $stm->fetchObject()) {
		return $img;
	} else {
		return new \stdClass();
	}
}

/**
 * Retrieves all images created from a parent images as a multi-dimensional array
 * @param  Int   $id Parent image ID
 * @return Array     [$mime => ['width', 'height', 'filesize', 'path', 'format']]
 */
function get_srcset(Int $id): Array
{
	static $srcset_stm = null;
	if (is_null($srcset_stm)) {
		$pdo = PDO::load(DB_CREDS);
		$srcset_stm = $pdo->prepare('SELECT * FROM `srcset` WHERE `parentID` = :id;');
	}
	$srcset_stm->id = $id;
	$srcset_stm->execute();
	$imgs = $srcset_stm->fetchAll(PDO::FETCH_CLASS);
	Console::table($imgs);

	return array_reduce($imgs, function(Array $carry, \stdClass $img): Array
	{
		if (! array_key_exists($img->format, $carry)) {
			$carry[$img->format] = [];
		}
		unset($img->parentId);
		$carry[$img->format][] = get_object_vars($img);
		return $carry;
	}, []);
}

/**
 * Create a `<figure>` & `<picture>` using image data from database
 * @param  HTMLElement $parent Element to append to
 * @param  Int         $id     Image ID
 * @return HTMLElement         Element with `<figure>` appended
 */
function get_picture(HTMLElement $parent, \stdClass $img): Bool
{
	if (! isset($img->id) or $img->id !== 0) {
		$srcset = get_srcset($img->id);
		make_picture($srcset, $parent, $img->creator, $img->caption, $img);
		return true;
	} else {
		return false;
	}
}

/**
 * Creates a `<dialog>` and appends it to optional $parent
 * @param  String      $id     The HTML ID attribute to set
 * @param  HTMLElement $parent Optional parent element
 * @param  Arary       $attrs  An array of additional attributes to set on `<dialog>`
 * @return HTMLElement         The `<dialog>`
 */
function make_dialog(
	String      $id,
	HTMLElement $parent = null,
	Array       $attrs = array()
): HTMLElement
{
	// Assume that, of there is not a parent element, the dialog is to be
	// deleted rather than closed.
	if (is_null($parent)) {
		$dom       = new HTML();
		$parent    = $dom->body;
		$data_attr = 'data-delete';
	} else {
		$data_attr = 'data-close';
	}
	$attrs['id'] = $id;
	$dialog = $parent->append('dialog', null, $attrs);
	$dialog->append('nav')->append('button', null, [
		'type'     => 'button',
		$data_attr => "#{$dialog->id}",
	]);
	$dialog->append('hr');
	return $dialog;
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
	HTMLElement $parent,
	String      $by       = null,
	String      $caption  = null,
	\stdClass   $dflt_img = null
): HTMLElement
{
	$dom = $parent->ownerDocument;
	$figure = $parent->append('figure', null, [
		'itemprop' => 'image',
		'itemtype' => 'http://schema.org/ImageObject',
		'itemscope' => '',
	]);
	$picture = $figure->append('picture');
	if (isset($by) or isset($caption)) {
		$cap = $figure->append('figcaption');
		if (isset($by)) {
			$cap->append('cite', null, [
				'itemprop' => 'creator',
				'itemtype' => 'http://schema.org/Person',
				'itemscope' => ''
			], [
				['b', 'Photo by&nbsp;'],
				['b', $by, ['itemprop' => 'name']],
			]);
		}
		if (isset($caption)) {
			$cap->append('blockquote', $caption, [
				'itemprop' => 'caption',
			]);
		}
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
	if (isset(
		$dflt_img,
		$dflt_img->src,
		$dflt_img->height,
		$dflt_img->width
	)) {
		$img = $picture->append('img', null, [
			'src'      => $dflt_img->src,
			'width'    => $dflt_img->width,
			'height'   => $dflt_img->height,
			'alt'      => $dflt_img->alt ?? null,
			'itemprop' => 'url',
		]);
	} else {
		$img = $picture->append('img', null, [
			'src'      => $imgs['image/jpeg'][0]['path'],
			'width'    => $imgs['image/jpeg'][0]['width'],
			'height'   => $imgs['image/jpeg'][0]['height'],
			'itemprop' => 'url',
		]);
	}
	$figure->append('meta', null, [
		'itemprop' => 'width',
		'content'  => $img->width,
	]);
	$figure->append('meta', null, [
		'itemprop' => 'height',
		'content'  => $img->height,
	]);
	$figure->append('meta', null, [
		'itemprop' => 'fileFormat',
		'content'  => 'image/jpeg',
	]);
	$figure->append('meta', null, [
		'itemprop' => 'uploadDate',
		'content'  => date(\DateTime::W3C),
	]);
	$figure->append('meta', null, [
		'itemprop' => 'contentSize',
		'content'  => (filesize(__DIR__ . $img->src) / 1024) . 'kB',
	]);
	return $figure;
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
				`posts`.`isFree`,
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
	$dialog = make_dialog('update-user-dialog');
	$logout = $dialog->getElementsByTagName('nav')->item(0)->append('button', null, [
		'type' => 'buton',
		'title' => 'Logout',
		'class' => 'icon',
		'data-request' => 'action=logout',
		'data-confirm' => 'Are you sure you want to logout?',
	]);
	use_icon('sign-out', $logout, ['height' => 32, 'width' => 32]);

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

	$pdo = PDO::load(DB_CREDS);
	try {
		$subs = $pdo(
			'SELECT
			`id`,
			`name`,
			`term`,
			`price`,
			`isLocal`
			FROM `subscription_rates`
			WHERE `isUpgrade` = 0
			ORDER BY `price` DESC;'
		);
	} catch(\Throwable $e) {
		trigger_error($e->getMessage());
	}

	$groups = [];

	usort($subs, function(\stdClass $sub1, \stdClass $sub2): Int
	{
		return new \DateTime($sub2->term) <=> new \DateTime($sub1->term);
	});

	array_map(function(\stdClass $sub) use (&$groups, $input)
	{
		if (! array_key_exists($sub->term, $groups)) {
			$groups[$sub->term] = new \DOMElement('optgroup');
			$input->appendChild($groups[$sub->term]);
			$groups[$sub->term]->setAttribute('label', $sub->term);
		}
		$option = new \DOMElement('option', "{$sub->name} [\${$sub->price}]");
		$groups[$sub->term]->appendChild($option);
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
		$user->status = get_role_id('guest');
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
 * Reads a CSV file containing SVG sprites and returns [$name => $path, ...]
 * @param  String $icon_csv The file to read from
 * @return Array            [$name => $path, ...]
 */
function get_icons(String $icon_csv): Array
{
	$icons = [];
	$csv = new File($icon_csv);
	$csv->setFlags(File::READ_CSV | File::SKIP_EMPTY | File::DROP_NEW_LINE);
	while ($csv->valid()) {
		list($key, $value) = $csv->fgetcsv();
		$icons[$key] = $value;
		$csv->next();
	}
	return $icons;
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
		'icon' => DOMAIN . ICONS['sign-in'],
		'data-show-modal' => '#login-dialog',
	]);

	$register = $menu->append('menuitem', null, [
		'label' => 'Register',
		'icon' => DOMAIN . ICONS['subscribe'],
		'data-show-modal' => '#registration-dialog',
	]);

	$logout = $menu->append('menuitem', null, [
		'label' => 'Sign out',
		'icon' => DOMAIN . ICONS['sign-out'],
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
			'icon' => DOMAIN . ICONS['arrow-up'],
			'data-scroll-to' => 'body > header',
		]],
		['menuitem', null, [
			'label' => 'Bottom',
			'icon' => DOMAIN . ICONS['arrow-down'],
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
			'icon' => DOMAIN . ICONS['facebook'],
			'data-share' => 'facebook',
		]],
		['menuitem', null, [
			'label' => 'Twitter',
			'icon' => DOMAIN . ICONS['twitter'],
			'data-share' => 'twitter',
		]],
		['menuitem', null, [
			'label' => 'Google+',
			'icon' => DOMAIN . ICONS['google+'],
			'data-share' => 'g+',
		]],
		['menuitem', null, [
			'label' => 'Linkedin',
			'icon' => DOMAIN . ICONS['linkedin'],
			'data-share' => 'linkedin',
		]],
		['menuitem', null, [
			'label' => 'Reddit',
			'icon' => DOMAIN . ICONS['reddit'],
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
	if (is_null($args)) {
		$args = [
			HTML::getInstance(),
			PDO::load(DB_CREDS),
			get_page(URL::getInstance()),
		];
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
				if (!$article->isFree) {
					$article->content = $article->description;
				}
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

/**
 * Import users from WP CSV file and MySQL generated CSV file
 * @param  String $members WP exported users CSV file
 * @param  String $users   MySQL exported CSV file
 * @return Int             Number of users imported
 * @see https://github.com/KVSun/kvsun.com/issues/100
 */
function import_users(String $members, String $users): Int
{
	$members = read_csv($members, true, 'username');
	$users = read_csv($users, true, 'user_login');
	$pdo = PDO::load(DB_CREDS);
	$users_stm = $pdo->prepare(
		'INSERT INTO `users` (
			`email`,
			`password`,
			`username`
		) VALUES (
			:email,
			:password,
			:username
		);'
	);
	$user_data_stm = $pdo->prepare(
		'INSERT INTO `user_data` (
			`id`,
			`name`,
			`tel`
		) VALUES (
			:id,
			:name,
			:tel
		) ON DUPLICATE KEY UPDATE
			`name` = COALESCE(:name, `name`),
			`tel`  = COALESCE(:tel, `tel`);'
	);
	$subscribers_stm = $pdo->prepare(
		'INSERT INTO `subscribers` (
			`id`,
			`status`,
			`sub_modified`,
			`sub_expires`
		) VALUES (
			:id,
			:status,
			:joined,
			:expires
		) ON DUPLICATE KEY UPDATE
			`status` = COALESCE(:status, `status`),
			`sub_expires` = COALESCE(:expires, `sub_expires`);'
	);

	$imported = 0;
	$roles = [
		'Online Subscription (1 year)'   => 7,
		'Staff'                          => 2,
		'E-Edition (1 year)'             => 6,
		'Online Subscription (6 months)' => 7,
		'E-Edition (6 months)'           => 6,
		'Online Subscription (1 month)'  => 7,
	];
	foreach ($members as $username => $member) {
		$pdo->beginTransaction();
		try {
			if (! in_array($member['membership'], $roles)) {
				$roles[] = $member['membership'];
			}
			if (array_key_exists($username, $users)) {
				$users_stm->execute([
					'email'    => strtolower($member['email']),
					'password' => $users[$username]['user_pass'],
					'username' => strtolower($username),
				]);
				if (intval($users_stm->errorCode()) !== 0) {
					$err = join(PHP_EOL, $users_stm->errorInfo());
					throw new \Exception($err);
				} elseif ($users_stm->rowCount() !== 1) {
					throw new \Exception("Failed to import {$username}");
				}
				$user_id = $pdo->lastInsertId();
				if (
					array_key_exists('firstname', $member)
					and array_key_exists('lastname', $member)
				) {
					$name = "{$member['firstname']} {$member['lastname']}";
				} else {
					$name= null;
				}
				$user_data_stm->execute([
					'id'   => $user_id,
					'name' => $name,
					'tel'  => isset($member['phone']) ? $member['tel'] : null,
				]);
				if (intval($user_data_stm->errorCode()) !== 0) {
					$err = join(PHP_EOL, $user_data_stm->errorInfo());
					throw new \Exception($err);
				} elseif ($user_data_stm->rowCount() !== 1) {
					throw new \Exception("Failed to import {$username}");
				}
				if ($member['expires'] !== 'Never') {
					$expires = (new \DateTime($member['expires']))->format(\DateTime::W3C);
				} else {
					$expires = null;
				}

				$joined = new \DateTime($member['joined']);
				$subscribers_stm->execute([
					'id' => $user_id,
					'status' => array_key_exists($member['membership'], $roles)
						? $roles[$member['membership']] : 8,
					'joined' => $joined->format(\DateTime::W3C),
					'expires' => $expires,
				]);
				if (intval($subscribers_stm->errorCode()) !== 0) {
					$err = join(PHP_EOL, $subscribers_stm->errorInfo());
					throw new \Exception($err);
				} elseif ($subscribers_stm->rowCount() !== 1) {
					throw new \Exception("Failed to import {$username}");
				}

				$imported++;
				$pdo->commit();
			} else {
				throw new \Exception("{$username} not found in users list.");
			}
		} catch (\Throwable $e) {
			$pdo->rollBack();
			echo $e->getMessage() . PHP_EOL;
			trigger_error($e->getMessage());
		}
	}
	foreach (func_get_args() as $file) {
		unlink($file);
	}
	return $imported;
}

/**
 * Reads a CSV file and returns a multi-dimensional array
 * @param  String  $fname       Filename of CSV, including extension
 * @param  boolean $use_headers Whether or not to use the first row as headers / column names
 * @param  String  $use_key     Optional column to use as key in created array. Requires $use_headers
 * @return Array                The parsed CSV file as a multi-dimensional array
 */
function read_csv(
	String $fname,
	Bool   $use_headers = false,
	String $use_key     = null
): Array
{
	$csv = new \SplFileObject($fname);
	$csv->setFlags($csv::READ_CSV | $csv::DROP_NEW_LINE | $csv::SKIP_EMPTY);
	$rows = [];

	if ($use_headers and $csv->valid()) {
		$headers = $csv->fgetcsv();
		$csv->next();
		$header_size = count($headers);
	}

	while ($csv->valid()) {
		$row = $csv->fgetcsv();
		$csv->next();

		if (empty($row)) {
			continue;
		}
		if ($use_headers) {
			$row = array_pad($row, $header_size, null);
			$row = array_combine($headers, $row);
		}
		if ($use_headers and isset($use_key) and array_key_exists($use_key, $row)) {
			$rows[$row[$use_key]] = $row;
		} else {
			$rows[] = $row;
		}
	}
	return $rows;
}
