<?php

use Rose\ClassLoader\ClassPathLoader;

include __DIR__ . '/../library/Rose/ClassLoader/ClassPathLoader.php';

$loader = new ClassPathLoader();
$loader->addLibraryPath(__DIR__ . '/class/');
$loader->register();

new Vendor\SimpleClass();
