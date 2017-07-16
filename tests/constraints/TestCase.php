<?php

namespace Riimu\Kit\ClassLoader\Test;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use PHPUnit\Util\InvalidArgumentHelper;
use Riimu\Kit\ClassLoader\ClassLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/MIT MIT License
 */
class TestCase extends PhpUnitTestCase
{
    /**
     * Attempts to load the class and check if it exists.
     * @param string $class Name of the class to attempt loading
     * @param ClassLoader $loader ClassLoader to use
     * @param bool $loads Whether the class should load successfully or not
     * @param string $message Optional message to display on failure
     * @throws Exception If invalid arguments are given
     */
    public static function assertClassLoads($class, ClassLoader $loader, $loads = true, $message = '')
    {
        if (!is_string($class)) {
            throw InvalidArgumentHelper::factory(1, 'string', $class);
        }

        self::assertThat($class, new Constraint\ClassLoaderLoads($loader, $loads), $message);
    }
}
