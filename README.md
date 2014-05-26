# PSR-0 and PSR-4 class autoloader #

This library provides a class autoloader with support for both PSR-0 and
PSR-4 class autoloading. It is possible to provide autoloading paths as base
paths which are appended with entire class namespace structure or as prefix
paths which replace part of the namespace with a specific directory path.

The library also provides additional classes for caching class file locations
to reduce the class autoload overhead.

API documentation for the classes can be generated using apigen.

## Usage ##

Basically, PSR-0 autoloading means that the entire class namespace structure
is reflected in the directory tree. For example, the file class 'Foo\Bar\Baz'
is located in '/path/to/classes/Foo/Bar/Baz.php'. Easiest way to autoload your
classes is to simply put them in directories according to their namespaces and
add the base path to the class loader. For example:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath('/path/to/classes/');
$loader->register();
```

PSR-4 autoloading, however, does not require that file paths necessarily reflect
the entire class namespace stucture. It's possible to replace part of the
namespace with a specific directory. For example, if your 'Foo\Bar\Baz.php' is
located in '/path/to/Library/Baz.php', you could do the following:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath('/path/to/Library/', 'Foo\Bar');
$loader->register();
```

For working examples, see the files in the examples directory.

## Caching ##

Looking for classes in the filesystem on each request is a costly affair. It is
highly recommended to cache the file locations so that they do not need to be
searched on every request. After all, the class file locations do not tend to
move around in the file system.

This library provides a very simple caching system via the class
`FileCacheClassLoader`. The class stores the file locations in a single PHP file
which is loaded on every request instead of searching for the files manually.

The usage of the cached class loader does not differ much from the base class
loader. You simply need to provide the path to a cache file that will be used
to store the class locations in the constructor. For example:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\FileCacheClassLoader(__DIR__ . '/cache.php');
$loader->addBasePath('/path/to/classes/');
$loader->register();
```

## Credits ##

This library is copyright 2013 - 2014 to Riikka Kalliomäki
