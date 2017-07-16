<?php

namespace Riimu\Kit\ClassLoader;

use Riimu\Kit\ClassLoader\Test\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
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
        $this->assertTrue(class_exists(\FileCacheClass::class));
        $file = $loader->getCacheFile();
        $this->destroy($loader);
        $this->assertFileExists($file);
    }

    public function testSavingAndLoading()
    {
        $loader = $this->getLoader();
        $loader->loadClass(\DoubleLoaded::class);
        $this->destroy($loader);

        $loaderB = $this->getMockBuilder(FileCacheClassLoader::class)
            ->setMethods(['storeCache'])
            ->setConstructorArgs([$this->cachePath])
            ->getMock();
        $loaderB->expects($this->never())->method('storeCache');
        $loaderB->loadClass(\DoubleLoaded::class);
        $this->assertSame(2, self::$counter);
    }

    /**
     * @return FileCacheClassLoader
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
        $loader->saveCacheFile();
        $loader = null;
    }
}
