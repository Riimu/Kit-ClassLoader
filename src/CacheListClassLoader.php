<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Provides a simple method of caching list of class file locations.
 *
 * CacheListClassLoader provides a simple way to implement your own caching
 * handlers for the ClassLoader. The base idea of this cache is to call a
 * provided cache save handler when a new class location is found with the
 * whole class location cache. The saved cache location should be provided
 * in the constructor when the class loader is constructed.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CacheListClassLoader extends ClassLoader
{
    /**
     * List of class file locations.
     * @var array
     */
    private $cache;

    /**
     * Callback used for storing the cache.
     * @var callback
     */
    private $cacheHandler;

    /**
     * Creates a new CacheListClassLoader instance.
     *
     * The parameter should contain the paths provided to your cache save
     * handler. If no cache exists yet, an empty array should be provided
     * instead.
     *
     * @param array $cache The cached paths stored by your cache handler
     */
    public function __construct(array $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->cacheHandler = null;
    }

    /**
     * Sets the callback used to store the cache.
     *
     * Whenever a new file location for class is found, the cache handler is
     * called with an associative array containing the path for different
     * classes. The cache handler should store the array and provide it in the
     * constructor in following requests.
     *
     * @param callable $callback Callback for storing cache.
     * @return CacheListClassLoader Returns self for call chaining
     */
    public function setCacheHandler(callable $callback)
    {
        $this->cacheHandler = $callback;
        return $this;
    }

    /**
     * Loads the class by first checking if the file path is cached.
     * @param string $class Full name of the class
     * @return boolean|null True if the class was loaded, false if not
     */
    public function loadClass($class)
    {
        if (isset($this->cache[$class])) {
            $result = include $this->cache[$class];

            if ($result === false) {
                unset($this->cache[$class]);
                $this->saveCache();
                $result = parent::loadClass($class);
            }
        } else {
            $result = parent::loadClass($class);
        }

        if ($this->verbose) {
            return $result !== false;
        }
    }

    /**
     * Loads the class from the given file and stores the path into cache.
     * @param string $file Full path to the file
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false if not
     */
    protected function loadFile($file, $class)
    {
        parent::loadFile($file, $class);
        $this->cache[$class] = $file;
        $this->saveCache();
        return true;
    }

    /**
     * Saves the cache by calling the cache handler with it.
     */
    private function saveCache()
    {
        if ($this->cacheHandler !== null) {
            call_user_func($this->cacheHandler, $this->cache);
        }
    }
}
