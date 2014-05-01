# PSR-0 and PSR-4 class autoloading #

This library provides a way to support both PSR-0 class autoloading by adding
base paths for where to look classes and PSR-4 class autoloading by adding
prefixed namespace paths for classes. The most important functionality of the
class autoloader is to map class names to different files according to given
rules.

API documentation for the classes can be generated using apigen.

## Usage ##

Easiest way to use the class loader is to put all your classes in a single
directory with sub directories according to their namespaces. By doing this,
you can just add the base directory to the class loader and let it handle the
rest.

```php
<?php
$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->addBasePath('/path/to/classes/');
$loader->register();
```

You may also add the directory to your include_path and allow the class loader
to use it via `setLoadFromIncludePath()`. Both of these methods are intended
for loading classes according to the PSR-0 standard.

The PSR-4 standard defines a way to define prefixes for class namespaces to load
classes from directories that do not entirely correspond their namespace
structure. To add prefixes, you can, for example, do following:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->addPrefixPath('Vendor\Mylib', '/path/to/MyLib/');
$loader->register();
```

For working examples, see the files in the examples directory.

## Caching ##

Class locations in projects don't generally move around that much. However,
every time a class is loaded with the autoloader, it has to look for that class
in all possible locations provided to the autoloader. Thus, faster class loading
can simply be achieved by caching the file locations for each class.

The library provides a simple mechanism to cache the file locations using the
provided `FileCachedLoader` class. The constructor for that class takes path to
the cache file as an argument and stores the class locations in that file.

## Credits ##

This library is copyright 2013 - 2014 to Riikka Kalliomäki