# PSR-0 and PSR-4 class autoloading #

Classloader is a PHP library for autoloading classes. Class autoloading means
that classes are loaded only when they are actually needed instead of having to
include each class file on every execution. This reduces the page loading
overhead especially on larger websites, as only some of the class files need to
be loaded. Usually the classes are also loaded dynamically from files with file
names based on the namespace and class name. This also makes it easier to
manage a large number of class files.

This library supports two of the current standards for autoloading classes,
namely the [PSR-0](http://www.php-fig.org/psr/psr-0/) and [PSR-4](http://www.php-fig.org/psr/psr-4/).
The basic idea behind these standards is that class files reside in directories
based on their namespace and in files named after the class. The key difference
between these two standards is that PSR-4 does not require the entire namespace
to be present in the directory hierarchy.

However, since the operation of finding the actual class files tends to be
relatively costly, this library also provides basic caching mechanisms that
allow caching the class file locations in a PHP file. With caching, the 
performance difference between autoloading and loading the files manually
becomes negligible.

The API documentation, which can be generated using Apigen, can be read online
at: http://kit.riimu.net/api/classloader/

[![Build Status](https://img.shields.io/travis/Riimu/Kit-ClassLoader.svg?style=flat)](https://travis-ci.org/Riimu/Kit-ClassLoader)
[![Coverage Status](https://img.shields.io/coveralls/Riimu/Kit-ClassLoader.svg?style=flat)](https://coveralls.io/r/Riimu/Kit-ClassLoader?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Riimu/Kit-ClassLoader.svg?style=flat)](https://scrutinizer-ci.com/g/Riimu/Kit-ClassLoader/?branch=master)

## Requirements ##

In order to use this library, the following requirements must be met:

  * PHP version 5.4

## Installation ##

This library can be installed via [Composer](http://getcomposer.org/). To do
this, download `composer.phar` and require this library as dependency. For
example:

```
$ php -r "readfile('https://getcomposer.org/installer');" | php
$ php composer.phar require riimu/kit-classloader:4.*
```

Alternatively, you add the dependency to your `composer.json` and run `composer
install`. For example:

```json
{
    "require": {
        "riimu/kit-classloader": "4.*"
    }
}
```

If you installed the library via Composer. You can load the library by including
the `vendor/autoload.php` file. If you do not want to use Composer, you can
download the latest release and include the `src/autoload.php` file instead.

## Usage ##

The `ClassLoader` supports autoloading as defined by the PSR-0 and PSR-4
standards via the methods `addBasePath()` and `addPrefixPath()` respectively.
You do not need to understand these standards to use this library, simply use
the method that works best for you.

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

require 'vendor/autoload.php';
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

require 'vendor/autoload.php';
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

Unlike PSR-0, the PSR-4 class autoloading standard does not require classes to
be placed in a directory tree that reflects their entire namespace. Instead,
part of the namespace can be replaced by a specific path.

For example, if your class 'Foo\Bar\Baz' was located in the file
'/path/to/Library/Baz.php', you could register the path using `addPrefixPath()`
and load the class as demonstrated in the following example:

```php
<?php

require 'vendor/autoload.php';
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

While you could simply call the path adding methods multiple times to add
multiple paths, it's possible to add multiple paths using an array. This usually
makes configuration much easier. You can either add multiple base paths using a
list like:

```php
<?php

require 'vendor/autoload.php';
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath([
    '/path/to/classes/',
    '/other/path/',
]);
$loader->register();
```

Or you can add namespace specific paths by providing an associative array that
defines the namespaces using the array keys:

```php
<?php

require 'vendor/autoload.php';
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addPrefixPath([
    'Foo\Bar' => '/path/to/classes/',
    'Other\Namesapace' => ['/other/path/', '/some/other'],
]);
$loader->register();
```

As shown in the example above, you can also provide an array of paths for
specific namespace.

### Caching ###

Looking for classes in the filesystem on each request is a costly affair. It is
highly recommended to cache the file locations so that they do not need to be
searched on every request. After all, the class file locations do not tend to
move around in the file system.

This library provides a very simple caching system via `FileCacheClassLoader`.
The class stores the file locations in a single PHP file which is loaded on
every request instead of searching for the files manually.

The usage of the cached class loader does not differ much from using
`ClassLoader` loader. You simply need to provide the path to a cache file that
will be used to store the class locations in the constructor. For example:

```php
<?php

require 'vendor/autoload.php';
$loader = new Riimu\Kit\ClassLoader\FileCacheClassLoader(__DIR__ . '/cache.php');
$loader->addBasePath('/path/to/classes/');
$loader->register();
```

## Credits ##

This library is copyright 2013 - 2015 to Riikka Kalliomäki.

See LICENSE for license and copying information.
