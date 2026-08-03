<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Filesystem\Virtual;

use Shudd3r\Toolshed\Tests\Filesystem\FilesystemTests;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Filesystem\Directory;


class VirtualFilesystemTest extends FilesystemTests
{
    public function testStringCasting_ReturnsAbsolutePath()
    {
        $root = $this->root();
        $this->assertSame('vfs://root', (string) $root);
        $this->assertSame('vfs://root/foo/bar', (string) $root->subdirectory('foo/bar'));
        $this->assertSame('vfs://root/foo/bar/baz.txt', (string) $root->file('foo/bar/baz.txt'));
    }

    protected function root(): Directory
    {
        return VirtualDirectory::root('vfs://root', '/');
    }
}
