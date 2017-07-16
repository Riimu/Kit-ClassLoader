<?php

require_once __DIR__ . '/definitions.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists(\PHPUnit\Framework\Constraint\Constraint::class)) {
    class_alias(\PHPUnit_Framework_Constraint::class, \PHPUnit\Framework\Constraint\Constraint::class);
}
