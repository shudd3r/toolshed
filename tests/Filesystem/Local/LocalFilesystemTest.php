<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Filesystem\Local;

use Shudd3r\Toolshed\Tests\Filesystem\FilesystemTests;
use Shudd3r\Toolshed\Filesystem\Local\LocalDirectory;
use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\Exception;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class LocalFilesystemTest extends FilesystemTests
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

    public function testStringCasting_ReturnsAbsolutePath()
    {
        $root = $this->root();
        $this->assertSame(self::$temp->pathname(''), (string) $root);
        $this->assertSame(self::$temp->pathname('foo/bar'), (string) $root->subdirectory('foo/bar'));
        $this->assertSame(self::$temp->pathname('foo/bar/baz.txt'), (string) $root->file('foo/bar/baz.txt'));
    }

    public function testCreatingNodes()
    {
        parent::testCreatingNodes();
        self::$temp->clear();

        $root         = $this->root();
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

    public function testRemovingNodes()
    {
        parent::testRemovingNodes();
        self::$temp->clear();

        $root = $this->root();
        $root->file('foo/bar/baz.txt')->write('contents');
        self::$temp->symlink('foo/bar/baz.txt', 'foo/valid.file.symlink');
        self::$temp->symlink('foo/bar', 'foo/valid.dir.symlink');
        self::$temp->symlink('foo/not/exists', 'foo/stale.symlink');

        $root->subdirectory('foo')->remove();
        $this->assertFalse($root->subdirectory('foo')->exists());
    }

    protected function root(): Directory
    {
        return LocalDirectory::root(self::$temp->directory());
    }
}
