<?php

include __DIR__ . '/../src/BasePathLoader.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class/');

$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->setLoadFromIncludePath(true);
$loader->register();

var_dump(new Vendor\SimpleClass());
