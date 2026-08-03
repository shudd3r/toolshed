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


trait CreatePathMethod
{
    private function createPathFor(string $name): void
    {
        $dirname = dirname($name);
        if ($dirname === '.') { return; }

        $node = VirtualDirectory::$nodes[$dirname] ?? null;
        if ($node instanceof VirtualDirectory) { return; }
        if ($node) {
            throw new Exception\FilesystemException('File already exists');
        }

        $root = substr($this->pathname, 0, $this->rootLength);
        $path = $root . self::$ds . str_replace('/', self::$ds, $dirname);

        $node = new VirtualDirectory($path, $this->rootLength);
        $node->create();
    }
}
