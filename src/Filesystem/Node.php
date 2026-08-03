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


abstract class Node
{
    protected static string $ds = DIRECTORY_SEPARATOR;

    protected string $pathname;
    protected int    $rootLength;

    private string $name;

    protected function __construct(string $pathname, int $rootLength)
    {
        $this->pathname   = $pathname;
        $this->rootLength = $rootLength;
    }

    /**
     * @return string Absolute pathname to node
     */
    public function __toString(): string
    {
        return $this->pathname;
    }

    /**
     * @return string Dot path `.` for root directory or relative normalized pathname
     */
    public function name(): string
    {
        return $this->name ??= str_replace('\\', '/', substr($this->pathname, $this->rootLength + 1));
    }

    /**
     * @return bool Whether node exist in the filesystem - instance itself doesnt mean it does
     */
    abstract public function exists(): bool;

    /** @throws Exception\FilesystemException */
    abstract public function remove(): void;
}
