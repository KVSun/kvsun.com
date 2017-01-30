<?php
namespace KVSun;
const REQUIRED = [
	'functions.php',
	'events.php',
	'vendor/autoload.php',
];
const EXT          = '.php';
const INCLUDE_PATH = array(
	'./classes',
	'./config',
	__DIR__,
);
const ERROR_REPORTING = 0;
const EXCEPTION_HANDLER = '\shgysk8zer0\Core\Listener::exception';
const ERROR_HANDLER = '\shgysk8zer0\Core\Listener::error';
const ERROR_LOG = 'errors.log';
const EXCEPTION_LOG = 'exceptions.log';

const COMPONENTS   = __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR;
const TEMPLATE_DIR = COMPONENTS . DIRECTORY_SEPARATOR . 'kvs-templates' . DIRECTORY_SEPARATOR;
const CONFIG       = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
const PAGES_DIR    = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;
const DB_CREDS     = CONFIG . 'connect.json';
const AUTHORIZE    = 'authorize.ini';
const DB_INSTALLER = __DIR__ . DIRECTORY_SEPARATOR . 'default.sql';

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
	],
	'script-src'   => ["'self'"],
	'style-src'    => ["'self'", "'unsafe-inline'"],
	'media-src'    => ["'self'"],
);

const HTML_TEMPLATES = [
	TEMPLATE_DIR . 'article.html',
	TEMPLATE_DIR . 'section.html',
];

const USER_ROLES   = array(
	0 => 'god',
	1 => 'admin',
	2 => 'editor',
	3 => 'freelancer',
	4 => 'subscriber',
	5 => 'guest',
);

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
	$_SERVER['SERVER_NAME'] = 'localhost';
}
if (! array_key_exists('HTTP_HOST', $_SERVER)) {
	$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
}
if (! array_key_exists('REQUEST_SCHEME', $_SERVER)) {
	$_SERVER['REQUEST_SCHEME'] = 'http';
}
define(__NAMESPACE__ . '\DEBUG', $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR']);
define(__NAMESPACE__ . '\DOMAIN', "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/");
