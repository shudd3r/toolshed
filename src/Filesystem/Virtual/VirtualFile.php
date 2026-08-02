<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Filesystem\Virtual;

use Shudd3r\Toolshed\Filesystem\Exception;
use Shudd3r\Toolshed\Filesystem\File;


class VirtualFile extends File
{
    use CreatePathMethod;

    private string $contents = '';

    public function exists(): bool
    {
        return (VirtualDirectory::$nodes[$this->name()] ?? null) === $this;
    }

    public function contents(): string
    {
        return $this->contents;
    }

    public function write(string $contents): void
    {
        $this->contents = $contents;
        if ($this->exists()) { return; }

        $name = $this->name();
        if (array_key_exists($name, VirtualDirectory::$nodes)) {
            throw new Exception\FilesystemException('Directory already exists');
        }

        $this->createPathFor($name);
        VirtualDirectory::$nodes[$name] = $this;
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        unset(VirtualDirectory::$nodes[$this->name()]);
        $this->contents = '';
    }
}
