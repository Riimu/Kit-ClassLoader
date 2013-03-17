<?php

include __DIR__ . '/../src/Riimu/Kit/ClassLoader/BasePathLoader.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class/');

$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->register();

var_dump(new Vendor\SimpleClass());
