<?php

namespace Riimu\Kit\ClassLoader;

use FooBar\PrefClassA;
use FooBar\testns\PrefClassB;
use Riimu\Kit\ClassLoader\Test\TestCase;
use testns\NamespaceClass;
use under_ns\under_class;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/MIT MIT License
 */
class ClassLoaderTest extends TestCase
{
    public function testRegistrationHandling()
    {
        $loader = new ClassLoader();
        $this->assertFalse($loader->isRegistered());
        $this->assertTrue($loader->register());
        $this->assertTrue($loader->isRegistered());
        $this->assertTrue($loader->unregister());
        $this->assertFalse($loader->isRegistered());
    }

    public function testRegisteringMultipleTimes()
    {
        $loader = new ClassLoader();
        $loader->register();
        $this->assertTrue($loader->register());
        $this->assertTrue($loader->unregister());
        $this->assertFalse($loader->isRegistered());
        $this->assertFalse($loader->unregister());
    }

    public function testRegisteringMultipleLoaders()
    {
        $loader = new ClassLoader();
        $loader2 = new ClassLoader();

        $loader->register();
        $this->assertFalse($loader2->isRegistered());
        $this->assertTrue($loader2->register());
        $this->assertTrue($loader2->isRegistered());
        $this->assertTrue($loader->isRegistered());
        $this->assertTrue($loader->unregister());
        $this->assertTrue($loader2->isRegistered());
        $this->assertTrue($loader2->unregister());
    }

    public function testCallingAutoloadCall()
    {
        $loader = $this->getMockBuilder(ClassLoader::class)
            ->setMethods(['loadClass'])
            ->getMock();

        $loader->expects($this->once())->method('loadClass')->willReturn(false);

        $this->assertTrue($loader->register());
        $this->assertFalse(class_exists('ThisClassDoesNotExist'));
        $this->assertTrue($loader->unregister());
    }

    public function testMissingClass()
    {
        $loader = new ClassLoader();
        $this->assertClassLoads('ThisClassDoesNotExist', $loader, false);
    }

    public function testBasePath()
    {
        $loader = new ClassLoader();
        $this->assertClassLoads(\BaseDirClass::class, $loader, false);
        $loader->addBasePath(CLASS_BASE);
        $this->assertClassLoads(\BaseDirClass::class, $loader, true);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadingExistingClass()
    {
        $loader = new ClassLoader();
        $loader->setVerbose(false);
        $this->assertNull($loader->loadClass(ClassLoader::class));
        $loader->setVerbose(true);
        $loader->loadClass(ClassLoader::class);
    }

    public function testLoadingViaIncludePath()
    {
        $loader = new ClassLoader();
        $includePath = get_include_path();

        $loader->useIncludePath(false);
        $this->assertClassLoads(\PathSuccess::class, $loader, false);
        $loader->useIncludePath(true);
        $this->assertClassLoads(\PathSuccess::class, $loader, false);

        set_include_path(get_include_path() . PATH_SEPARATOR . CLASS_BASE . DIRECTORY_SEPARATOR . 'include_path');

        $loader->useIncludePath(false);
        $this->assertClassLoads(\PathSuccess::class, $loader, false);
        $loader->useIncludePath(true);
        $this->assertClassLoads(\PathSuccess::class, $loader, true);

        set_include_path($includePath);
    }

    /**
     * @return ClassLoader
     */
    public function testMissingClassWithoutException()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE);
        $loader->setVerbose(false);
        $this->assertNull($loader->loadClass('NoClassHere'));

        return $loader;
    }

    /**
     * @param ClassLoader $loader
     * @depends testMissingClassWithoutException
     * @expectedException \RuntimeException
     */
    public function testMissingClassWithException(ClassLoader $loader)
    {
        $loader->setVerbose(true);
        $loader->loadClass('NoClassHere');
    }

    public function testNamespaceLoadOrder()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(['testns\\' => CLASS_BASE . DIRECTORY_SEPARATOR . 'pathB' . DIRECTORY_SEPARATOR]);
        $loader->addBasePath([CLASS_BASE . DIRECTORY_SEPARATOR . 'pathA'], 'testns');
        $this->assertClassLoads(NamespaceClass::class, $loader, true);
        $this->assertSame('B', NamespaceClass::$source);
    }

    public function testAutoChainingDifferentTypes()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE);
        $loader->register();
        $this->assertTrue(class_exists(\SomeClass::class, true));
        $this->assertTrue(interface_exists(\SomeInterface::class, false));
        $this->assertTrue(trait_exists(\SomeTrait::class, false));
        $loader->unregister();
    }

    public function testDifferentExtensions()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE);
        $loader->setFileExtensions(['.inc']);
        $this->assertClassLoads(\DifferentExt::class, $loader, true);
    }

    public function testMissingFromNamespace()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE . DIRECTORY_SEPARATOR . 'pathA', 'testns');
        $this->assertClassLoads('testns\ThisDoesNotExist', $loader, false);
    }

    public function testLoadingClassWithUnderscores()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE, 'pathB\testns');
        $this->assertClassLoads(\pathB_testns_UnderScored::class, $loader, true);
        $loader->addBasePath(CLASS_BASE);
        $this->assertClassLoads(under_class::class, $loader, true);
    }

    public function testPrefixClassLoading()
    {
        $loader = new ClassLoader();
        $loader->addPrefixPath(CLASS_BASE . DIRECTORY_SEPARATOR . 'pathA', 'FooBar');
        $loader->addPrefixPath([CLASS_BASE . DIRECTORY_SEPARATOR . 'pathB'], 'FooBar');
        $this->assertClassLoads('FooBar\IsNotClass', $loader, false);
        $this->assertClassLoads(PrefClassA::class, $loader, true);
        $this->assertClassLoads(PrefClassB::class, $loader, true);
    }

    public function testFindingEmptyClass()
    {
        $loader = new ClassLoader();
        $this->assertFalse($loader->findFile(''));
    }

    public function testFindingInEmptyDirectory()
    {
        $loader = new ClassLoader();
        $loader->addBasePath('');
        $loader->setVerbose(true);
        $this->assertFalse($loader->loadClass('ThisClassDoesNotExist'));
    }
}
