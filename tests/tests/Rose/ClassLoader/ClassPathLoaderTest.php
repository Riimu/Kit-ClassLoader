<?php

namespace Rose\ClassLoader;

/**
 * Tests for ClassPathLoader.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class ClassPathLoaderTest extends \PHPUnit_Framework_TestCase
{
	public function testConstructor ()
	{
		$ml = new ClassPathLoader();
		$this->assertInstanceOf('Rose\ClassLoader\ClassPathLoader', $ml);
		return $ml;
	}

	/**
	 * @depends testConstructor
	 */
	public function testRegistration ($ml)
	{
		$this->assertTrue($ml->register());
		return $ml;
	}

	/**
	 * @depends testRegistration
	 */
	public function testRegisteredCheck ($ml)
	{
		$this->assertTrue($ml->isRegistered());
		return $ml;
	}

	/**
	 * @depends testRegisteredCheck
	 */
	public function testUnRegistration ($ml)
	{
		$this->assertTrue($ml->unregister());
	}

	public function testMultipleRegistration ()
	{
		$ml = new ClassPathLoader();
		$ml->register();
		$this->assertTrue($ml->register());
		$this->assertTrue($ml->unregister());
		$this->assertFalse($ml->unregister());
		$this->assertFalse($ml->isRegistered());
	}

	public function testMultipleLoaderRegistrations ()
	{
		$ml = new ClassPathLoader();
		$ml2 = new ClassPathLoader();

		$ml->register();
		$this->assertFalse($ml2->isRegistered());
		$this->assertTrue($ml2->register());
		$this->assertTrue($ml2->isRegistered());
		$this->assertTrue($ml->isRegistered());
		$this->assertTrue($ml->unregister());
		$this->assertTrue($ml2->isRegistered());
		$this->assertTrue($ml2->unregister());
	}

	public function testRegisteredCall()
	{
		$ml = $this->getMock('Rose\ClassLoader\ClassPathLoader', array('loadClass'));
		$ml->expects($this->once())->method('loadClass')->will($this->returnValue(false));

		$this->assertTrue($ml->register());
		$this->assertFalse(class_exists('NotExistantToTriggerAutoLoad'));
		$this->assertTrue($ml->unregister());
	}

	public function testFailedLoading ()
	{
		$ml = new ClassPathLoader();
		$this->loadTest($ml, 'NoThisDoesNotActuallyExist', false);
	}

	public function testIncludePathLoading ()
	{
		$path = get_include_path();
		set_include_path($path . PATH_SEPARATOR . CLASS_BASE);

		$ml = new ClassPathLoader();
		$this->loadTest($ml, 'Include_Dummy');

		$ml->setLoadFromIncludePath(false);
		$this->loadTest($ml, 'Include_NotLoaded', false);

		set_include_path($path);
	}

	public function testLibraryPathLoading ()
	{
		$ml = $this->getLibraryLoader();
		$this->loadTest($ml, 'Library_Dummy');
        return $ml;
	}

    /**
     * @expectedException InvalidArgumentException
     * @depends testLibraryPathLoading
     */
    public function testDoubleLoadingFailure($ml)
    {
        $ml->loadClass('Library_Dummy');
    }

	public function testExtensionLoading ()
	{
		$ml = $this->getLibraryLoader();
		$ml->setFileExtensions(array('.php'));
		$this->loadTest($ml, 'Library_IncExt', false);
		$ml->setFileExtensions(array('.php', '.inc.php'));
		$this->loadTest($ml, 'Library_IncExt');
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testExceptionOnFile ()
	{
		$ml = $this->getLibraryLoader();
		$ml->loadClass('Library_Typoed');
	}

	public function testNoExceptionOnFile ()
	{
		$ml = $this->getLibraryLoader();
		$ml->setThrowOnFileError(false);
		$this->loadTest($ml, 'Library_Typoed2', false);
	}

	public function testVendorPathLoading ()
	{
		$ml = $this->getVendorLoader();
		$this->loadTest($ml, 'Vendor_Dummy');
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testMissingVendorClassException ()
	{
		$ml = $this->getVendorLoader();
        $ml->setThrowOnVendorError(true);
		$ml->loadClass('Vendor_NonExtistant');
	}

	public function testMissingVendorClassNoException ()
	{
		$ml = $this->getVendorLoader();
		$ml->setThrowOnVendorError(false);
		$this->loadTest($ml, 'Vendor_NonExistant2', false);
	}

	public function testLoadingFromVendorSubPath ()
	{
		$ml = $this->getVendorLoader();
		$this->loadTest($ml, 'Vendor_RealSub_Dummy');
	}

	public function testLoadingFromDifferentSubPath ()
	{
		$ml = $this->getVendorLoader();
		$ml->addVendorPath('Vendor\OtherSub', $this->path('Vendor', 'FakeSub'));
		$this->loadTest($ml, 'Vendor_OtherSub_Fake');
	}

	public function testVendorLoadingFallback ()
	{
		$ml = $this->getVendorLoader();
		$ml->addVendorPath('Vendor\RealSub', $this->path('Vendor', 'FakeSub'));
		$this->loadTest($ml, 'Vendor_RealSub_Right');
		$this->loadTest($ml, 'Vendor_RealSub_Wrong');
		return $ml;
	}

	/**
	 * @depends testVendorLoadingFallback
	 */
	public function testClassOverride ()
	{
		// Tests that the class loaded by previous test was the correct class
		$right = new \Vendor_RealSub_Right();
		$this->assertEquals('override', $right->type);
	}

    public function testNamespaceLoading ()
    {
        $ml = $this->getVendorLoader();
        $this->loadTest($ml, 'Vendor\RealSub\Namespaced');
    }

    public function testMixedNamespaceDefinitions ()
    {
        $ml = $this->getVendorLoader();
        $this->loadTest($ml, '\Vendor\RealSub_Mixed');
    }

	/**
	 * @return \Rose\ClassLoader\ClassPathLoader
	 */
	private function getLoader ()
	{
		$ml = new ClassPathLoader();
		$ml->setLoadFromIncludePath(false);
		return $ml;
	}

	/**
	 * @return \Rose\ClassLoader\ClassPathLoader
	 */
	private function getLibraryLoader ()
	{
		$ml = $this->getLoader();
		$ml->addLibraryPath($this->path());
		return $ml;
	}

	private function getVendorLoader ()
	{
		$ml = $this->getLoader();
		$ml->addVendorPath('Vendor', $this->path('Vendor'));
		return $ml;
	}

	private function path ()
	{
		$paths = func_get_args();
		return implode(
			DIRECTORY_SEPARATOR,
			array_merge(array(CLASS_BASE), $paths)
		);
	}

	private function loadTest (ClassPathLoader $ml, $class, $exists = true)
	{
		$this->assertEquals($exists, $ml->loadClass($class));
		$this->assertEquals($exists, class_exists($class, false));
	}
}
