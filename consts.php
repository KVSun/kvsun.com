<?php
namespace KVSun\Consts;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
use function \KVSun\Functions\{get_icons};
const REQUIRED = [
	'events.php',
	'vendor/autoload.php',
];
const EXT          = '.php';
const INCLUDE_PATH = [
	'./classes',
	'./config',
	__DIR__,
];
const PAGE_COMPONENTS = [
	'head',
	'header',
	'nav',
	'main',
	'sidebar',
	'footer',
];
const ERROR_REPORTING = 0;
const EXCEPTION_HANDLER = '\shgysk8zer0\Core\Listener::exception';
const ERROR_HANDLER = '\shgysk8zer0\Core\Listener::error';
const ERROR_LOG = 'errors.log';
const EXCEPTION_LOG = 'exceptions.log';
const CRLF = "\r\n";

const DATE_FORMAT = 'D. M j, Y \a\t h:m:s A';
const DATETIME_FORMAT = \DATETIME::W3C;

const LOGO = 'images/sun-icons/256.png';
const LOGO_SIZE = 256;
const LOGO_VECTOR = 'images/sun-icons/any.svg';

const COMPONENTS   = __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR;
const TEMPLATE_DIR = COMPONENTS . DIRECTORY_SEPARATOR . 'kvs-templates' . DIRECTORY_SEPARATOR;
const CONFIG       = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
const PAGES_DIR    = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;
const DB_CREDS     = CONFIG . 'connect.json';
const AUTHORIZE    = 'authorize.ini';
const DB_INSTALLER = __DIR__ . DIRECTORY_SEPARATOR . 'default.sql';

const PUBLIC_KEY   = 'keys/public.pem';
const PRIVATE_KEY  = 'keys/private.pem';

const DEV_STYLE    = 'stylesheets/styles/import.css';
const STYLE        = 'stylesheets/styles/styles.css';
const SCRIPTS_DIR  = 'scripts/';
const SCRIPTS      = array('custom.js');
const SPRITES      = 'images/icons.svg';
const CSP          = array(
	'default-src'  => "'self'",
	'img-src'      => [
		"'self'",
		'https://www.gravatar.com',
		'https://i.imgur.com',
		'https://kernvalleysun.com',
		'data:',
	],
	'script-src'   => ["'self'"],
	'style-src'    => ["'self'", "'unsafe-inline'"],
	'media-src'    => ["'self'"],
	'report-uri'   => '/csp.php',
);

const HTML_TEMPLATES = [
	TEMPLATE_DIR . 'article.html',
	TEMPLATE_DIR . 'section.html',
];

const LOCAL_ZIPS = [
	93205,
	93238,
	93240,
	93255,
	93283,
	93285
];

const LOGGED_OUT_ONLY = [
	'[data-show-modal="#login-dialog"]',
	'[data-show-modal="#registration-dialog"]',
];

const LOGGED_IN_ONLY = [
	'[data-request="action=logout"]',
];

if (! array_key_exists('SERVER_NAME', $_SERVER)) {
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'localhost';
	$_SERVER['REMOTE_ADDR'] = $_SERVER['SERVER_ADDR'] = '127.0.0.1';
	$_SERVER['REQUEST_SCHEME'] = 'http';
}

function defined(String $const): Bool
{
	$const = strtoupper(trim($const, '\\'));
	return \defined(__NAMESPACE__ . "\\{$const}");
}

function define(String $const, $value): Bool
{
	$const = strtoupper(trim($const, '\\'));
	return \define(__NAMESPACE__ . "\\{$const}", $value);
}

define('DEBUG', $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR']);
define('DOMAIN', "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/");
define('ICONS', get_icons('./images/icons.csv'));

if (@file_exists('./config/.passwd')) {
	define('PASSWD', file_get_contents('./config/.passwd'));
} else {
	file_put_contents('./config/.passwd', bin2hex(openssl_random_pseudo_bytes(rand(20, 40))));
	define('PASSWD', file_get_contents('./config/.passwd'));
}
