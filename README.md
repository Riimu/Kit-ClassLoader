# Class autoloader for Riimu\Kit #

The ClassLoader package provides an autoloader for classes that are PSR-0
compliant. In other words, the autoloader can handle loading classes in
folder structures that correspond to their namespaces.

API documentation is available in the class files. Additionally, documentation
can be generated using apigen.

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

If the path is already in your include_path, you don't even need to call the
`addBasePath()` method.

Alternatively, if some classes for another vendor are loaded from another base
directory, you can add additional base directory for those classes using the
`addNamespacePath()` method.

```php
<?php
$loader = new Riimu\Kit\ClassLoader\BasePathLoader();
$loader->addNamespacePath('Vendor', '/other/class/path/');
$loader->register();
```

For working examples, see the files in the examples directory.

## Credits ##

This library is copyright 2013 to Riikka Kalliomäki