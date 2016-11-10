<?php
namespace KVSun;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

define('URL', \shgysk8zer0\Core\URL::getInstance());
load('head', 'header', 'nav', 'main', 'sidebar', 'footer');
exit(\shgysk8zer0\DOM\HTML::getInstance());
