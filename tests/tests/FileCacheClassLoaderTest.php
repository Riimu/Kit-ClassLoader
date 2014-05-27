<?php

namespace Riimu\Kit\ClassLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileCacheClassLoaderTest extends TestCase
{
    public static $counter = 0;
    private $cachePath;

    public function tearDown()
    {
        if ($this->cachePath !== null && file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }

        $this->cachePath = null;
        self::$counter = 0;
    }

    public function testLoadingWithNoFile()
    {
        $loader = $this->getLoader();
        $file = $loader->getCacheFile();
        $this->destroy($loader);
        $this->assertFileNotExists($file);
    }

    public function testCacheCreation()
    {
        $loader = $this->getLoader();
        $loader->register();
        $this->assertTrue(class_exists('FileCacheClass'));
        $file = $loader->getCacheFile();
        $this->destroy($loader);
        $this->assertFileExists($file);
    }

    public function testSavingAndLoading()
    {
        $loader = $this->getLoader();
        $loader->loadClass('DoubleLoaded');
        $this->destroy($loader);

        $loaderB = $this->getMock('Riimu\Kit\ClassLoader\FileCacheClassLoader', ['storeCache'], [$this->cachePath]);
        $loaderB->expects($this->never())->method('storeCache');
        $loaderB->loadClass('DoubleLoaded');
        $this->assertSame(2, self::$counter);
    }

    /**
     * @return \Riimu\Kit\ClassLoader\FileCacheClassLoader
     */
    private function getLoader()
    {
        $this->cachePath = __DIR__ . DIRECTORY_SEPARATOR . 'cache.php';
        $loader = new FileCacheClassLoader($this->cachePath);
        $loader->addBasePath(CLASS_BASE);
        return $loader;
    }

    /**
     * @param FileCacheClassLoader $loader
     */
    private function destroy(FileCacheClassLoader & $loader)
    {
        $loader->unregister();
        $loader->__destruct();
        $reflect = new \ReflectionClass($loader);
        $prop = $reflect->getProperty('store');
        $prop->setAccessible(true);
        $prop->setValue($loader, null);
        $loader = null;
    }
}
