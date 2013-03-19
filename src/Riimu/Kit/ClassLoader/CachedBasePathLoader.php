<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Improved BasePathLoader with class file path caching.
 *
 * In addition to the features of BasePathLoader, the CachedBasePathLoader
 * provides for caching of file paths for classes. This can improve the class
 * auto loading times, because the class file no longer need to be searched in
 * known paths.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CachedBasePathLoader extends BasePathLoader
{
    /**
     * List of classes and files.
     * @var array
     */
    private $cache;

    /**
     * Callback used for storing the cache.
     * @var type
     */
    private $cacheHandler;

    /**
     * Creates a new instance.
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
     * The cache handler is called with the full class path cache array whenever
     * it is changed.
     *
     * @param callable $callback Callback for storing cache.
     * @return CachedBasePathLoader Returns self for call chaining
     */
    public function setCacheHandler(callable $callback)
    {
        $this->cacheHandler = $callback;
        return $this;
    }

    /**
     * Loads the class by first checking if the file path is cached.
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false otherwise
     */
    public function load($class)
    {
        if (isset($this->cache[$class])) {
            $result = include $this->cache[$class];

            if ($result === false) {
                unset($this->cache[$class]);
                $this->saveCache();
            } else {
                return true;
            }
        }

        return parent::load($class);
    }

    /**
     * Loads the class from the given file and stores the path into cache.
     * @param string $class Full name of the class
     * @param string $file File where the class could exist
     * @return boolean True if the class was loaded, false if not
     */
    protected function loadFromFile($class, $file)
    {
        if (!parent::loadFromFile($class, $file)) {
            return false;
        }

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
