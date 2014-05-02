<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Class autoloader with simple file based caching for file locations.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileCacheClassLoader extends CacheListClassLoader
{
    /**
     * Path to the cache file.
     * @var string
     */
    private $cacheFile;

    /**
     * Creates new cached class loader and loads the cache from the file.
     *
     * If no path to the cache file is provided, then the cache is stored to
     * the same directory as the class itself.
     *
     * @param string $cacheFile Path to cache file or null for default
     */
    public function __construct($cacheFile = null)
    {
        if ($cacheFile === null) {
            $cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'cache.php';
        }

        $this->cacheFile = $cacheFile;

        if (file_exists($cacheFile)) {
            $cache = include $cacheFile;
        }

        if (empty($cache) || !is_array($cache)) {
            $cache = [];
        }

        parent::__construct($cache);
        $this->setCacheHandler(array($this, 'saveFile'));
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
     * Saves the class cache into the cache file.
     * @param array $cache Class location cache
     */
    public function saveFile(array $cache)
    {
        file_put_contents($this->cacheFile, $this->createCache($cache), LOCK_EX);
    }

    /**
     * Creates the PHP code for the class cache.
     * @param array $cache Class location cache
     * @return string PHP code for the cache file
     */
    private function createCache(array $cache)
    {
        $string = '<?php return [' . PHP_EOL;

        foreach ($cache as $key => $value) {
            $string .= sprintf("\t'%s' => '%s'," . PHP_EOL,
                $this->escape($key), $this->escape($value));
        }

        return  $string . '];' . PHP_EOL;
    }

    /**
     * Escapes strings to be put into singnle quotes.
     * @param string $string String to escape
     * @return string Escaped string
     */
    private function escape($string)
    {
        return strtr($string, ["'" => "\\'", "\\" => "\\\\"]);
    }
}
