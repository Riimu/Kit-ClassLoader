<?php

namespace riimu\kit\ClassLoader;

/**
 * Autoloader for PSR-0 compliant namespace based folder structures.
 *
 * BasePathLoader is PSR-0 compliant autoloader for classes. Classes are loaded
 * from files using folder structures based on their namespaces (or faux
 * namespaces by using underscore in class names). Additionally, multiple and
 * separate paths can defined for different sub namespaces.
 *
 * While namespaces and class names are case insensitive in PHP, this autoloader
 * will treat all definitions as case sensitive due to the fact that numerous
 * file systems are case sensitive. While only classes are mentioned, everything
 * in the autoloader also applies to interfaces and traits.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BasePathLoader
{
    /**
     * Separator that separates paths in include_path ini setting.
     * @var string
     */
    private $includePathSeparator;

    /**
     * Separator the separates folders in directory tree.
     * @var string
     */
    private $directorySeparator;

    /**
     * List of paths where to look for all classes.
     * @var array
     */
    private $basePaths;

    /**
     * List of namespace specific base paths to use for looking for classes.
     * @var array
     */
    private $subPaths;

    /**
     * List of file extensions that are used for file inclusion.
     * @var array
     */
    private $fileExtensions;

    /**
     * Whether to look for classes in include_path or not.
     * @var boolean
     */
    private $loadFromIncludePath;

    /**
     * Whether to throw an exception if class was not contained in the file.
     * @var boolean
     */
    private $throwOnMissingClass;

    /**
     * Whether to throw an exception when class does not exist in namespace specific paths.
     * @var boolean
     */
    private $throwOnMissingSubPath;

    /**
     * The autoload method use to load classes.
     * @var callable
     */
    private $loader;

    /**
     * Creates a new BasePathLoader instance.
     */
    public function __construct()
    {
        $this->includePathSeparator = PATH_SEPARATOR;
        $this->directorySeparator = DIRECTORY_SEPARATOR;
        $this->basePaths = array();
        $this->subPaths = array();
        $this->fileExtensions = array('.php');
        $this->loadFromIncludePath = true;
        $this->throwOnMissingClass = true;
        $this->throwOnMissingSubPath = false;
        $this->loader = array($this, 'load');
    }

    /**
     * Registers this instance as a class autoloader.
     * @return boolean True if the registration was succesful, false if not
     */
    public function register()
    {
        return spl_autoload_register($this->loader);
    }

    /**
     * Unregisters this instance as a class autoloader.
     * @return boolean True if the unregistration was succesful, false otherwise
     */
    public function unregister()
    {
        return spl_autoload_unregister($this->loader);
    }

    /**
     * Tells if this instance is currently registered as a class autoloader.
     * @return boolean True if registered, false if not
     */
    public function isRegistered()
    {
        return in_array($this->loader, spl_autoload_functions(), true);
    }

    /**
     * Tells whether you want to look for classes in include_path or not.
     *
     * Defaults to true.
     *
     * @param boolean $enabled True to use include_path, false to not use
     */
    public function setLoadFromIncludePath($enabled)
    {
        $this->loadFromIncludePath = (bool) $enabled;
    }

    /**
     * Enables exceptions if the class does not exist in an included file.
     *
     * Defaults to true.
     *
     * @param boolean $enabled True to throw an exception, false to not
     */
    public function setThrowOnMissingClass($enabled)
    {
        $this->throwOnMissingClass = (bool) $enabled;
    }

    /**
     * Enables exceptions if class did not exist in namespace specific path.
     *
     * When enabled and a namespace specific path is defined for a class, an
     * exception will be thrown if the class could not be loaded from any
     * path defined for any namespace that matches the class.
     *
     * Defaults to false.
     *
     * @param boolean $enabled True to throw an exception, false to not
     */
    public function setThrowOnMissingSubPath($enabled)
    {
        $this->throwOnMissingSubPath = (bool) $enabled;
    }

    /**
     * Sets list of dot included file extensions to use for inclusion.
     *
     * Defaults to ['.php']
     *
     * @param array $extensions Array of dot included file extensions to use
     */
    public function setFileExtensions(array $extensions)
    {
        $this->fileExtensions = $extensions;
    }

    /**
     * Adds a path where to look for all classes.
     * @param string|array $path Single path or multiple paths
     */
    public function addBasePath($path)
    {
        $this->basePaths = array_merge($this->canonizePaths($path), $this->basePaths);
    }

    /**
     * Adds a path that is used to look for classes in specific namespace.
     *
     * The paths can be provided as a string containing a single path or an
     * array containing multiple paths. Additionally, instead of providing
     * two arguments, you can provide a single array, where keys indicate
     * namespaces and the values are the paths. The namespace may contain any
     * number of sub namespaces and even the name of the class.
     *
     * Note that the path must point to base path for the loaded class. For
     * example, if the classes for the namespace "vendor\foo\bar" are in
     * "/usr/lib/vendor/foo/bar", the you could call the function like
     *
     * <pre>$loader->addNamespacePath('vendor\foo\bar', '/usr/lib')</pre>
     *
     * @param string|array $namespace Namespace or array definition
     * @param string|array $path Single path or multiple paths
     */
    public function addNamespacePath($namespace, $path = '.')
    {
        $paths = func_num_args() == 1 ? $namespace : [$namespace => $path];

        foreach($paths as $namespace => $path) {
            $parts = $this->getParts(rtrim($namespace, '_\\'));
            $namespace = implode('\\', $parts) . '\\';
            $path = $this->canonizePaths($path);

            if (!isset($this->subPaths[$parts[0]][$namespace])) {
                $this->subPaths[$parts[0]][$namespace] = $path;
            } else {
                $this->subPaths[$parts[0]][$namespace] = array_merge(
                    $this->subPaths[$parts[0]][$namespace], $path);
            }
        }
    }

    /**
     * Makes sure that the paths are followed by directory separator.
     * @param string|array $paths Single path or multiple paths
     * @return array Canonized paths in an array
     */
    private function canonizePaths($paths)
    {
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $key => $path) {
            if (substr($path, -1) != $this->directorySeparator) {
                $paths[$key] .= $this->directorySeparator;
            }
        }

        return $paths;
    }

    /**
     * Attemps to load the class from any known path.
     *
     * The name of the class is treated according to PSR-0. Namespace separators
     * and underscores in the class name are replace with directory separators.
     * The file is then searched in any path added for that spesific namespace,
     * in any general base path or in include_path, if allowed, in that order.
     *
     * If enabled, exceptions can be thrown if an included file does not contain
     * the class it is supposed to contain. Additionally, an exception can be
     * thrown if a namespace specific path existed for that class, but that
     * class was not found in any of those paths. Loading a class that already
     * exists will also cause an exception.
     *
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false otherwise
     * @throws \RuntimeException If the class could not be loaded from expected path
     * @throws \InvalidArgumentException If the class name is invalid or already exists
     */
    public function load($class)
    {
        if (empty($class) || !is_string($class)) {
            throw new \InvalidArgumentException("Invalid class name");
        } elseif ($this->exists($class)) {
            throw new \InvalidArgumentException("Attempting to load class " .
                "'$class' that already exists");
        }

        $parts = $this->getParts($class);
        $file = implode($this->directorySeparator, $parts);
        $loaded = false;

        if (isset($this->subPaths[$parts[0]])) {
            $loaded = $this->loadFromSubPaths($parts, $this->subPaths[$parts[0]], $class, $file);
        }
        if (!$loaded && !empty($this->basePaths)) {
            $loaded = $this->loadFromPaths($this->basePaths, $class, $file);
        }
        if (!$loaded && $this->loadFromIncludePath) {
            $paths = explode($this->includePathSeparator, get_include_path());
            $loaded = $this->loadFromPaths($this->canonizePaths($paths), $class, $file);
        }

        return $loaded;
    }

    /**
     * Attempts to load the class for sub paths for one namespace.
     * @param array $parts The class name separated into namespace parts
     * @param array $base The base paths define for the namespace
     * @return boolean True if the class was loaded, false if not
     * @throws \RuntimeException If class was not in any matching path
     */
    private function loadFromSubPaths(array $parts, array $base, $class, $file)
    {
        $combined = '';
        $paths = [];

        foreach ($parts as $part) {
            $combined .= $part . '\\';
            if (isset($base[$combined])) {
                $paths = array_merge($paths, $base[$combined]);
            }
        }

        $loaded = $this->loadFromPaths(array_reverse($paths), $class, $file);

        if (!$loaded && !empty($paths) && $this->throwOnMissingSubPath) {
            throw new \RuntimeException("The class '$class' could " .
                "not be loaded from any matching namespace specific paths");
        }

        return $loaded;
    }

    /**
     * Attempts to load the class from any given path.
     * @param array $paths Paths where to look for the class file
     * @return boolean true if the class was loaded, false if not
     */
    private function loadFromPaths(array $paths, $class, $file)
    {
        foreach ($paths as $path) {
            foreach ($this->fileExtensions as $ext) {
                if ($this->loadFromFile($class, $path . $file . $ext)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Attempts to load the class from given file.
     * @param string $file File where the class could exist
     * @return boolean True if the class was loaded, false if not
     * @throws \RuntimeException If class was not in the loaded file
     */
    private function loadFromFile($class, $file)
    {
        $loaded = false;

        if (file_exists($file)) {
            require $file;
            $loaded = $this->exists($class);

            if (!$loaded && $this->throwOnMissingClass) {
                throw new \RuntimeException("Loaded file '$file' but it did " .
                    "contain the class '$class'");
            }
        }

        return $loaded;
    }

    /**
     * Separates the class name into namespaces according to PSR-0.
     * @param string $class Name of the class
     * @return array Class name exploded into parts
     */
    private function getParts($class) {
        $parts = explode('\\', ltrim($class, '\\'));
        $last = explode('_', array_pop($parts));
        return array_merge($parts, $last);
    }

    /**
     * Tells if the given class is already defined.
     * @param string $name Name of the class
     * @return boolean True if it exists, false if it does not exists
     */
    private function exists($class) {
        return class_exists($class) ||
            interface_exists($class) ||
            trait_exists($class);
    }
}
