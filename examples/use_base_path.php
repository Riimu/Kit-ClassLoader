<?php

include __DIR__ . '/../src/ClassLoader.php';

$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath(__DIR__ . '/class/');
$loader->register();

var_dump(new Vendor\SimpleClass());
