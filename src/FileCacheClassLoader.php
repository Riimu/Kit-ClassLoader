<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Provides a simplified PHP file storage for the class file path cache.
 *
 * FileCacheClassLoader implements the CacheListClassLoader by storing the
 * class paths in a PHP file that is loaded when the class is loaded. If changes
 * are made to the cache, a new cache file is generated at the end of the
 * request, instead of on every new file to reduce file writes.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileCacheClassLoader extends CacheListClassLoader
{
    /** @var string Path to the cache file */
    private $cacheFile;

    /** @var string[]|null Cache to store at the end of request */
    private $store;

    /**
     * Creates new FileCacheClassLoader instance and loads the cache file.
     * @param string $cacheFile Path to cache file
     */
    public function __construct($cacheFile)
    {
        $this->store = null;
        $this->cacheFile = $cacheFile;

        if (file_exists($cacheFile)) {
            $cache = include $cacheFile;
        }

        if (empty($cache) || !is_array($cache)) {
            $cache = [];
        }

        parent::__construct($cache);
        $this->setCacheHandler([$this, 'storeCache']);
    }

    /**
     * Writes the cache file if changes were made.
     */
    public function __destruct()
    {
        if ($this->store !== null) {
            file_put_contents($this->cacheFile, $this->createCache($this->store), LOCK_EX);
        }
    }

    /**
     * Returns the path to the cache file.
     * @return string Path to the cache file
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    /**
     * Stores the cache to be saved at the end of the request.
     * @param array $cache Class location cache
     */
    public function storeCache(array $cache)
    {
        $this->store = $cache;
    }

    /**
     * Creates the PHP code for the class cache.
     * @param array $cache Class location cache
     * @return string PHP code for the cache file
     */
    private function createCache(array $cache)
    {
        ksort($cache);

        $string = '<?php return [' . PHP_EOL;
        $format = "\t%s => %s," . PHP_EOL;

        foreach ($cache as $key => $value) {
            $string .= sprintf($format, var_export($key, true), var_export($value, true));
        }

        return  $string . '];' . PHP_EOL;
    }
}
