<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Class autoloader with PSR-0 and PSR-4 compatibility.
 *
 * ClassLoader provides both PSR-0 and PSR-4 compliant class autoloading. Paths
 * for classes can be provided as base paths or prefixed class paths.
 *
 * When base paths are provided, classes are searched in given paths replacing
 * all the namespace separators (and underscores in the class name) with
 * directory separators (as per PSR-0). With prefixed paths, part of the
 * namespace can be replaced with a specific path and the underscores in the
 * class name are ignored (as per PSR-4).
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ClassLoader
{
    /** @var array List of PSR-4 compatible paths by namespace */
    private $prefixPaths;

    /** @var array List of PSR-0 compatible paths by namespace */
    private $basePaths;

    /** @var string[] List of file extensions used to find files */
    private $fileExtensions;

    /** @var boolean Whether to look for classes in include_path or not */
    private $useIncludePath;

    /** @var callable The autoload method use to load classes */
    private $loader;

    /** @var boolean Whether loadClass should return values and throw exceptions or not */
    protected $verbose;

    /**
     * Creates a new ClassLoader instance.
     */
    public function __construct()
    {
        $this->prefixPaths = [];
        $this->basePaths = [];
        $this->fileExtensions = ['.php'];
        $this->useIncludePath = false;
        $this->verbose = true;
        $this->loader = [$this, 'loadClass'];
    }

    /**
     * Registers this instance as a class autoloader.
     * @return boolean True if the registration was successful, false if not
     */
    public function register()
    {
        return spl_autoload_register($this->loader);
    }

    /**
     * Unregisters this instance as a class autoloader.
     * @return boolean True if the unregistration was successful, false if not
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
     * Tells whether to use include_path as part of base paths.
     *
     * When enabled, the directory paths in include_path are treated as base
     * paths where to look for classes. This option defaults to false for PSR-4
     * compliance.
     *
     * @param boolean $enabled True to use include_path, false to not use
     * @return ClassLoader Returns self for call chaining
     */
    public function useIncludePath($enabled = true)
    {
        $this->useIncludePath = (bool) $enabled;
        return $this;
    }

    /**
     * Sets whether to return values and throw exceptions from loadClass.
     *
     * PSR-4 requires that autoloaders do not return values and do not throw
     * exceptions from the autoloader. By default, the verbose mode is set to
     * false for PSR-4 compliance.
     *
     * @param boolean $enabled True to return values and exceptions, false to not
     * @return ClassLoader Returns self for call chaining
     */
    public function setVerbose($enabled)
    {
        $this->verbose = (bool) $enabled;
        return $this;
    }

    /**
     * Sets list of dot included file extensions to use for finding files.
     *
     * Defaults to ['.php']
     *
     * @param string[] $extensions Array of dot included file extensions to use
     * @return ClassLoader Returns self for call chaining
     */
    public function setFileExtensions(array $extensions)
    {
        $this->fileExtensions = $extensions;
        return $this;
    }

    /**
     * Adds a PSR-0 compliant base path for searching classes.
     *
     * In PSR-0, the class namespace structure directly reflects their location
     * in the directory tree. Adding a base path tells the base directories
     * where to look for classes. For example, if the class 'Foo\Bar', is
     * located in '/usr/lib/Foo/Bar.php', you would need to add '/usr/lib' as a
     * base path.
     *
     * Additionally, you may specify that the base path applies only to a
     * specific namespace. For example, if in the above example, you would
     * want the the base path to only apply to 'Foo' namespace, you could
     * add 'Foo' as the namespace parameter.
     *
     * Note that as per PSR-0, the underscores in the class name are treated
     * as namespace separators. Therefore 'Foo_Bar_Baz', would need to reside
     * in 'Foo/Bar/Baz.php'. Regardless of whether the namespace is indicated
     * by namespace separators or underscores, the namespace parameter must be
     * defined using namespace separators, e.g 'Foo\Bar'.
     *
     * You may also provide an array of paths, instead of just single path. You
     * may also provide an associative array where keys indicate the namespace
     * and the values are either a single path or array of paths.
     *
     * @param string|array $path Single path or array of paths
     * @param string|null $namespace Limit the path only to specific namespace
     * @return ClassLoader Returns self for call chaining
     */
    public function addBasePath($path, $namespace = null)
    {
        $this->addPath($this->basePaths, $path, $namespace);
        return $this;
    }

    /**
     * Returns all added base paths as an array.
     *
     * The paths will be returned in an associative array, in which the key
     * represents the namespace. Paths without namespace can be found in the
     * key '' (empty string).
     *
     * @return mixed All added base paths.
     */
    public function getBasePaths()
    {
        return $this->basePaths;
    }

    /**
     * Adds a PSR-4 compliant prefixed path for searching classes.
     *
     * In PSR-4, it is possible to replace part of namespace with specific
     * path in the directory tree instead of requiring the entire namespace
     * structure to be present in the namespace. For example, if the class
     * 'Vendor\Library\Class' is located in'/usr/lib/Library/src/Class.php',
     * You would just need to add the path '/usr/lib/Library/src' to namespace
     * 'Vendor\Library'.
     *
     * If the method is called without providing a namespace, then the paths
     * work similarly to paths added via addBasePath(), except that the
     * underscores in the file name are not treated as namespace separators.
     *
     * Similarly to addBasePath(), the paths may be provided as an array or you
     * can just provide a single associative array as the parameter.
     *
     * @param string|array $path Single path or array of paths
     * @param string|null $namespace The namespace prefix the given path replaces
     * @return ClassLoader Returns self for call chaining
     */
    public function addPrefixPath($path, $namespace = null)
    {
        $this->addPath($this->prefixPaths, $path, $namespace);
        return $this;
    }

    /**
     * Returns all added prefix paths ar an array.
     *
     * The paths will be returned in an associative array, in which the key
     * represents the namespace. Paths without namespace can be found in the
     * key '' (empty string).
     *
     * @return mixed All added prefix paths.
     */
    public function getPrefixPaths()
    {
        return $this->prefixPaths;
    }

    /**
     * Canonizes the namespaces and paths and adds them to a list.
     * @param string $list List of paths to modify
     * @param string|array $path Single path or array of paths
     * @param string|null $namespace The namespace definition
     */
    private function addPath(& $list, $path, $namespace)
    {
        if ($namespace !== null) {
            $paths = [$namespace => $path];
        } else {
            $paths = is_array($path) ? $path : ['' => $path];
        }

        foreach ($paths as $ns => $directories) {
            $this->addNamespacePaths($list, is_int($ns) ? '' : $ns, $directories);
        }
    }

    private function addNamespacePaths(& $list, $namespace, $paths)
    {
        $namespace = $namespace === '' ? '' : trim($namespace, '\\') . '\\';

        if (!isset($list[$namespace])) {
            $list[$namespace] = [];
        }

        if (is_array($paths)) {
            $list[$namespace] = array_merge($list[$namespace], $paths);
        } else {
            $list[$namespace][] = $paths;
        }
    }

    /**
     * Attempts to load the class using known class paths.
     *
     * The class is first attempted to load using the prefixed paths and then
     * using the base paths. If the use of include_path is enabled, the paths
     * in include_path are added to the list of base paths.
     *
     * The classes are searched from the prefix and base paths in the order they
     * were added to the class loader and only the first found matching file
     * is loaded.
     *
     * If verbose mode is enabled, then the method will return true if the class
     * loading was successful and false if not. Additionally the method will
     * throw an exception if the class already exists or if the class was not
     * defined in the file that was included.
     *
     * @param string $class Full name of the class
     * @return boolean|null True if the class was loaded, false if not
     * @throws \RuntimeException If the class was not defined in the included file
     * @throws \InvalidArgumentException If the class already exists
     */
    public function loadClass($class)
    {
        if ($this->verbose) {
            return $this->load($class);
        }

        try {
            $this->load($class);
        } catch (\Exception $exception) {
            // Ignore exceptions as per PSR-4
        }
    }

    private function load($class)
    {
        if ($this->isLoaded($class)) {
            throw new \InvalidArgumentException("Attempting to load class '$class' that already exists'");
        }

        if ($file = $this->findFile($class)) {
            return $this->loadFile($file, $class);
        }

        return false;
    }

    /**
     * Attempts to find a file for the given class using known paths.
     * @param string $class Full name of the class
     * @return string|false Path to the class file or false if not found
     */
    public function findFile($class)
    {
        $class = ltrim($class, '\\');

        if ($file = $this->findNamespace($this->prefixPaths, $class, true)) {
            return $file;
        }

        $class = preg_replace('/_(?=[^\\\\]*$)/', '\\', $class);

        if ($file = $this->findNamespace($this->basePaths, $class, false)) {
            return $file;
        } elseif ($this->useIncludePath) {
            return $this->findDirectory(explode(PATH_SEPARATOR, get_include_path()), $class);
        }

        return false;
    }

    /**
     * Attempt finding the class                                                                                                        file in namespace specific paths
     * @param array $paths Namespace path definitions
     * @param string $class Canonized class name
     * @param boolean $truncate True to remove namespace from file path
     * @return string|false Path to the class file or false if not found
     */
    private function findNamespace($paths, $class, $truncate)
    {
        foreach ($paths as $namespace => $directories) {
            if ($fullPath = $this->findFullPath($class, $namespace, $directories, $truncate)) {
                return $fullPath;
            }
        }

        return false;
    }

    private function findFullPath($class, $namespace, $directories, $truncate)
    {
        if (strncmp($class, $namespace, strlen($namespace)) !== 0) {
            return false;
        }

        return $this->findDirectory(
            $directories,
            $truncate ? substr($class, strlen($namespace)) : $class
        );
    }

    /**
     * Searches for the class file in the given directories.
     * @param string[] $directories List of directory paths where to look for the class
     * @param string $class Part of the class name that translates to the file name
     * @return string|false Path to the class file or false if not found
     */
    private function findDirectory($directories, $class)
    {
        foreach ($directories as $directory) {
            $directory = trim($directory);
            $path = preg_replace('/[\\/\\\\]+/', DIRECTORY_SEPARATOR, $directory . '/' . $class);

            if ($directory && $fullPath = $this->findExtension($path)) {
                return $fullPath;
            }
        }

        return false;
    }

    private function findExtension($path)
    {
        foreach ($this->fileExtensions as $ext) {
            if (file_exists($path . $ext)) {
                return $path . $ext;
            }
        }

        return false;
    }

    /**
     * Includes the file and makes sure the class exists.
     * @param string $file Full path to the file
     * @param string $class Full name of the class
     * @return boolean Always returns true
     * @throws \RuntimeException If the class was not defined in the included file
     */
    protected function loadFile($file, $class)
    {
        include $file;

        if (!$this->isLoaded($class)) {
            throw new \RuntimeException("The included '$file' did not define class '$class'");
        }

        return true;
    }

    /**
     * Tells if a class, interface or trait exists with given name.
     * @param string $class Full name of the class
     * @return boolean True if it exists, false if it does not exists
     */
    private function isLoaded($class)
    {
        return class_exists($class, false) ||
            interface_exists($class, false) ||
            trait_exists($class, false);
    }
}
