<?php

include __DIR__ . '/../src/riimu/kit/ClassLoader/BasePathLoader.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class/');

$loader = new riimu\kit\ClassLoader\BasePathLoader();
$loader->register();

var_dump(new Vendor\SimpleClass());