# PSR-0 and PSR-4 class autoloader #

This library provides a class autoloader with support for both
[PSR-0](http://www.php-fig.org/psr/psr-0/) and [PSR-4](http://www.php-fig.org/psr/psr-4/)
class autoloading. It is possible to provide base directory paths that
are used to load classes according to PSR-0 or you can provide namespace
specific paths, which are used to load classes according to PSR-4.

Loading classes according to PSR-0 means that the entire namespace structure
must be included in the source directory tree. Additionally, undescores in
the class name are treated as namespace separators. In PSR-4, on the other
hand, it is possible to replace part of the namespace with a specific directory,
which usually means smaller directory trees.

The library also provides additional classes for caching class file locations
to reduce the overhead caused by class autoloading.

API documentation is [available](http://kit.riimu.net/api/classloader/) and it
can be generated using ApiGen.

[![Build Status](https://travis-ci.org/Riimu/Kit-ClassLoader.svg?branch=master)](https://travis-ci.org/Riimu/Kit-ClassLoader)
[![Coverage Status](https://coveralls.io/repos/Riimu/Kit-ClassLoader/badge.png?branch=master)](https://coveralls.io/r/Riimu/Kit-ClassLoader?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Riimu/Kit-ClassLoader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Riimu/Kit-ClassLoader/?branch=master)

## Installation ##

This library can be easily installed using [Composer](http://getcomposer.org/)
by including the following dependency in your `composer.json`:

```json
{
    "require": {
        "riimu/kit-classloader": "4.*"
    }
}
```

The library will be the installed by running `composer install` and the classes
can be loaded with simply including the `vendor/autoload.php` file.

## Usage ##

The autoloader supports both PSR-0 and PSR-4 class autoloading standards via
the methods `ClassLoader::addBasePath()` and `ClassLoader::addPrefixPath()`
respectively. You do not need to understand these standards to use the class
loader, simply use the method that works best for you.

### PSR-0 class autoloading ###

PSR-0 class autoloading defines that class files must be placed in a directory
tree that reflects their namespace. For example, the class 'Foo\Bar\Baz' could
be located in a file '/path/to/classes/Foo/Bar/Baz.php'. This method is usually
the simplest way to place your class files.

Using the `addBasePath()` method, you can define the base directories where to
look for classes. The load the class mentioned above, you could use the
following code:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath('/path/to/classes/');
$loader->register();

$obj = new Foo\Bar\Baz();
```

If a specific directory only applies to a specific namespace, you can use the
second parameter to define the namespace as well. The directory still needs to
point to the base directory for the namespace. For example:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath('/path/to/classes/', 'Foo\Bar');
$loader->register();

$obj = new Foo\Bar\Baz();
```

Note that PSR-0 also states that underscores in the class name are treated as
namespace separators (but not in the namespaces themselves). So, even if your
class was called 'Foo\Bar_Baz', both of the above examples would still work.
Regardless of whether the namespace are defined using underscore or a backslash,
the namespace parameter in the function must use backslashes.

### PSR-4 class autoloading ###

Unlike PSR-0, the PSR-0 class autoloading standard does not require classes to
be placed in a directory trees that reflect their namespace. Instead, part of
the namespace can be replaced by a specific path.

For example, if your class 'Foo\Bar\Baz' was located in the file
'/path/to/Library/Baz.php', you could register the path using `addPrefixPath()`
and load the class as demonstrated in the following example:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath('/path/to/Library/', 'Foo\Bar');
$loader->register();

$obj = new Foo\Bar\Baz();
```

This allows shorter directory trees as the entire namespace does not need to
be reflected in the directory structure. It's also possible to omit the
namespace argument, in which case the path will work just like in PSR-0
autoloading with the exception that the underscores in the class name will not
be treated as namespace separators.

### Adding multiple paths ###

While you could simply call the methods to add paths multiple times, it's
possible to add multiple paths using an array. This usually makes configuration
much easier. You can either add multiple base paths using a list like:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath([
    '/path/to/classes/',
    '/other/path/',
]);
$loader->register();
```

Or you can add namespace specific paths by defining the namespace in the key
like:

```php
<?php
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath([
    'Foo\Bar' => '/path/to/classes/',
    'Other\Namesapace' => ['/other/path/', '/some/other'],
]);
$loader->register();
```

As shown in the exampe above, you can also provide an array of paths for
specific namespace. This also works if you provide a list of paths and the
namespace as the second parameter. The namespaces are treated according to
which loading standard you are using.

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
