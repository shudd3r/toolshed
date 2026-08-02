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
use Shudd3r\Toolshed\Filesystem\Local\LocalDirectory;
use Shudd3r\Toolshed\Filesystem\Local\LocalFile;
use Shudd3r\Toolshed\Filesystem\Exception;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class LocalFilesystemTest extends TestCase
{
    private static TempFiles $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new TempFiles(basename(static::class));
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
    }

    public function testNotExistingRootDirectory_ThrowsException()
    {
        $this->expectException(Exception\FilesystemException::class);
        LocalDirectory::root(self::$temp->pathname('notExists'));
    }

    public function testNodeInstantiations()
    {
        $root = $this->root();
        $this->assertInstanceOf(LocalDirectory::class, $subdirectory = $root->subdirectory('foo/bar'));
        $this->assertEquals($subdirectory, $root->subdirectory('foo')->subdirectory('bar'));

        $this->assertInstanceOf(LocalFile::class, $file = $root->file('foo/bar/baz.txt'));
        $this->assertEquals($file, $subdirectory->file('baz.txt'));
    }

    public function testStringCasting_ReturnsAbsolutePath()
    {
        $root = $this->root();
        $this->assertSame(self::$temp->pathname(''), (string) $root);
        $this->assertSame(self::$temp->pathname('foo/bar'), (string) $root->subdirectory('foo/bar'));
        $this->assertSame(self::$temp->pathname('foo/bar/baz.txt'), (string) $root->file('foo/bar/baz.txt'));
    }

    public function testName_ReturnsPathnameRelativeToRootDirectory()
    {
        $root = $this->root();
        $this->assertSame('.', $root->name());
        $this->assertSame('foo', $root->subdirectory('foo')->name());
        $this->assertSame('foo/bar/baz.txt', $root->file('foo/bar/baz.txt')->name());
        $this->assertSame('foo/bar/baz.txt', $root->subdirectory('foo/bar')->file('baz.txt')->name());
    }

    public function testCheckingIfDirectoryNodeExists()
    {
        $root = $this->root();
        $this->assertTrue($root->exists());
        $this->assertFalse($root->subdirectory('foo')->exists());

        self::$temp->file('foo/bar/baz.txt', 'contents');

        $this->assertTrue($root->subdirectory('foo')->exists());
        $this->assertTrue($root->subdirectory('foo/bar')->exists());
        $this->assertFalse($root->file('foo/bar')->exists());
        $this->assertFalse($root->subdirectory('foo/bar/baz.txt')->exists());
        $this->assertTrue($root->file('foo/bar/baz.txt')->exists());
        $this->assertFalse($root->subdirectory('some/name')->exists());
    }

    public function testInstantiatingNodes()
    {
        $root = $this->root();

        $subdirectory = $root->subdirectory('foo/bar');
        $this->assertDirectoryDoesNotExist((string) $subdirectory);

        $subdirectory->create();
        $this->assertDirectoryExists((string) $root->subdirectory('foo\\'));
        $this->assertDirectoryExists((string) $root->subdirectory('foo/bar'));
        $this->assertFileDoesNotExist((string) $root->file('foo/bar/baz.txt'));

        $root->file('foo/bar/baz.txt')->write('contents');
        $this->assertFileExists((string) $root->file('\\foo/bar/baz.txt'));
        $root->file('new/path/file.txt')->write('contents');
        $this->assertFileExists((string) $root->file('new/path/file.txt/'));
    }

    /** @dataProvider invalidNames */
    public function testInstantiatingNodeWithInvalidName_ThrowsException(string $invalidName)
    {
        $this->expectException(Exception\FilesystemException::class);
        $this->root()->subdirectory($invalidName);
    }

    public function testCreatingInvalidDirectoryNode_ThrowsException()
    {
        $root = $this->root();
        $root->file('foo/bar')->write('contents');
        $subdirectory = $root->subdirectory('foo/bar');
        $this->expectException(Exception\FilesystemException::class);
        $subdirectory->create();
    }

    public function testCreatingInvalidFileNode_ThrowsException()
    {
        $root = $this->root();
        $root->subdirectory('foo/bar')->create();
        $file = $root->file('foo/bar');
        $this->expectException(Exception\FilesystemException::class);
        $file->write('contents');
    }

    public function testReadingFileContents()
    {
        $root = $this->root();
        $root->file('foo/bar/baz.txt')->write('contents');

        $this->assertSame('contents', $root->file('foo/bar/baz.txt')->contents());
        $this->assertSame('', $root->file('not/file.txt')->contents());
        $this->assertSame('', $root->file('foo/bar')->contents());

        $root->file('foo/bar/baz.txt')->write('new contents');
        $this->assertSame('new contents', $root->subdirectory('foo/bar')->file('baz.txt')->contents());
    }

    public function testRemovingNodes()
    {
        $root = $this->root();
        $root->file('foo/bar/baz.txt')->write('contents');
        $root->file('foo/bar.file')->write('contents');
        $root->file('foo/empty/baz.txt')->write('contents');

        self::$temp->symlink('foo/empty/baz.txt', 'foo/no.target.symlink');
        $file = $root->file('foo/empty/baz.txt');
        $this->assertFileExists((string) $file);
        $file->remove();
        $this->assertFileDoesNotExist((string) $file);

        self::$temp->symlink('foo/bar', 'foo/aaa.valid.symlink');
        self::$temp->symlink('foo/bar', 'foo/zzz.stale.symlink');
        $subdirectory = $root->subdirectory('foo');
        $subdirectory->remove();
        $this->assertDirectoryDoesNotExist((string) $subdirectory);
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

        $this->assertNodes(fn () => $root->files(), [
            $root->file('root-file')
        ]);

        $this->assertNodes(fn () => $root->files(true), [
            $root->file('bar/baz2.txt'),
            $root->file('foo/bar/baz1.txt'),
            $root->file('root-file')
        ]);

        $txtOnly = fn (LocalFile $file): bool => str_ends_with((string) $file, '.txt');
        $this->assertNodes(fn () => $root->files(true, $txtOnly), [
            $root->file('bar/baz2.txt'),
            $root->file('foo/bar/baz1.txt')
        ]);

        $this->assertNodes(fn () => $root->subdirectories(), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo')
        ]);

        $this->assertNodes(fn () => $root->subdirectories(true), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo/bar/baz'),
            $root->subdirectory('foo/bar'),
            $root->subdirectory('foo')
        ]);

        $barBasename = fn (LocalDirectory $directory): bool => basename((string) $directory) === 'bar';
        $this->assertNodes(fn () => $root->subdirectories(true, $barBasename), [
            $root->subdirectory('bar'),
            $root->subdirectory('foo/bar')
        ]);
    }

    public static function invalidNames(): array
    {
        return [
            'empty'       => [''],
            'dot segment' => ['./dot/segment'],
            'double dot'  => ['foo/../../bar'],
            'more dots'   => ['foo/..../bar'],
            'untrimmed1'  => ['foo  /bar'],
            'untrimmed2'  => ['foo/bar   ']
        ];
    }

    private function assertNodes(callable $nodesGenerator, array $nodeList)
    {
        foreach ($nodesGenerator() as $node) {
            $this->assertContainsEquals($node, $nodeList);
        }
    }

    private function root(): LocalDirectory
    {
        return LocalDirectory::root(self::$temp->directory());
    }
}
