<?php
namespace KVSun\Components\Article;
return function (
	\shgysk8zer0\DOM\HTML $dom,
	\shgysk8zer0\Core\PDO $pdo,
	\KVSun\KVSAPI\Abstracts\Content $kvs
)
{
	if (
		is_object($kvs)
		and isset($kvs->content, $kvs->posted, $kvs->title)
	) {
		$main = $dom->getElementsByTagName('main')->item(0);
		$template = $dom->getElementById('article-template');
		foreach ($template->childNodes as $node) {
			$main->appendChild($node->cloneNode(true));
		}
		$article = $main->getElementsByTagName('article')->item(0);
		$xpath = new \DOMXPath($dom);
		try {
			$breadcrumbs = $xpath->query('.//*[@itemprop="item"]', $main);
			$xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(0))->item(0)->setAttribute('href', \KVSun\DOMAIN);
			$xpath->query('.//*[@itemprop="name"]', $breadcrumbs->item(1))->item(0)->textContent = $kvs->category;
			$xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(1))->item(0)->setAttribute('href', \KVSun\DOMAIN . $kvs->category);
			$xpath->query('.//*[@itemprop="name"]', $breadcrumbs->item(2))->item(0)->textContent = $kvs->title;
			// $xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(2))->item(0)->setAttribute('href', \KVSun\DOMAIN . ltrim($kvs->url->path, '/'));
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		}
		$updated = new \DateTime(isset($kvs->updated) ? $kvs->updated : $kvs->posted);
		$posted = new \DateTime($kvs->posted);

		try {
			$xpath->query('.//*[@itemprop="headline"]', $article)->item(0)->textContent = $kvs->title;
			$xpath->query('.//*[@itemprop="articleSection"]', $article)->item(0)->textContent = $kvs->category;
			$xpath->query('.//*[@itemprop="author"]', $article)->item(0)->textContent = $kvs->author;
			$xpath->query('.//*[@itemprop="dateModified"]', $article)->item(0)->setAttribute('content', $updated->format(\DateTime::W3C));
			$keywords = $xpath->query('.//*[@itemprop="keywords"]', $article);
			if ($keywords = $xpath->query('.//*[@itemprop="keywords"]', $article)) {
				set_keywords($keywords->item(0), $kvs->keywords);
			}

			$pub_date = $xpath->query('.//*[@itemprop="datePublished"]', $article)->item(0);
			$pub_date->textContent = $posted->format('D. M j, Y \a\t h:m a');
			$pub_date->setAttribute('datetime', $posted->format(\DateTime::W3C));
			$articleBody = $xpath->query('.//*[@itemprop="articleBody"]', $article)->item(0);
			$articleBody->importHTML($kvs->content);

			$pub = $xpath->query('.//*[@itemprop="publisher"]', $article);
			if ($pub) {
				$pub = $pub->item(0);
				$xpath->query('.//*[@itemprop="url"]', $pub)->item(0)->setAttribute('href', \KVSun\DOMAIN);
				$xpath->query('.//*[@itemprop="name"]', $pub)->item(0)->textContent = 'Kern Valley Sun';
				$xpath->query('.//*[@itemprop="logo"]', $pub)->item(0)->setAttribute('content', \KVSun\DOMAIN . 'images/sun-icons/256.png');
			}
			set_img_data($articleBody);
		} catch(\Exception $e) {
			trigger_error($e);
		} catch(\Error $e) {
			trigger_error($e);
		}
	} else {
		trigger_error('Invalid page contents given.');
	}
};

function set_keywords(\DOMElement $container, Array $keywords) {
	foreach ($keywords as $keyword) {
		$item = $container->ownerDocument->createElement('a', $keyword);
		$container->appendChild($item);
		$item->setAttribute('rel', 'tag');
	}
}

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
