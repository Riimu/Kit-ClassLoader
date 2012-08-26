<?php

namespace Rose\ClassLoader;

/**
 * Class autoloader for mapping class names to folder structures.
 *
 * ClassPathLoader is for loading classes that have namespaces correspoding to
 * their folder structure. Classes can be loaded from paths added to the class
 * loader or from include_path. Additionally, you may define separate paths for
 * different vendors. Namespaces are understood as defined in PSR-0. Namespace
 * separators and underscores in the class name are assumed to mean folders
 * in the file system.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 */
class ClassPathLoader
{
    /**
     * Separator that separates path in include_path ini setting.
     * @var string
     */
    private $pathSeparator;

    /**
     * Separator the separates folders in directory tree.
     * @var string
     */
    private $directorySeparator;

    /**
     * List of paths where class base namespaces are looked for.
     * @var array
     */
    private $libraryPaths;

    /**
     * List of paths where vendor specific namespaces are stored.
     * @var array
     */
    private $vendorPaths;

    /**
     * List of file extensions that are used for file inclusion.
     * @var array
     */
    private $fileExtensions;

    /**
     * Whether to look into include paths or not.
     * @var boolean
     */
    private $loadFromIncludePath;

    /**
     * Whether throw on error when file did not contain advertised class or not.
     * @var boolean
     */
    private $throwOnFileError;

    /**
     * Whether to throw error when registered vendor did not have loaded class.
     * @var boolean
     */
    private $throwOnVendorError;

    /**
     * The autoload method use to load classes.
     * @var callable
     */
    private $loader;

    /**
     * Creates a new ClassPathLoader instance.
     */
    public function __construct()
    {
        $this->pathSeparator = PATH_SEPARATOR;
        $this->directorySeparator = DIRECTORY_SEPARATOR;
        $this->libraryPaths = array();
        $this->vendorPaths = array();
        $this->fileExtensions = array('.php');
        $this->loadFromIncludePath = true;
        $this->throwOnFileError = true;
        $this->throwOnVendorError = false;
        $this->loader = array($this, 'loadClass');
    }

    /**
     * Registers the ClassPathLoader as a class autoloader.
     * @return boolean Whether the registration was succesful or not
     */
    public function register()
    {
        return spl_autoload_register($this->loader);
    }

    /**
     * Unregisters the ClassPathLoader as a class autoloader.
     * @return boolean True if the loader was succesfully unregistered, false otherwise
     */
    public function unregister()
    {
        return spl_autoload_unregister($this->loader);
    }

    /**
     * Tells if the ClassPathLoader is currently registered as a class autoloader.
     * @return boolean True if registered, false if not
     */
    public function isRegistered()
    {
        return in_array($this->loader, spl_autoload_functions(), true);
    }

    /**
     * Tells whether to look for classes in include paths.
     * @param boolean $enabled True to look for, false to not
     */
    public function setLoadFromIncludePath($enabled)
    {
        $this->loadFromIncludePath = (bool) $enabled;
    }

    /**
     * Tells whether to throw an exception when class does not exist in its file.
     * @param boolean $enabled True to throw an exception, false to not
     */
    public function setThrowOnFileError($enabled)
    {
        $this->throwOnFileError = (bool) $enabled;
    }

    /**
     * Tells whether to throw an exception when class does not exist in its vendor path.
     *
     * Enabling this will cause an exception, if attempting to autoload a class
     * that mathes any of the registered vendor paths and the class file does
     * not exist. Note that this will cause class_exists() to throw an exception,
     * if the vendor path is registered.
     *
     * @param boolean $enabled True to throw an exception, false to not
     */
    public function setThrowOnVendorError($enabled)
    {
        $this->throwOnVendorError = (bool) $enabled;
    }

    /**
     * Sets list of file extensions (dot included) to look for.
     * @param array $extensions List of file extensions to use for looking files
     */
    public function setFileExtensions(array $extensions)
    {
        $this->fileExtensions = $extensions;
    }

    /**
     * Adds a path where to look for class files to load.
     * @param string $path Path to a base directory for classes
     */
    public function addLibraryPath($path)
    {
        $this->libraryPaths[] = (string) $path;
    }

    /**
     * Adds a vendor spesific path where to look for that vendor's classes.
     *
     * The vendor simply means the first namespace of the class. You may also
     * define separate paths for sub namespaces inside the vendor. The sub
     * namespaces should be separated by backslash, regardless of whether the
     * class is defined with underscores or via namespace.
     *
     * For example:
     * $loader->addVendorPath('YourVendor', '/www/YourFiles');
     * would cause any class that has namespace beginning with "YourVendor" or
     * the class name beginning with "YourVendor_" would be looked for in the
     * directory "/www/YourFiles"
     *
     * $loader->addVendorPath('YourVendor\Other', '/www/otherfiles');
     * would cause classes with namespace YourVendor\Other or name beginning with
     * "YourVendor_Other_" to be looked for in the directory "/www/otherfiles".
     *
     * The appropriate parts are, of course, removed from the directory path,
     * i.e. the loader would expect to find "YourVendor_Foo_Bar" inside
     * "/www/YourFiles/Foo/Bar.php" and "YourVendor_Other_Foo" inside
     * "/www/otherfiles/Foo.php".
     *
     * @param string $vendor Name of the vendor
     * @param string $path Path where to look for the vendor's files
     */
    public function addVendorPath($vendor, $path)
    {
        $vendor = rtrim($vendor, '\\');
        list($vendorName) = explode('\\', $vendor);

        if (!isset($this->vendorPaths[$vendorName][$vendor])) {
            $this->vendorPaths[$vendorName][$vendor] = array();
            krsort($this->vendorPaths[$vendorName]);
        }

        $this->vendorPaths[$vendorName][$vendor][] = $path;
    }

    /**
     * Attemps to load the class from any known allowed paths.
     *
     * In the name of the class, both underscores and backslashes are treated as
     * namespace separators. Namespaces are used to determine the folders where
     * the class resides and the name of the class is used to determine the file
     * name.
     *
     * The loader will first attempt to look for the file in any registered
     * vendor path. If the class matches multiple vendor paths (i.e. you have
     * defined different paths for sub namespaces), it will first look at the
     * most accurate one and then fall back to less accurate ones.
     *
     * If the class is not loaded via vendor path, the loader will then look
     * into any added library path. Finally, if allowed, the loader will look
     * into paths provided by include_path.
     *
     * Exceptions can be thrown, if allowed, when either the class matched any
     * registered vendor, but the class could not be found or if a matching file
     * was found but it did not actually contains the class.
     *
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false otherwise
     * @throws RuntimeException If file was expected to be found but did not load
     */
    public function loadClass($class)
    {
        if (class_exists($class, false)) {
            throw new \InvalidArgumentException("Attempting to load existing class '$class'");
        }

        $parts = $this->getParts($class);

        if (count($parts) > 1 && isset($this->vendorPaths[$parts[0]])) {
            if ($this->loadFromVendorPath($parts[0], $class, $parts)) {
                return true;
            }
        }

        if ($this->loadClassFromPaths($class, $this->libraryPaths, $parts)) {
            return true;
        }

        if ($this->loadFromIncludePath) {
            if ($this->loadClassFromPaths($class, explode($this->pathSeparator, get_include_path()), $parts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Separates the class name into file path parts.
     * @param string $class Name of the class
     * @return array Class name exploded into path parts
     */
    protected function getParts($class)
    {
        $parts = explode('\\', ltrim($class, '\\'));
        $class = explode('_', array_pop($parts));
        return array_merge($parts, $class);
    }

    /**
     * Loads a class from given vendor path.
     * @param string $vendor Name of the vendor
     * @param string $class Original name of the class
     * @param array $parts Separated parts from the class name
     * @return boolean True if loaded succesfully, false if not
     * @throws RuntimeException If the class was expected, but not found
     */
    private function loadFromVendorPath($vendor, $class, array $parts)
    {
        $found = false;
        $fullClass = implode('\\', $parts);

        foreach ($this->vendorPaths[$vendor] as $sub => $paths) {
            if (strpos($fullClass, $sub . '\\') === 0) {
                $lastParts = array_slice($parts, substr_count($sub, '\\') + 1);
                if ($this->loadClassFromPaths($class, $paths, $lastParts)) {
                    return true;
                }
                $found = true;
            }
        }

        if ($found && $this->throwOnVendorError) {
            throw new \RuntimeException("Could not load class '$class' from registered vendor paths");
        }

        return false;
    }

    /**
     * Attemps to load given class from given paths.
     * @param string $class Original name of the class
     * @param array $paths Paths where to look for the class file
     * @param array $parts Separated parts of the class name
     * @return boolean True if the class was loaded, false if not
     * @throws RuntimeException If the file was included, but the class was not loaded
     */
    private function loadClassFromPaths($class, array $paths, array $parts)
    {
        foreach ($paths as $path) {
            $fullPath = implode($this->directorySeparator, array_merge(array($path), $parts));

            foreach ($this->fileExtensions as $extension) {
                $file = $fullPath . $extension;

                if (file_exists($file)) {
                    require_once $file;

                    if (!class_exists($class, false)) {
                        if ($this->throwOnFileError) {
                            throw new \RuntimeException("The class '$class' did not exist in '$file'");
                        } else {
                            return false;
                        }
                    }

                    return true;
                }
            }
        }

        return false;
    }
}
