# Rose Class Loader #

Rose class loader provides an easy to use class autoloader for classes that are
stored in folder structure according to their namespace.

For details, see the documentation in the source files.

## Usage ##

Easiest way to use the class loader is to simply create it, add the base
directory for all your classes and register the loader.

```php
<?php
$loader = new Rose\ClassLoader\ClassPathLoader();
$loader->addLibraryPath('/path/to/vendors/');
$loader->register();
```

If the path is already in your include path, you don't even need to call the
`addLibraryPath()` method.

Alternatively, you can specify a specific path to some vendor using the
`addVendorPath()` method.

```php
<?php
$loader = new Rose\ClassLoader\ClassPathLoader();
$loader->addVendorPath('Vendor', __DIR__ . '/class/vendor/');
$loader->register();
```

For working examples, see the files in the examples directory.

## Credits ##

This library is copyright 2012 to Riikka Kalliomäki