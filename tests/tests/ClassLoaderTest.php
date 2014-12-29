<?php

namespace Riimu\Kit\ClassLoader;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
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

    public function testRegisteringMultipleTimes ()
    {
        $loader = new ClassLoader();
        $loader->register();
        $this->assertTrue($loader->register());
        $this->assertTrue($loader->unregister());
        $this->assertFalse($loader->isRegistered());
        $this->assertFalse($loader->unregister());
    }

    public function testRegisteringMultipleLoaders ()
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
        $loader = $this->getMock('Riimu\Kit\ClassLoader\ClassLoader', ['loadClass']);
        $loader->expects($this->once())->method('loadClass')->will($this->returnValue(false));

        $this->assertTrue($loader->register());
        $this->assertFalse(class_exists('ThisClassDoesNotExist'));
        $this->assertTrue($loader->unregister());
    }

    public function testMissingClass ()
    {
        $loader = new ClassLoader();
        $this->assertClassLoads('ThisClassDoesNotExist', $loader, false);
    }

    public function testBasePath()
    {
        $loader = new ClassLoader();
        $this->assertClassLoads('BaseDirClass', $loader, false);
        $loader->addBasePath(CLASS_BASE);
        $this->assertClassLoads('BaseDirClass', $loader, true);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadingExistingClass()
    {
        $loader = new ClassLoader();
        $loader->setVerbose(false);
        $this->assertSame(null, $loader->loadClass('Riimu\Kit\ClassLoader\ClassLoader'));
        $loader->setVerbose(true);
        $loader->loadClass('Riimu\Kit\ClassLoader\ClassLoader');
    }

    public function testLoadingViaIncludePath()
    {
        $loader = new ClassLoader();
        $includePath = get_include_path();

        $loader->useIncludePath(false);
        $this->assertClassLoads('pathSuccess', $loader, false);
        $loader->useIncludePath(true);
        $this->assertClassLoads('pathSuccess', $loader, false);

        set_include_path(get_include_path() . PATH_SEPARATOR . CLASS_BASE . DIRECTORY_SEPARATOR . 'include_path');
        $this->assertClassLoads('pathSuccess', $loader, true);

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
        $this->assertSame(null, $loader->loadClass('NoClassHere'));
        return $loader;
    }

    /**
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
        $this->assertClassLoads('testns\nsClass', $loader, true);
        $this->assertSame('B', \testns\nsClass::$source);
    }

    public function testAutoChainingDifferentTypes()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE);
        $loader->register();
        $this->assertTrue(class_exists('SomeClass', true));
        $this->assertTrue(interface_exists('SomeInterface', false));
        $this->assertTrue(trait_exists('SomeTrait', false));
        $loader->unregister();
    }

    public function testDifferentExtensions()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(CLASS_BASE);
        $loader->setFileExtensions(['.inc']);
        $this->assertClassLoads('DifferentExt', $loader, true);
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
        $this->assertClassLoads('pathB_testns_UnderScored', $loader, true);
        $loader->addBasePath(CLASS_BASE);
        $this->assertClassLoads('under_ns\under_class', $loader, true);
    }

    public function testPrefixClassLoading()
    {
        $loader = new ClassLoader();
        $loader->addPrefixPath(CLASS_BASE . DIRECTORY_SEPARATOR . 'pathA', 'FooBar');
        $loader->addPrefixPath([CLASS_BASE . DIRECTORY_SEPARATOR . 'pathB'], 'FooBar');
        $this->assertClassLoads('FooBar\IsNotClass', $loader, false);
        $this->assertClassLoads('\FooBar\PrefClassA', $loader, true);
        $this->assertClassLoads('FooBar\testns\PrefClassB', $loader, true);
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
