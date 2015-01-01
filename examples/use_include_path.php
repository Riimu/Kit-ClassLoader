<?php

require __DIR__ . '/../src/autoload.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class/');

$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->useIncludePath(true);
$loader->register();

var_dump(new Vendor\SimpleClass());
