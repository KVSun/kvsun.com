<?php
namespace KVSun\Classifieds;

use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

const BASE = __DIR__;
const COLS = 5;
const IMG_PATH = 'current' . DIRECTORY_SEPARATOR . '01 Current Graphics';
const ALLOWED_TAGS = '<b><p><div><br><hr>';
const DISP_AD_PATTERN = '/^\s*\*+\s*DISPLAY\s+AD\s*\*+/i';
const EXT = '.html';
const CSV_FEED = 'display ad feed.5.1.csv';

function build_classifieds(Array $files, DOM\HTMLElement $container, CSV $csv)
{
	$now = new \DateTime();
	$csv = array_filter($csv->getArrayCopy(), function($row) use ($now)
	{
		return $now >= new \DateTime($row['Start Date']) and $now <= new \DateTime($row['End Date']);
	});

	foreach ($files as $file) {
		$cat = basename($file, EXT);
		if (!is_numeric($cat) or ! array_key_exists($cat, Codes::CATEGORIES)) continue;
		$details = $container->append('details', null, ['open' => '']);
		$details->append('summary', htmlentities(Codes::CATEGORIES[$cat]));
		$file = file_get_contents($file);
		$file = strip_tags($file, ALLOWED_TAGS);
		$file = str_replace(["\r", "\r\n", "\n"], null, $file);
		$details->importHTML($file);

		foreach ($csv as $ad) {
			if ((int)$ad['Category Code'] === (int)$cat) {
				$details->append('img', null, [
					'src' => IMG_PATH . "/{$ad['Filename']}",
					'alt' => htmlentities($ad['Ad Text']),
					'title' => htmlentities($ad['Ad Text']),
					'class' => 'ad'
				]);
			}
			$details->append('br');
		}


		foreach($details->getElementsByTagName('div') as $div) {
			$title = $div->getElementsByTagName('b')[0];
			if (isset($title) and preg_match(DISP_AD_PATTERN, $title->textContent)) {
				$details->removeChild($div);
			} else {
				unset($div->align);
			}
		}
	}
}

function build(DOM\HTMLElement $parent)
{
	ini_set('auto_detect_line_endings', true);
	$header = Core\Headers::getInstance();

	$classifieds = glob(__DIR__ . DIRECTORY_SEPARATOR . 'current' . DIRECTORY_SEPARATOR . '*' . EXT);

	$csv = new CSV(__DIR__ . DIRECTORY_SEPARATOR . IMG_PATH . DIRECTORY_SEPARATOR . CSV_FEED);
	$parent->append('h1', 'Classifieds');

	build_classifieds($classifieds, $parent->append('main', null, [
		'class' => 'classified-list',
		'id' => 'classifieds',
		'data-cols' => 'auto',
	]), $csv);
};
\KVSun\load('head', 'nav');
build(DOM\HTML::getInstance()->body);
\KVSun\load('footer');
exit(DOM\HTML::getInstance());
// $console = \shgysk8zer0\Core\Console::getInstance();
// $console->asErrorHandler();
// $console->asExceptionHandler();

// if (defined('KVSun\DEBUG_MODE') and \KVSun\DEBUG_MODE) {
// 	$console = Core\Console::getInstance();
// 	$console->asErrorHandler()->asExceptionHandler();
// }
//
// if (isset($console)) {
// 	$console->sendLogHeader();
// }
//
// exit($dom);
