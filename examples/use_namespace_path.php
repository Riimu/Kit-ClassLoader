<?php

include __DIR__ . '/../src/riimu/kit/ClassLoader/BasePathLoader.php';

$loader = new riimu\kit\ClassLoader\BasePathLoader();
$loader->addNamespacePath('Vendor', __DIR__ . '/class/');
$loader->register();

var_dump(new Vendor\SimpleClass());
