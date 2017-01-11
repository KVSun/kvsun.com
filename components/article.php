<?php
namespace KVSun\Components\Article;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo, $page)
{
	// $article->append('meta', null, ['itemprop' => 'publisher', 'content' => 'Kern Valley Sun']);
	if (is_object($page) and isset($page->content, $page->posted, $page->title)) {

		$article = $dom->getElementsByTagName('main')->item(0)->append('article');
		$article->itemscope = '';
		$article->itemtype = 'https://schema.org/Article';

		$updated = isset($page->updated)
			? new \DateTime($page->updated)
			: new \DateTime($page->posted);
		$posted = new \DateTime($page->posted);
		$header = $article->append('header');
		$header->append('meta', null, [
			'itemprop' => 'dateModified',
			'content' => $updated->format(\DateTime::W3C),
		]);

		$header->append('h2', $page->title, [
			'itemprop' => 'headline',
		]);
		$header->append('b', 'By&nbsp;')->append('span', $page->author, [
			'itemprop' => 'author',
		]);
		$header->append('br');
		$header->append('i', 'on&nbsp;')->append(
			'time',
			$posted->format('D. M j, Y \a\t h:m a'),
			[
				'datetime' => $posted->format(\DateTime::W3C),
				'itemprop' => 'datePublished',
			]
		);
		$header->append('br');
		$publisher = $header->append('h4', 'Published by&nbsp;')->append('span', null, [
			'itemscope' => '',
			'itemprop' => 'publisher',
			'itemtype' => 'https://schema.org/Organization',
		]);
		$publisher->append('a', null, [
			'itemprop' => 'url',
			'href' => \KVSun\DOMAIN,
		])->append('span', 'Kern Valley Sun', [
			'itemprop' => 'name',
		]);
		$publisher->append('meta', null, [
			'itemprop' => 'logo',
			'content' => \KVSun\DOMAIN . 'images/sun-icons/128.png',
		]);
		$header->append('hr');
		$container = $article->append('div', null, ['itemprop' => 'articleBody']);
		$container->importHTML($page->content);
		set_img_data($container);

	} else {
		trigger_error('Invalid page contents given.');
	}
};

function set_img_data(\DOMElement $container)
{
	$imgs = $container->getElementsByTagName('img');
	if ($imgs->length !== 0) {
		$img = $imgs->item(0);
		$url = parse_url($img->src);
		if (!array_key_exists('host', $url)) {
			$img->src = \KVSun\DOMAIN . ltrim($img->src, '/');
		}
		$container->append('meta', null,[
			'itemprop' => 'image',
			'content' => $img->src,
		]);
	} else {
		$container->append('meta', null, [
			'itemprop' => 'image',
			'content' => \KVSun\DOMAIN . 'images/sun-icons/256.png',
		]);
	}
}
