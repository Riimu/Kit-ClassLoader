<?php

include __DIR__ . '/../src/BasePathLoader.php';

$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->addBasePath(__DIR__ . '/class/');
$loader->register();

var_dump(new Vendor\SimpleClass());
