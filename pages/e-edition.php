<?php
/**
 * @todo Add login check before loading E-Edition. Redirect to login if needed.
 */
namespace KVSun\WP_E_Edition;
use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

const TITLE       = 'Read an E-Edtion';
const ROOT        = __DIR__ . DIRECTORY_SEPARATOR . 'E-Editions';
const PUB_DAY     = 'Wednesday';
const DATE_KEY    = 'date';
const ISSUE_KEY   = 'section';
const SCAN_BACK   = 4;
const ICON_SIZE   = 64;
const IMG_PATH    = '../images/';
// Date format for humans to read
const OUT_FORM    = 'long';
// Date format for internal/server user
const IN_FORM     = 'week';
const FORMATS     = array(
	'week'   => 'Y-\WW',
	'long'  => 'F j, Y'
);


ob_start();
// require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

function list_weeks(Week $week, DOM\HTMLElement $container, $scan = SCAN_BACK)
{
	$url = Core\URL::getInstance();
	$scanner = $week->scan();
	$weeks = 0;

	while ($weeks++ < $scan) {
		$details = $container->append('details');
		$details->append('summary')->append('b', $week->format(FORMATS[OUT_FORM]));
		$list = $details->append('ul');

		foreach ($scanner as $file) {
			$url->query = [
				DATE_KEY    => $week->format(FORMATS[IN_FORM]),
				ISSUE_KEY   => "$file"
			];
			$list->append('li')->append('a', $file, ['href' => "$url"] );
		}
		try {
			$week->modify('-1 week');
		} catch (\Exception $e) {
			Core\Console::getInstance()->error($e->getMessage());
			break;
		}
	}
	$container->getElementsByTagName('details')->item(0)->open = 'true';
}

$console = Core\Console::getInstance();
$console->asErrorHandler()->asExceptionHandler();
$timer = new Core\Timer();

try {
	$header = Core\Headers::getInstance();
	$url    = Core\URL::getInstance();
	$date   = new \DateTime(array_key_exists(DATE_KEY, $_GET) ? $_GET[DATE_KEY] : null);

	if ($date->format('l') !== PUB_DAY) {
		$date->modify(PUB_DAY);
	}

	if ($date > new \DateTime()) {
		$date->modify('-1 week');
	}

	$week = new Week(ROOT, $date->format($date::W3C));

	if (array_key_exists(ISSUE_KEY, $_GET)) {
		if (isset($week->{$_GET[ISSUE_KEY]})) {
			$week->{$_GET[ISSUE_KEY]}->out();
		} else {
			trigger_error(
				"No section '{$_GET[ISSUE_KEY]}' found for {$week->format(FORMATS[OUT_FORM])}."
			);
		}
	}

	$header->content_type = 'text/html';
	$dom = new DOM\HTML();
	$dom->head->append('title', TITLE);
	$dom->body->append('a', null, [
		'href' => '/'
	])->append('img', null, [
		'src' => IMG_PATH . 'sun.svg',
		'alt' => 'Kern Valley Sun homepage'
	]);

	$form = $dom->body->append('form', null, ['name' => 'edition-date']);
	$form->append('label', "Pick a week/year");
	$form->append('input', null, [
		'type'        => 'week',
		'name'        => DATE_KEY,
		'id'          => DATE_KEY,
		'value'       => (new \DateTime())->format(FORMATS[IN_FORM]),
		'placeholder' => 'YYYY-W##',
		'required'    => '',
		'autofocus'   => ''
	]);
	$form->append('br');
	$form->append('button', null, ['type' => 'submit]'])->append('svg', null, [
		'height'      => 32,
		'width'       => 32,
		'xmlns'       => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
	])->append('use', null, [
		'xlink:href' => IMG_PATH . 'icons.svg#search'
	]);
	unset($form);

	$dom->body->append('h1', TITLE);

	$dom->body->append('hr');
	$dom->body->append('svg', null, [
		'height'      => ICON_SIZE,
		'width'       => ICON_SIZE,
		'xmlns'       => 'http://www.w3.org/2000/svg',
		'xmlns:xlink' => 'http://www.w3.org/1999/xlink'
	])->append('use', null, [
		'xlink:href' => IMG_PATH . 'icons.svg#calendar'
	]);

	list_weeks($week, $dom->body);

	exit($dom);
} catch(\Exception $e) {
	http_response_code($e->getCode());
	$header->content_type = 'text/plain';
	$console->error($e);
	exit($e->getMessage());
}
