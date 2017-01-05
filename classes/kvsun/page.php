<?php
namespace KVSun;

use \shgysk8zer0\Core_API as API;
use \shgysk8zer0\Core as Core;
use \shgysk8zer0\DOM as DOM;

final class Page
{
	// use API\Traits\GetInstance;

	const MAGIC_PROPERTY = '_data';

	private $_path = array();

	private $_data = array(
		'title'       => null,
		'author'      => null,
		'posted'      => null,
		'updated'     => null,
		'content'     => null,
		'category'    => null,
		'keywords'    => null,
		'description' => null,
		'url'         => null,
		'id'          => null,
	);

	private $_pdo;

	public function __construct(Core\URL $url)
	{
		$this->_set('url', "$url");
		$this->_pdo = Core\PDO::load();
		$this->_path = explode('/', trim($url->path, '/'));
		$this->_path = array_filter($this->_path);
		if (!empty($this->_path)) {
			$this->_set('category', $this->_getCat());
			$post = $this($this->category->id, end($this->_path));
			if (is_object($post)) {
				foreach (get_object_vars($post) as $key => $value) {
					$this->_set($key, $value);
				}
			}
		}
	}

	public function __get($prop)
	{
		if ($this->__isset($prop)) {
			return $this->{self::MAGIC_PROPERTY}[$prop];
		}
	}

	public function __isset($prop)
	{
		return isset($this->{self::MAGIC_PROPERTY}[$prop]);
	}

	public function __toString()
	{
		return json_encode($this->{self::MAGIC_PROPERTY});
	}

	public function getCategories($like = null)
	{
		if (is_string($like)) {
			$stm = $this->_pdo->prepare('SELECT `id`,
					`name`,
					`url-name` as `url`,
					`parent`
				FROM `categories`
				WHERE `url-name` LIKE :category
				 ORDER BY `sort`;'
			);
			$stm->category = "%$like%";
		} else {
			$stm = $this->_pdo->prepare('SELECT `id`,
					`name`,
					`url-name` as `url`,
					`parent`
				FROM `categories`
				 ORDER BY `sort`;'
			);
		}
		return $stm->execute()->getResults();
	}

	public function __invoke($category, $url)
	{
		$stm = $this->_pdo->prepare('SELECT `title`,
				`author`,
				`content`,
				`posted`,
				`updated`,
				`posted_by`,
				`description`,
				`keywords`
			FROM `posts`
			WHERE `cat-id` = :category and `url` = :url
			LIMIT 1;
		');
		$stm->category = $category;
		$stm->url = $url;
		return $stm->execute()->getResults(0);
	}

	public function __debugInfo()
	{
		return $this->{self::MAGIC_PROPERTY};
	}

	private function _set($prop, $val)
	{
		if (array_key_exists($prop, $this->{self::MAGIC_PROPERTY})) {
			$this->{self::MAGIC_PROPERTY}[$prop] = $val;
		}
	}

	private function _getPath(Core\URL $url)
	{
		$path = trim($url->path, '/');
		$path = explode('/', $path);
		return array_filter($path);
	}

	private function _getCat($uri = '')
	{
		$stm = $this->_pdo->prepare('SELECT `id`,
				`name`,
				`url-name`,
				`parent`
			FROM `categories`
			WHERE `url-name` = :category
			LIMIT 1;'
		);
		$stm->category = $this->_path[0];
		return $stm->execute()->getResults(0);
	}
}
