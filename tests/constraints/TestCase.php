<?php

namespace Riimu\Kit\ClassLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/MIT MIT License
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Attempts to load the class and check if it exists.
     * @param string $class Name of the class to attempt loading
     * @param ClassLoader $loader ClassLoader to use
     * @param boolean $loads Whether the class should load successfully or not
     * @param string $message Optional message to display on failure
     * @throws \PHPUnit_Framework_Exception If invalid arguments are given
     */
    public static function assertClassLoads ($class, ClassLoader $loader, $loads = true, $message = '')
    {
        if (!is_string($class)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string', $class);
        }

        self::assertThat($class, new Constraint\ClassLoaderLoads($loader, $loads), $message);
    }
}
