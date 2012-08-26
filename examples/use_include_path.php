<?php

use Rose\ClassLoader\ClassPathLoader;

include __DIR__ . '/../library/Rose/ClassLoader/ClassPathLoader.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class/');

$loader = new ClassPathLoader();
$loader->register();

new Vendor\SimpleClass();
