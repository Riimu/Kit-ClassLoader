<?php

namespace Riimu\Kit\ClassLoader;

use PHPUnit\Framework\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PathAddingTest extends TestCase
{
    public function testDirectorySeparatorCanonization()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(__DIR__ . '/../tests/', '');
        $this->assertSame(path([__DIR__, '..', 'tests', 'PathAddingTest.php']), $loader->findFile('PathAddingTest'));

        $loader2 = new ClassLoader();
        $loader2->addBasePath(__DIR__ . '\\..\\tests\\', '');
        $this->assertSame(path([__DIR__, '..', 'tests', 'PathAddingTest.php']), $loader2->findFile('PathAddingTest'));
    }

    public function testNameSpaceSeparatorCanonization()
    {
        $loader = new ClassLoader();
        $loader->addBasePath(path(['path', 'to', 'classes']), 'Foo\Bar');
        $loader->addPrefixPath(path(['path', 'to', 'classes']), 'Foo\Bar');
        $this->assertPathsAre([
            'Foo\\Bar\\' => [path(['path', 'to', 'classes'])],
        ], $loader);

        $loader2 = new ClassLoader();
        $loader2->addBasePath(path(['path', 'to', 'classes']), '\\Foo\\Bar\\');
        $loader2->addPrefixPath(path(['path', 'to', 'classes']), '\\Foo\\Bar\\');
        $this->assertPathsAre([
            'Foo\\Bar\\' => [path(['path', 'to', 'classes'])],
        ], $loader2);
    }

    public function testAddingListOfPaths()
    {
        $list = [
            path(['some', 'path'], true),
            path(['other', 'path'], true),
        ];

        $loader = new ClassLoader();
        $loader->addBasePath($list);
        $loader->addPrefixPath($list);
        $this->assertPathsAre([
            '' => $list,
        ], $loader);
    }

    public function testAddingListOfSpecificPaths()
    {
        $list = [
            'Foo\Bar' => path(['some', 'path'], true),
            'Baz'     => path(['other', 'path'], true),
        ];

        $loader = new ClassLoader();
        $loader->addBasePath($list);
        $loader->addPrefixPath($list);
        $this->assertPathsAre([
            'Foo\Bar\\' => [path(['some', 'path'], true)],
            'Baz\\'     => [path(['other', 'path'], true)],
        ], $loader);
    }

    public function testAddingMixedPaths()
    {
        $list = [
            path(['some', 'path'], true),
            'Baz' => path(['other', 'path'], true),
            path(['third', 'path'], true),
        ];

        $loader = new ClassLoader();
        $loader->addBasePath($list);
        $loader->addPrefixPath($list);
        $this->assertPathsAre([
            ''      => [path(['some', 'path'], true), path(['third', 'path'], true)],
            'Baz\\' => [path(['other', 'path'], true)],
        ], $loader);
    }

    public function testAddingMultiplePaths()
    {
        $list = [
            ''      => [path(['some', 'path'], true), path(['third', 'path'], true)],
            'Baz\\' => [path(['other', 'path'], true)],
        ];

        $loader = new ClassLoader();
        $loader->addBasePath($list);
        $loader->addPrefixPath($list);
        $this->assertPathsAre($list, $loader);
    }

    public function testAddingMultiplePathsWithNamespaceArgument()
    {
        $list = [
            path(['some', 'path'], true),
            path(['third', 'path'], true),
        ];

        $loader = new ClassLoader();
        $loader->addBasePath($list, 'Foo\Bar');
        $loader->addPrefixPath($list, 'Foo\Bar');
        $this->assertPathsAre([
            'Foo\Bar\\' => $list,
        ], $loader);
    }

    /**
     * @param array $expected
     * @param ClassLoader $loader
     */
    public function assertPathsAre(array $expected, $loader)
    {
        $this->assertSame($expected, $loader->getBasePaths());
        $this->assertSame($expected, $loader->getPrefixPaths());
    }
}
