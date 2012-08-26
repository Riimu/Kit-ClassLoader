<?php

include __DIR__ . '/../library/Rose/ClassLoader/ClassPathLoader.php';

$loader = new Rose\ClassLoader\ClassPathLoader();
$loader->addLibraryPath(__DIR__ . '/class/');
$loader->register();

new Vendor\SimpleClass();
