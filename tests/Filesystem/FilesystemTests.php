<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\File;
use Shudd3r\Toolshed\Filesystem\Node;
use Shudd3r\Toolshed\Filesystem\Exception;
use Generator;


abstract class FilesystemTests extends TestCase
{
    public function testNodeInstantiations()
    {
        $root = $this->root();
        $this->assertInstanceOf(Directory::class, $subdirectory = $root->subdirectory('foo/bar'));
        $this->assertEquals($subdirectory, $root->subdirectory('foo')->subdirectory('bar'));

        $this->assertInstanceOf(File::class, $file = $root->file('foo/bar/baz.txt'));
        $this->assertEquals($file, $subdirectory->file('baz.txt'));
    }

    abstract public function testStringCasting_ReturnsAbsolutePath();

    public function testName_ReturnsPathnameRelativeToRootDirectory()
    {
        $root = $this->root();
        $this->assertSame('.', $root->name());
        $this->assertSame('foo', $root->subdirectory('foo')->name());
        $this->assertSame('foo/bar/baz.txt', $root->file('foo/bar/baz.txt')->name());
        $this->assertSame('foo/bar/baz.txt', $root->subdirectory('foo/bar')->file('baz.txt')->name());
    }

    public function testNodeName_IsNormalized()
    {
        $root = $this->root();
        $this->assertEquals($root->subdirectory('some/node/path'), $node = $root->subdirectory('\\some/node\\path/'));
        $this->assertSame('some/node/path', $node->name());
    }

    /** @dataProvider invalidNames */
    public function testInstantiatingNodeWithInvalidName_ThrowsException(string $invalidName)
    {
        $this->expectException(Exception\FilesystemException::class);
        $this->root()->subdirectory($invalidName);
    }

    public function testCreatingNodes()
    {
        $root = $this->root();
        $this->assertTrue($root->exists());
        $this->assertFalse($root->file('foo/bar/baz.txt')->exists());
        $this->assertSame('', $root->file('foo/bar/baz.txt')->contents());
        $this->assertFalse($root->subdirectory('foo')->exists());

        $root->file('foo/bar/baz.txt')->write('contents');

        $this->assertTrue($root->file('foo/bar/baz.txt')->exists());
        $this->assertSame('contents', $root->file('foo/bar/baz.txt')->contents());
        $this->assertTrue($root->subdirectory('foo')->exists());
        $this->assertTrue($root->subdirectory('foo/bar')->exists());
        $this->assertFalse($root->file('foo/bar')->exists());
        $this->assertFalse($root->subdirectory('foo/bar/baz.txt')->exists());
        $this->assertFalse($root->subdirectory('another')->exists());

        $root->subdirectory('another/path/foo')->create();

        $this->assertTrue($root->subdirectory('another/path/foo')->exists());
        $this->assertTrue($root->subdirectory('another')->exists());
        $this->assertFalse($root->file('another/path/foo/bar.txt')->exists());

        $root->file('another/path/foo/bar.txt')->write('contents');
        $this->assertTrue($root->subdirectory('another/path')->file('foo/bar.txt')->exists());
    }

    public function testFileContents_CanBeOverwritten()
    {
        $root = $this->root();
        $root->file('foo/bar/baz.txt')->write('--- contents ---');

        $this->assertSame('--- contents ---', $root->file('foo/bar/baz.txt')->contents());
        $this->assertSame('', $root->file('not/file.txt')->contents());
        $this->assertSame('', $root->file('foo/bar')->contents());

        $root->file('foo/bar/baz.txt')->write('--- new contents ---');
        $this->assertSame('--- new contents ---', $root->subdirectory('foo/bar')->file('baz.txt')->contents());
    }

    public function testCreatingDirectory_OnFilePath_ThrowsException()
    {
        $root = $this->root();
        $root->file('foo/bar')->write('contents');
        $subdirectory = $root->subdirectory('foo/bar');
        $this->expectException(Exception\FilesystemException::class);
        $subdirectory->create();
    }

    public function testCreatingFile_OnDirectoryPath_ThrowsException()
    {
        $root = $this->root();
        $root->subdirectory('foo/bar')->create();
        $file = $root->file('foo/bar');
        $this->expectException(Exception\FilesystemException::class);
        $file->write('contents');
    }

    public function testCreatingNode_OnExpandedFilePath_ThrowsException()
    {
        $root = $this->root();
        $root->file('foo/bar.txt')->write('contents');
        $node = $root->subdirectory('foo/bar.txt/path/expand');
        $this->expectException(Exception\FilesystemException::class);
        $node->create();
    }

    public function testRemovingNodes()
    {
        $root = $this->root();
        $root->file('foo/bar/baz.txt')->write('contents');
        $root->file('foo/bar.file')->write('contents');
        $root->file('foo/empty/baz.txt')->write('contents');
        $root->subdirectory('bar/baz')->create();

        $file = $root->file('foo/empty/baz.txt');
        $this->assertTrue($file->exists());
        $file->remove();
        $this->assertFalse($file->exists());

        $subdirectory = $root->subdirectory('foo');
        $subdirectory->remove();
        $this->assertFalse($subdirectory->exists());
        $this->assertFalse($root->subdirectory('foo/bar')->exists());
        $this->assertTrue($root->subdirectory('bar/baz')->exists());
    }

    public function testRemovingRootDirectory_ThrowsException()
    {
        $this->expectException(Exception\FilesystemException::class);
        $this->root()->remove();
    }

    public function testNodeIteration()
    {
        $root = $this->root();
        $root->file('foo/bar/baz1.txt')->write('contents');
        $root->file('bar/baz2.txt')->write('contents');
        $root->subdirectory('foo/bar/baz')->create();
        $root->file('root-file')->write('contents');

        $specificContents = fn (File $file) => $file->contents() !== 'contents';
        $this->assertNodes($root->files(false, $specificContents), []);

        $this->assertNodes($root->files(), [
            $root->file('root-file')
        ]);

        $this->assertNodes($root->files(true), [
            $root->file('bar/baz2.txt'),
            $root->file('foo/bar/baz1.txt'),
            $root->file('root-file')
        ]);

        $txtOnly = fn (File $file): bool => str_ends_with((string) $file, '.txt');
        $this->assertNodes($root->files(true, $txtOnly), [
            $root->file('bar/baz2.txt'),
            $root->file('foo/bar/baz1.txt')
        ]);

        $this->assertNodes($root->subdirectories(), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo')
        ]);

        $this->assertNodes($root->subdirectories(true), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo/bar/baz'),
            $root->subdirectory('foo/bar'),
            $root->subdirectory('foo')
        ]);

        $barBasename = fn (Directory $directory): bool => basename((string) $directory) === 'bar';
        $this->assertNodes($root->subdirectories(true, $barBasename), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo/bar')
        ]);
    }

    public static function invalidNames(): array
    {
        return [
            'empty'         => [''],
            'empty segment' => ['foo//bar/baz'],
            'dot segment'   => ['./dot/segment'],
            'double dot'    => ['foo/../../bar'],
            'more dots'     => ['foo/..../bar'],
            'untrimmed1'    => ['foo  /bar'],
            'untrimmed2'    => ['foo/bar   ']
        ];
    }

    /** @param array<Node> $nodeList */
    protected function assertNodes(Generator $nodesGenerator, array $nodeList)
    {
        $indexedNodes = [];
        foreach ($nodeList as $node) {
            $indexedNodes[$node->name()] = $node;
        }

        foreach ($nodesGenerator as $name => $node) {
            $this->assertEquals($node, $indexedNodes[$name]);
            unset($indexedNodes[$name]);
        }
        $nodeNames = $indexedNodes ? '[`' . implode('`, `', array_keys($indexedNodes)) . '`]' : '[]';
        $this->assertEmpty($indexedNodes, sprintf('Some of expected nodes were not iterated: %s', $nodeNames));
    }

    abstract protected function root(): Directory;
}
