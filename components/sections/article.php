<?php
namespace KVSun\Components\Sections\Article;

use \shgysk8zer0\DOM\{HTML};
use \shgysk8zer0\Core\{PDO};
use \KVSun\KVSAPI\{Comments, Abstracts\Content as KVSAPI};

use const \KVSun\Consts\{DOMAIN, DATE_FORMAT, DATETIME_FORMAT, LOGO};
use function \KVSun\Functions\{user_can};

return function (HTML $dom, PDO $pdo, KVSAPI $kvs)
{
	if (! $kvs->is_free and ! user_can('paidArticles')) {
		// #TODO need 404 pages (#123)
		return;
	}
	if (isset($kvs, $kvs->content, $kvs->posted, $kvs->title, $kvs->category)) {
		$main = $dom->getElementsByTagName('main')->item(0);
		$template = $dom->getElementById('article-template');
		foreach ($template->childNodes as $node) {
			$main->appendChild($node->cloneNode(true));
		}
		$article = $main->getElementsByTagName('article')->item(0);
		$xpath = new \DOMXPath($dom);
		try {
			$breadcrumbs = $xpath->query('.//*[@itemprop="item"]', $main);
			$xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(0))->item(0)->setAttribute('href', DOMAIN);
			$xpath->query('.//*[@itemprop="name"]', $breadcrumbs->item(1))->item(0)->textContent = $kvs->category->name;
			$xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(1))->item(0)->setAttribute('href', DOMAIN . $kvs->category->url);
			$xpath->query('.//*[@itemprop="name"]', $breadcrumbs->item(2))->item(0)->textContent = $kvs->title;
			$xpath->query('.//*[@itemprop="url"]', $breadcrumbs->item(2))->item(0)->setAttribute('href', DOMAIN . $kvs->category->url . '/' . $kvs->url);
		} catch (\Throwable $e) {
			trigger_error($e->getMessage());
		}
		$updated = new \DateTime(isset($kvs->updated) ? $kvs->updated : $kvs->posted);
		$posted = new \DateTime($kvs->posted);

		try {
			$xpath->query('.//*[@itemprop="headline"]', $article)->item(0)->textContent = $kvs->title;
			$xpath->query('.//*[@itemprop="articleSection"]', $article)->item(0)->textContent = $kvs->category->name;
			$xpath->query('.//*[@itemprop="author"]', $article)->item(0)->textContent = $kvs->author;
			$xpath->query('.//*[@itemprop="dateModified"]', $article)->item(0)->setAttribute('content', $updated->format(DATETIME_FORMAT));
			$keywords = $xpath->query('.//*[@itemprop="keywords"]', $article);
			if ($keywords = $xpath->query('.//*[@itemprop="keywords"]', $article)) {
				set_keywords($keywords->item(0), $kvs->keywords);
			}

			$pub_date = $xpath->query('.//*[@itemprop="datePublished"]', $article)->item(0);
			$pub_date->textContent = $posted->format(DATE_FORMAT);
			$pub_date->setAttribute('datetime', $posted->format(DATETIME_FORMAT));
			$articleBody = $xpath->query('.//*[@itemprop="articleBody"]', $article)->item(0);
			$articleBody->importHTML($kvs->content);

			$pub = $xpath->query('.//*[@itemprop="publisher"]', $article);
			if ($pub) {
				$pub = $pub->item(0);
				$xpath->query('.//*[@itemprop="url"]', $pub)->item(0)->setAttribute('href', DOMAIN);
				$xpath->query('.//*[@itemprop="name"]', $pub)->item(0)->textContent = 'Kern Valley Sun';
				$xpath->query('.//*[@itemprop="logo"]', $pub)->item(0)->setAttribute('content', DOMAIN . LOGO);
			}
			$count = add_comments($main->getElementsByTagName('footer')->item(0), $kvs->comments);
			$article->append('meta', null, [
				'itemprop' => 'commentCount',
				'content'  => count($kvs->comments),
			]);
		} catch(\Exception $e) {
			trigger_error($e);
		} catch(\Error $e) {
			trigger_error($e);
		}
	} else {
		trigger_error('Invalid page contents given.');
	}
};

function add_comments(\DOMElement $parent, Comments $comments)
{
	foreach ($comments as $comment) {
		$created = new \DateTime($comment->created);
		$container = $parent->append('div', null, [
			'itemprop'  => 'comment',
			'itemtype'  => 'http://schema.org/Comment',
			'id'        => "comment-{$comment->commentID}",
			'itemscope' => '',
		]);
		$user = $container->append('span', null,
		[
			'itemprop' => 'author',
			'itemtype' => 'http://schema.org/Person',
			'itemscope' => '',
		]);
		$user->append('img', null, [
			'src' => "https://www.gravatar.com/avatar/{$comment->email}",
			'width' => 80,
			'height' => 80,
			'itemprop' => 'image',
			'alt' => "{$comment->username} avatar",
		]);
		$user->append('b', 'By&nbsp;')->append('u', $comment->name, [
			'itemprop' => 'name',
		]);
		$container->append('span', '&nbsp;on&nbsp;')->append('time', $created->format(DATE_FORMAT), [
			'itemprop' => 'dateCreated',
			'datetime' => $created->format(DATETIME_FORMAT),
		]);
		$container->append('br');
		$container->append('div', $comment->text, [
			'itemprop' => 'text',
		]);
		$container->append('hr');
	}
}

function set_keywords(\DOMElement $container, Array $keywords)
{
	foreach ($keywords as $keyword) {
		$item = $container->ownerDocument->createElement('a', $keyword);
		$container->appendChild($item);
		$item->setAttribute('rel', 'tag');
	}
}
