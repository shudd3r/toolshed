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
use Shudd3r\Toolshed\Filesystem\FilesystemException;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class DirectoryTest extends TestCase
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

    public function testCheckingIfNodeExists()
    {
        $directory = $this->directory(false);
        $this->assertFalse($directory->isDir());

        self::$temp->file('root/foo/bar/baz.txt', 'contents');
        $directory = $this->directory();
        $this->assertTrue($directory->isDir());
        $this->assertTrue($directory->isDir('foo'));
        $this->assertTrue($directory->isDir('foo/bar'));
        $this->assertFalse($directory->isFile('foo/bar'));
        $this->assertFalse($directory->isDir('foo/bar/baz.txt'));
        $this->assertTrue($directory->isFile('foo/bar/baz.txt'));
        $this->assertFalse($directory->isDir('some/name'));
    }

    public function testCreatingNodes()
    {
        $directory = $this->directory();
        $this->assertDirectoryDoesNotExist($directory->pathname('foo/bar'));

        $directory->createSubdirectory('foo/bar');
        $this->assertDirectoryExists($directory->pathname('foo'));
        $this->assertDirectoryExists($directory->pathname('foo/bar'));
        $this->assertFileDoesNotExist($directory->pathname('foo/bar/baz.txt'));

        $directory->fileWrite('foo/bar/baz.txt', 'contents');
        $this->assertFileExists($directory->pathname('foo/bar/baz.txt'));
        $directory->fileWrite('new/path/file.txt', 'contents');
        $this->assertFileExists($directory->pathname('new/path/file.txt'));
    }

    public function testCreatingInvalidDirectoryNode_ThrowsException()
    {
        $directory = $this->directory();
        $directory->fileWrite('foo/bar', 'contents');
        $this->expectException(FilesystemException::class);
        $directory->createSubdirectory('foo/bar');
    }

    public function testCreatingInvalidFileNode_ThrowsException()
    {
        $directory = $this->directory();
        $directory->createSubdirectory('foo/bar');
        $this->expectException(FilesystemException::class);
        $directory->fileWrite('foo/bar', 'contents');
    }

    public function testReadingFileContents()
    {
        $directory = $this->directory();
        $directory->fileWrite('foo/bar/baz.txt', 'contents');

        $this->assertSame('contents', $directory->fileContents('foo/bar/baz.txt'));
        $this->assertSame('', $directory->fileContents('not/file.txt'));
        $this->assertSame('', $directory->fileContents('foo/bar'));

        $directory->fileWrite('foo/bar/baz.txt', 'new contents');
        $this->assertSame('new contents', $directory->fileContents('foo/bar/baz.txt'));
    }

    public function testRemovingNodes()
    {
        $directory = $this->directory();
        $directory->fileWrite('foo/bar/baz.txt', 'contents');
        $directory->fileWrite('foo/bar.file', 'contents');
        $directory->createSubdirectory('foo/empty');
        $directory->fileWrite('foo/empty/baz.txt', 'contents');

        $this->assertFileExists($directory->pathname('foo/empty/baz.txt'));
        $directory->remove('foo/empty/baz.txt');
        $this->assertFileDoesNotExist($directory->pathname('foo/empty/baz.txt'));

        $directory->remove();
        $this->assertDirectoryDoesNotExist($directory->pathname());
    }

    private function directory(bool $rootExists = true): Directory
    {
        return new Directory($rootExists ? self::$temp->directory('root') : self::$temp->pathname('root'));
    }
}
