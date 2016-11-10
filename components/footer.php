<?php
namespace KVSun\Components\Footer;
return function (\shgysk8zer0\DOM\HTML $dom, \shgysk8zer0\Core\PDO $pdo)
{
	$footer = $dom->body->append('footer', __NAMESPACE__);
};
