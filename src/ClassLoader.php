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
    /**
     * List of base paths for searching class files.
     * @var array
     */
    private $basePaths;

    /**
     * List of prefixed paths where to search for class files.
     * @var array
     */
    private $prefixPaths;

    /**
     * List of file extensions that are used for file inclusion.
     * @var array
     */
    private $fileExtensions;

    /**
     * Whether to look for classes in include_path or not.
     * @var boolean
     */
    private $useIncludePath;

    /**
     * Whether to return values and throw exceptions from loadClass or not
     * @var boolean
     */
    protected $verbose;

    /**
     * The autoload method use to load classes.
     * @var callable
     */
    private $loader;

    /**
     * Creates a new ClassLoader instance.
     */
    public function __construct()
    {
        $this->basePaths = [];
        $this->prefixPaths = [];
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
     * exceptions from the autoloader. By default, the class verbose mode is set
     * to false for PSR-4 compliance.
     *
     * @param boolean $enabled True for return values and exceptions, false for none
     * @return ClassLoader Returns self for call chaining
     */
    public function setVerbose($enabled)
    {
        $this->verbose = (bool) $enabled;
        return $this;
    }

    /**
     * Sets list of dot included file extensions to use for inclusion.
     *
     * Defaults to ['.php']
     *
     * @param array $extensions Array of dot included file extensions to use
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
     * @param string $namespace Limit the path only to specific namespace
     * @return ClassLoader Returns self for call chaining
     */
    public function addBasePath($path, $namespace = null)
    {
        $this->addPath('basePaths', $path, $namespace);
        return $this;
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
     * @param string $namespace The namespace prefix the given path replaces
     * @return ClassLoader Returns self for call chaining
     */
    public function addPrefixPath($path, $namespace = null)
    {
        $this->addPath('prefixPaths', $path, $namespace);
        return $this;
    }

    /**
     * Canonizes the namespaces and paths and adds them to a list.
     * @param string $type Name of the variable where to store
     * @param string|array $path Single path or array of paths
     * @param string|null $namespace The namespace definition
     */
    private function addPath($type, $path, $namespace)
    {
        $paths = $namespace !== null
            ? [$namespace => $path]
            : (!is_array($path) || isset($path[0]) ? ['' => $path] : $path);

        foreach ($paths as $key => $value) {
            $key = $key !== '' ? trim($key, '\\') . '\\' : '';

            if (!isset($this->{$type}[$key])) {
                $this->{$type}[$key] = [];
            }

            foreach ((array) $value as $new) {
                $new = rtrim($new, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $this->{$type}[$key][] = $new;
            }
        }
    }

    /**
     * Attempts to load the class using provided class paths.
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
     * throw an exception if the class name is invalid, it already exists or if
     * the class did not exist in the file that was included.
     *
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false if not
     * @throws \RuntimeException if a file was included but no class was found
     * @throws \InvalidArgumentException If the class name is invalid or already exists
     */
    public function loadClass($class)
    {
        $success = false;

        if ($this->isValidClass($class)) {
            $file = $this->findFile($class);

            if ($file !== false && $this->loadFile($file, $class)) {
                $success = true;
            }
        }

        if ($this->verbose) {
            return $success;
        }
    }

    /**
     * Makes sure the class name is valid and it is not an existing class.
     * @param string $class Full name of the class
     * @return boolean True if the class name is valid, false if not
     * @throws \InvalidArgumentException If the class name is invalid
     */
    private function isValidClass($class)
    {
        $valid = true;

        if (empty($class) || !is_string($class)) {
            $valid = false;

            if ($this->verbose) {
                throw new \InvalidArgumentException("Invalid class name");
            }
        } elseif ($this->classExists($class)) {
            $valid = false;

            if ($this->verbose) {
                throw new \InvalidArgumentException("Attempting to load $class' that already exists");
            }
        }

        return $valid;
    }

    /**
     * Attempts to find a file for the given class using known paths.
     * @param string $class Full name of the class
     * @return string|boolean Path to the class file or false if not found
     */
    public function findFile($class)
    {
        $class = ltrim($class, '\\');
        $file = $this->findFromPrefixPaths($class);

        if ($file === false) {
            $file = $this->findFromBasePaths($class);
        }

        return $file;
    }

    /**
     * Attempts to find the class file using prefix paths.
     * @param string $class Full name of the class
     * @return string|boolean Path to the class file or false if not found
     */
    private function findFromPrefixPaths($class)
    {
        foreach ($this->prefixPaths as $namespace => $paths) {
            $length = strlen($namespace);
            if (strncmp($namespace, $class, $length) === 0) {
                $fname = strtr(substr($class, $length), ['\\' => DIRECTORY_SEPARATOR]);
                if ($fullPath = $this->findPath($paths, $fname)) {
                    return $fullPath;
                }
            }
        }

        return false;
    }

    /**
     * Attempts to find the class file using base paths.
     * @param string $class Full name of the class
     * @return string|boolean Path to the class file or false if not found
     */
    private function findFromBasePaths($class)
    {
        $canon = preg_replace('/_(?=[^\\\\]*$)/', '\\', $class);
        $basePaths = $this->basePaths;

        if ($this->useIncludePath) {
            if (!isset($basePaths[''])) {
                $basePaths[''] = [];
            }

            foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
                $basePaths[''][] = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
        }

        foreach ($basePaths as $namespace => $paths) {
            if (strncmp($namespace, $canon, strlen($namespace)) === 0) {
                $fname = strtr($canon, ['\\' => DIRECTORY_SEPARATOR]);
                if ($fullPath = $this->findPath($paths, $fname)) {
                    return $fullPath;
                }
            }
        }

        return false;
    }

    /**
     * Searches for the class file in given paths.
     * @param array $paths List of paths where to look
     * @param string $fname File name appended to the path
     * @return string|boolean Path to the class file or false if not found
     */
    private function findPath($paths, $fname)
    {
        foreach ($paths as $path) {
            foreach ($this->fileExtensions as $ext) {
                if (file_exists($path . $fname . $ext)) {
                    return $path . $fname . $ext;
                }
            }
        }

        return false;
    }

    /**
     * Includes the file and makes sure the class exists.
     * @param string $file Full path to the file
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false if not
     * @throws \RuntimeException If a file was loaded, but no class was found
     */
    protected function loadFile($file, $class)
    {
        include $file;
        $exists = $this->classExists($class);

        if (!$exists && $this->verbose) {
            throw new \RuntimeException("Included file '$file' did not contain the class '$class'");
        }

        return $exists;
    }

    /**
     * Tells if a class, interface or trait exists with given name.
     * @param string $class Full name of the class
     * @return boolean True if it exists, false if it does not exists
     */
    private function classExists($class)
    {
        return class_exists($class, false) ||
            interface_exists($class, false) ||
            trait_exists($class, false);
    }
}
