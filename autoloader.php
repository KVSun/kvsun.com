<?php
namespace KVSun;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'consts.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

set_include_path(CLASSES . PATH_SEPARATOR . CONFIG . PATH_SEPARATOR . get_include_path());
spl_autoload_register('spl_autoload');
