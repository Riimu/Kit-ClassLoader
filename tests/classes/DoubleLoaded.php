<?php

if (!class_exists('DoubleLoaded')) {
    require __DIR__ . '/DoubleLoadedClass.php';
}

Riimu\Kit\ClassLoader\FileCacheClassLoaderTest::$counter++;
