<?php

namespace Riimu\Kit\ClassLoader\Constraint;


/**
 * Tests if the class loader provides expected results from loading the class.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/MIT MIT License
 */
class ClassLoaderLoads extends \PHPUnit_Framework_Constraint
{
    /** @var \Riimu\Kit\ClassLoader\ClassLoader */
    private $loader;

    /** @var boolean */
    private $loads;

    /**
     * @param \Riimu\Kit\ClassLoader\ClassLoader $loader
     * @param $loads
     */
    public function __construct(\Riimu\Kit\ClassLoader\ClassLoader $loader, $loads)
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
        } elseif (class_exists($other, false) !== $this->loads) {
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
