<?php

require __DIR__ . '/../src/autoload.php';

$loader = new Riimu\Kit\ClassLoader\FileCacheClassLoader(__DIR__ . '/cache.php');
$loader->addBasePath(__DIR__ . '/class');
$loader->register();

var_dump(new Vendor\SimpleClass());
