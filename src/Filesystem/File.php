<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Filesystem;


abstract class File extends Node
{
    /**
     * @return string Empty string if File does not exist or is not readable
     */
    abstract public function contents(): string;

    /**
     * Creates File if it does not exist. Even if saving empty string.
     *
     * @throws Exception\FilesystemException
     */
    abstract public function write(string $contents): void;
}
