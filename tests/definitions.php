<?php

define('CLASS_BASE', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

function path(array $path, $add = false)
{
    return implode(DIRECTORY_SEPARATOR, $path) . ($add ? DIRECTORY_SEPARATOR : '');
}
