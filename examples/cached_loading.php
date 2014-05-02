<?php

include __DIR__ . '/../src/ClassLoader.php';
include __DIR__ . '/../src/CacheListClassLoader.php';
include __DIR__ . '/../src/FileCacheClassLoader.php';

$loader = new Riimu\Kit\ClassLoader\FileCacheClassLoader(__DIR__ . '/cache.php');
$loader->addBasePath(__DIR__ . '/class');
$loader->register();

var_dump(new Vendor\SimpleClass());
