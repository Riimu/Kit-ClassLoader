<?php

namespace Riimu\Kit\ClassLoader;

/**
 * Class autoloader with PSR-0 and PSR-4 compatibility.
 *
 * BasePathLoader provides both PSR-0 compliant class autoloading and PSR-4
 * compliant class autoloading. When classes are loaded using base paths or
 * namespace paths, they are handled as instructed in PSR-0 and classes loaded
 * by adding prefix paths are handled according to PSR-4.
 *
 * In PSR-0, underscores in the class name are treated as namespace separators
 * which are translated to directory separators in the directory system. This
 * does not happen in PSR-4 class loading. By default, the class also does not
 * return values from the autoload method nor throw exceptions, as per PSR-4,
 * but these settings can be changed.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ClassLoader
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
     * List of prefixed namespace paths where to look for files.
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
    private $loadFromIncludePath;

    /**
     * Whether to act completely silently as autoloader or not.
     * @var boolean
     */
    protected $silent;

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
        $this->basePaths = [];
        $this->subPaths = [];
        $this->prefixPaths = [];
        $this->fileExtensions = ['.php'];
        $this->loadFromIncludePath = false;
        $this->silent = true;
        $this->loader = [$this, 'load'];
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
     * When enabled, the directory paths in include_path are treated as base
     * paths where to look for classes. This defaults to false.
     *
     * @param boolean $enabled True to use include_path, false to not use
     * @return BasePathLoader Returns self for call chaining
     */
    public function setLoadFromIncludePath($enabled)
    {
        $this->loadFromIncludePath = (bool) $enabled;
        return $this;
    }

    /**
     * Sets whether to throw expections and return values from class loading.
     *
     * PSR-4 states that class autloader must not throw exceptions and should
     * not return any values. This value defaults to true and when disabled,
     * the load method will return a value and throw exceptions on errors.
     *
     * @param boolean $enabled True to throw an exception, false to not
     * @return BasePathLoader Returns self for call chaining
     */
    public function setSilent($enabled)
    {
        $this->silent = (bool) $enabled;
        return $this;
    }

    /**
     * Sets list of dot included file extensions to use for inclusion.
     *
     * Defaults to ['.php']
     *
     * @param array $extensions Array of dot included file extensions to use
     * @return BasePathLoader Returns self for call chaining
     */
    public function setFileExtensions(array $extensions)
    {
        $this->fileExtensions = $extensions;
        return $this;
    }

    /**
     * Adds a path where to look for all classes.
     * @param string|array $path Single path or array of paths
     * @return BasePathLoader Returns self for call chaining
     */
    public function addBasePath($path)
    {
        $this->basePaths = array_merge($this->canonizePaths($path), $this->basePaths);
        return $this;
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
     * @return BasePathLoader Returns self for call chaining
     */
    public function addNamespacePath($namespace, $path = '.')
    {
        $paths = is_array($namespace) ? $namespace : [$namespace => $path];

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

        return $this;
    }

    /**
     * Adds PSR-4 prefixed paths for namespaces.
     *
     * A prefixed path for namespace is a path that replaces part of the
     * namespace with path when resolving class names to file locations. For
     * example, if your class Vendor\Foo\Bar is in file /usr/lib/Foo/Bar.php,
     * you could add a prefix path using:
     *
     * <pre>$loader->addPrefixPath('Vendor\Foo', '/usr/lib/Foo')</pre>
     *
     * @param string|array $prefix Namespace prefix or associative array
     * @param string|array $path Single or multiple paths to add
     * @return BasePathLoader Returns self for call chaining
     */
    public function addPrefixPath($prefix, $path = '.')
    {
        $prefixes = is_array($prefix) ? $prefix : [$prefix => $path];

        foreach ($prefixes as $pre => $paths) {
            $pre = trim($pre, '\\') . '\\';
            $paths = $this->canonizePaths($paths);

            if (isset($this->prefixPaths[$pre])) {
                $this->prefixPaths[$pre] = array_merge($this->prefixPaths[$pre], $paths);
            } else {
                $this->prefixPaths[$pre] = $paths;
            }
        }

        return $this;
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
            $paths[$key] = rtrim($path, $this->directorySeparator) .
                $this->directorySeparator;
        }

        return $paths;
    }

    /**
     * Attemps to load the class from any known path.
     *
     * The load method first tries to load the class using PSR-4 rules for
     * resolving file names using the added prefix paths. If not found, then
     * the class name is treated according to PSR-0, using underscores in the
     * file name as namespace and directory separators. The class loader then
     * attemps to load the class using namespace paths, base paths and include
     * path in that order.
     *
     * If silent mode is disabled, the method will return true if the class was
     * loaded and false if not. Additionally, if the class name is invalid, or
     * the class loader included a file without finding the class, the class
     * loader will throw an exception.
     *
     * @param string $class Full name of the class
     * @return boolean|null True if the class was loaded, false if not, or null on silent mode
     * @throws \RuntimeException if a file was included but no class was found
     * @throws \InvalidArgumentException If the class name is invalid or already exists
     */
    public function load($class)
    {
        if ($this->isValidClass($class)) {
            $loaded = $this->loadClass($class);

            if (!$this->silent) {
                return $loaded;
            }
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
        if (empty($class) || !is_string($class)) {
            if ($this->silent) {
                return false;
            } else {
                throw new \InvalidArgumentException("Invalid class name");
            }
        } elseif ($this->exists($class)) {
            if ($this->silent) {
                return false;
            } else {
                throw new \InvalidArgumentException("Attempting to load " .
                    "class '$class' that already exists");
            }
        }

        return true;
    }

    /**
     * Attempts to load the class using different strategies.
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false if not
     */
    private function loadClass($class)
    {
        $parts = $this->getParts($class);
        $file = implode($this->directorySeparator, $parts);

        if (!empty($this->prefixPaths) &&
            $this->loadFromPrefixPaths($this->prefixPaths, $class)) {
            return true;
        } elseif (isset($this->subPaths[$parts[0]]) &&
            $this->loadFromSubPaths($parts, $this->subPaths[$parts[0]], $class, $file)) {
            return true;
        } elseif (!empty($this->basePaths) &&
            $this->loadFromPaths($this->basePaths, $class, $file)) {
            return true;
        } elseif ($this->loadFromIncludePath) {
            $paths = explode($this->includePathSeparator, get_include_path());

            if ($this->loadFromPaths($this->canonizePaths($paths), $class, $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempts to load the class for sub paths for one namespace.
     * @param array $parts The class name separated into namespace parts
     * @param array $base The base paths define for the namespace
     * @param string $class Full name of the class
     * @param string $file File path generated from class name
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

        return $this->loadFromPaths(array_reverse($paths), $class, $file);
    }

    /**
     * Attemps to load the class from any of the prefixed paths.
     * @param array $paths List of paths
     * @param string $class Full name of the class
     * @return boolean true if the class was loaded, false if not
     */
    private function loadFromPrefixPaths(array $paths, $class)
    {
        $canon = ltrim($class, '\\');

        foreach ($paths as $prefix => $paths) {
            if (strpos($canon, $prefix) === 0) {
                $file = str_replace('\\', $this->directorySeparator,
                    substr($canon, strlen($prefix)));
                if ($this->loadFromPaths($paths, $class, $file)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Attempts to load the class from any given path.
     * @param array $paths Paths where to look for the class file
     * @param string $class Full name of the class
     * @param string $file File path generated from class name
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
     * @param string $class Full name of the class
     * @param string $file File where the class could exist
     * @return boolean True if the class was loaded, false if not
     * @throws \RuntimeException If class was not in the loaded file
     */
    protected function loadFromFile($class, $file)
    {
        $loaded = false;

        if (file_exists($file)) {
            include $file;
            $loaded = $this->exists($class);

            if (!$loaded && !$this->silent) {
                throw new \RuntimeException("Loaded file '$file' but it did " .
                    "not contain the class '$class'");
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
