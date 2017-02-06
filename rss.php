<?php
namespace KVSun\RSS;
use \shgysk8zer0\Core_API\Abstracts\HTTPStatusCodes as HTTP;
use \shgysk8zer0\DOM as DOM;

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';
}

const DIR = __DIR__ . DIRECTORY_SEPARATOR . 'rss' . DIRECTORY_SEPARATOR;
const EXT = '.rss';
const CAT_KEY = 'category';

if (! array_key_exists(CAT_KEY, $_GET)) {
	http_response_code(HTTP::BAD_REQUEST);
	exit;
} elseif (false and @file_exists(DIR . $_GET[CAT_KEY] . EXT)) {
	header(sprintf('Content-Type: %s', DOM\RSS::CONTENT_TYPE));
	readfile(DIR . $_GET[CAT_KEY] . EXT);
	exit;
} elseif (!\KVSun\category_exists($_GET[CAT_KEY])) {
	http_response_code(HTTP::NOT_FOUND);
	exit;
} else {
	$rss = \KVSun\build_rss($_GET[CAT_KEY]);
	$rss->save(DIR . $_GET[CAT_KEY] . EXT);

	header(sprintf('Content-Type: %s', DOM\RSS::CONTENT_TYPE));
	exit($rss);
}
