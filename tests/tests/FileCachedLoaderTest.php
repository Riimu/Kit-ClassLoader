<?php

use Riimu\Kit\ClassLoader\FileCachedLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileCachedLoaderTest extends PHPUnit_Framework_TestCase
{
    private $loader;

    public function tearDown()
    {
        if (file_exists($this->loader->getCacheFile())) {
            unlink($this->loader->getCacheFile());
        }
        if ($this->loader->isRegistered()) {
            $this->loader->unregister();
        }

        $this->loader = null;
    }

    public function testLoadingWithNoFile()
    {
        $loader = $this->getLoader();
        $this->assertFileNotExists($loader->getCacheFile());
    }

    public function testDefaultCachePath()
    {
        $loader = $this->getLoader();
        $loader->register();
        new FileCacheClass();
        $this->assertFileExists($loader->getCacheFile());
    }

    public function testDifferentPath()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'differentCache.php';
        $loader = $this->getLoader($file);
        $loader->register();
        new FileCacheClassB();
        $this->assertFileExists($file);
    }

    public function testSavingAndLoading()
    {
        $GLOBALS['doubleLoadedIncluded'] = 0;
        $loader = $this->getLoader();
        $loader->load('DoubleLoaded');

        $loaderB = $this->getMock('Riimu\Kit\ClassLoader\FileCachedLoader', ['saveFile']);
        $loaderB->expects($this->never())->method('saveFile');
        $loaderB->load('DoubleLoaded');
        $this->assertSame(2, $GLOBALS['doubleLoadedIncluded']);
    }

    /**
     * @return \Riimu\Kit\ClassLoader\FileCachedLoader
     */
    private function getLoader($path = null)
    {
        if (func_num_args() < 1) {
            $this->loader = new FileCachedLoader();
        } else {
            $this->loader = new FileCachedLoader($path);
        }

        $this->loader->addBasePath(CLASS_BASE);
        return $this->loader;
    }
}
