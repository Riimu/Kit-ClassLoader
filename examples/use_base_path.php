<?php

require __DIR__ . '/../src/autoload.php';

$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath(__DIR__ . '/class/');
$loader->register();

var_dump(new Vendor\SimpleClass());
