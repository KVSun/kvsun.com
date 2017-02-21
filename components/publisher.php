<?php
namespace KVSun\Components\Publisher;

use const \KVSun\Consts\{DOMAIN};

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};

return function (HTML $dom, PDO $pdo)
{
	$dom->formatOutput = true;
	$publisher = $pdo->nameValue('publisher') ?? new \stdClass();
	if (isset($publisher, $publisher->name)) {
		$footer = $dom->body->getElementsByTagName('footer')->item(0);
		$container = $footer->append('div', null, [
			'itemscope' => '',
			'itemprop'  => 'publisher',
			'itemtype'  => 'http://schema.org/Organization',
		]);
		$container->append('b', $publisher->name, [
			'itemprop' => 'name',
		]);
		$container->append('meta', null, [
			'content' => isset($publisher->url) ? $publisher->url : DOMAIN,
			'itemprop' => 'url',
		]);
		if (isset($publisher->logo)) {
			$logo = $container->append('div', null, [
				'itemprop'  => 'logo',
				'itemtype'  => 'http://schema.org/ImageObject',
				'itemscope' => '',
				'hidden'    => '',
			]);
			$logo->append('meta', null, [
				'content' => DOMAIN . ltrim($publisher->logo),
				'itemprop' => 'url',
			]);
		}
		if (isset($publisher->tel)) {
			$container->append('div', 'Phone #&nbsp;')->append('a', $publisher->tel, [
				'href'     => "tel:{$publisher->tel}",
				'itemprop' => 'telephone'
			]);
		}
		if (
			isset($publisher->email)
			and filter_var($publisher->email, FILTER_VALIDATE_EMAIL
		)) {
			$container->append('a', $publisher->email, [
				'href'     => "mailto:{$publisher->email}",
				'itemprop' => 'email',
			]);
			$container->append('br');
		}
		$container->append('br');
		if (isset(
			$publisher->street,
			$publisher->city,
			$publisher->state,
			$publisher->zip
		)) {
			$address = $container->append('div', null, [
				'itemprop'  => 'address',
				'itemtype'  => 'http://schema.org/PostalAddress',
				'itemscope' => '',
			]);
			if (isset($publisher->POBox)) {
				$address->append('div', 'PO Box&nbsp;')
				->append('span', $publisher->POBox, [
					'itemprop' => 'postOfficeBoxNumber',
				]);
			}
			$address->append('p', $publisher->street, [
				'itemprop' => 'streetAddress',
			]);
			$address->append('div', null, [], [
				[
					'span', $publisher->city, [
						'itemprop' => 'addressLocality',
					]
				], [
					'span', ',&nbsp;'
				], [
					'span', $publisher->state, [
						'itemprop' => 'addressRegion',
					]
				], [
					'span', '&nbsp;'
				], [
					'span', $publisher->zip, [
						'itemprop' => 'postalCode',
					]
				]
			]);
		}
	}
};
