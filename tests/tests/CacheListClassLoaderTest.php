<?php

namespace Riimu\Kit\ClassLoader;

use Riimu\Kit\ClassLoader\Test\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CacheListClassLoaderTest extends TestCase
{
    public function testCachingStorage()
    {
        $result = false;
        $loader = new CacheListClassLoader([]);
        $loader->addBasePath(CLASS_BASE);
        $loader->setCacheHandler(function ($cache) use (& $result) {
            $result = $cache;
        });
        $loader->loadClass(\StoreIntoCache::class);

        $this->assertSame(
            ['StoreIntoCache' => CLASS_BASE . DIRECTORY_SEPARATOR . 'StoreIntoCache.php'],
            $result
        );
    }

    public function testFailLoadingFile()
    {
        $loader = new CacheListClassLoader([]);
        $loader->addBasePath(CLASS_BASE);
        $loader->setVerbose(true);
        $this->assertFalse($loader->loadClass('NonExistentFile'));
    }

    public function testFailLoadingCache()
    {
        $result = false;
        $loader = new CacheListClassLoader(
            ['NonExistentClass' => CLASS_BASE . DIRECTORY_SEPARATOR . 'NonExistentFile.php']
        );
        $loader->setCacheHandler(function ($cache) use (& $result) {
            $result = $cache;
        });
        $loader->setVerbose(true);
        $this->assertFalse(@$loader->loadClass('NonExistentClass'));
        $this->assertSame([], $result);
    }

    public function testLoadingBadFile()
    {
        $loader = $this->getMockBuilder(CacheListClassLoader::class)
            ->setMethods(['saveCache'])
            ->setConstructorArgs([[]])
            ->getMock();

        $loader->expects($this->never())->method('saveCache');
        $loader->setVerbose(false);
        $loader->addBasePath(CLASS_BASE);
        $this->assertNull($loader->loadClass('NoClassHere'));
    }

    public function testCacheLoading()
    {
        $loader = new CacheListClassLoader(['CachedClass' => CLASS_BASE . DIRECTORY_SEPARATOR . 'CachedClass.php']);
        $loader->setVerbose(true);
        $this->assertTrue($loader->loadClass(\CachedClass::class));
    }
}
