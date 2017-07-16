<?php

namespace Riimu\Kit\ClassLoader\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Riimu\Kit\ClassLoader\ClassLoader;

/**
 * Tests if the class loader provides expected results from loading the class.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/MIT MIT License
 */
class ClassLoaderLoads extends Constraint
{
    /** @var ClassLoader */
    private $loader;

    /** @var bool */
    private $loads;

    /**
     * @param ClassLoader $loader
     * @param bool $loads
     */
    public function __construct(ClassLoader $loader, $loads)
    {
        parent::__construct();
        $this->loader = $loader;
        $this->loads = (bool) $loads;
    }

    /**
     * @param string $other
     * @return bool
     */
    protected function matches($other)
    {
        if ($this->loader->loadClass($other) !== $this->loads) {
            return false;
        }

        if (class_exists($other, false) !== $this->loads) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return ($this->loads ? 'is loaded' : 'is not loaded') . ' by the ClassLoader';
    }
}
