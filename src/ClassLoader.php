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
     * List of paths where to look for all classes.
     * @var array
     */
    private $basePaths;

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
    private $useIncludePath;

    /**
     * Whether to act completely silently as autoloader or not.
     * @var boolean
     */
    protected $verbose;

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
        $this->basePaths = [];
        $this->prefixPaths = [];
        $this->fileExtensions = ['.php'];
        $this->useIncludePath = false;
        $this->verbose = true;
        $this->loader = [$this, 'loadClass'];
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
    public function useIncludePath($enabled = true)
    {
        $this->useIncludePath = (bool) $enabled;
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
    public function addBasePath($path, $namespace = null)
    {
        $this->addPath('basePaths', $path, $namespace);
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
    public function addPrefixPath($path, $namespace = null)
    {
        $this->addPath('prefixPaths', $path, $namespace);
        return $this;
    }

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
                throw new \InvalidArgumentException("Attempting to load " .
                    "'$class' that already exists");
            }
        }

        return $valid;
    }

    /**
     * Attempts to load the class using different strategies.
     * @param string $class Full name of the class
     * @return boolean True if the class was loaded, false if not
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

    private function findPath($paths, $fname) {
        foreach ($paths as $path) {
            foreach ($this->fileExtensions as $ext) {
                if (file_exists($path . $fname . $ext)) {
                    return $path . $fname . $ext;
                }
            }
        }

        return false;
    }

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
     * Tells if the given class is already defined.
     * @param string $name Name of the class
     * @return boolean True if it exists, false if it does not exists
     */
    private function classExists($class) {
        return class_exists($class, false) ||
            interface_exists($class, false) ||
            trait_exists($class, false);
    }
}
