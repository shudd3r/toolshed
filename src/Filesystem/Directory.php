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

use Generator;


interface Directory extends Node
{
    /** @throws Exception\FilesystemException */
    public function create(): void;

    public function file(string $name): File;

    public function subdirectory(string $name): Directory;

    /** @param callable|null $filter fn(string) => bool */
    public function files(bool $isRecursive = false, ?callable $filter = null): Generator;

    /** @param callable|null $filter fn(string) => bool */
    public function subdirectories(bool $isRecursive = false, ?callable $filter = null): Generator;
}
