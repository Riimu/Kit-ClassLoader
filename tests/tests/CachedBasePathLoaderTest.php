<?php

use Riimu\Kit\ClassLoader\CachedBasePathLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CachedBasePathLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testCachingStorage()
    {
        $result = false;
        $loader = new CachedBasePathLoader([]);
        $loader->addBasePath(CLASS_BASE);
        $loader->setCacheHandler(function ($cache) use (& $result) {
            $result = $cache;
        });
        $loader->load('StoreIntoCache');
        $this->assertEquals(['StoreIntoCache' => CLASS_BASE . DIRECTORY_SEPARATOR . 'StoreIntoCache.php'], $result);
    }

    public function testFailLoadingFile()
    {
        $loader = new CachedBasePathLoader([]);
        $loader->addBasePath(CLASS_BASE);
        $loader->setThrowOnMissingClass(false);
        $this->assertFalse($loader->load('NoClassHere'));
    }

    public function testFailLoadingCache()
    {
        $result = false;
        $loader = new CachedBasePathLoader(['NonExistantClass' => CLASS_BASE . DIRECTORY_SEPARATOR . 'NonExistantFile.php']);
        $loader->setCacheHandler(function ($cache) use (& $result) {
            $result = $cache;
        });
        $this->assertFalse(@$loader->load('NonExistantClass'));
        $this->assertEquals([], $result);
    }

    public function testCacheLoading()
    {
        $loader = new CachedBasePathLoader(['CachedClass' => CLASS_BASE . DIRECTORY_SEPARATOR . 'CachedClass.php']);
        $this->assertTrue($loader->load('CachedClass'));
    }
}
