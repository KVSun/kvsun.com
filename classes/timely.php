<?php
final class Timely extends\shgysk8zer0\cURL\request
{
	const PARAMS = array(
		'plugin' => 'all-in-one-event-calendar',
		'controller' => 'ai1ec_exporter_controller',
		'action' => 'export_events',
		'xml' => 'true',
	);

	const HEADERS = array(
		'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	);

	public function __construct($url)
	{
		$timely = new \shgysk8zer0\Core\URL($url);
		foreach(self::PARAMS as $key => $value) {
			$timely->query->$key = $value;
		}
		// \shgysk8zer0\Core\Console::getInstance()->log($timely);
		// $req = new \shgysk8zer0\cURL\Request($timely);
		// header('Content-Type: text/plain');
		parent::__construct($timely, [], self::HEADERS);
	}
}
