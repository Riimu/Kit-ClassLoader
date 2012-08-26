<?php

include __DIR__ . '/../library/Rose/ClassLoader/ClassPathLoader.php';

$loader = new Rose\ClassLoader\ClassPathLoader();
$loader->addVendorPath('Vendor', __DIR__ . '/class/vendor/');
$loader->register();

new Vendor\SimpleClass();
