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


abstract class Directory extends Node
{
    public function name(): string
    {
        return $this->isRoot() ? '.' : parent::name();
    }

    /** @throws Exception\FilesystemException */
    abstract public function create(): void;

    abstract public function file(string $name): File;

    abstract public function subdirectory(string $name): Directory;

    /**
     * @param callable(File): bool|null $filter fn(string) => bool
     *
     * @return Generator<File>
     */
    abstract public function files(bool $isRecursive = false, ?callable $filter = null): Generator;

    /**
     * @param callable(Directory): bool|null $filter fn(string) => bool
     *
     * @return Generator<Directory>
     */
    abstract public function subdirectories(bool $isRecursive = false, ?callable $filter = null): Generator;

    protected function isRoot(): bool
    {
        return strlen($this->pathname) === $this->rootLength;
    }

    protected function pathname(string $name): string
    {
        $relative = trim(str_replace(['/', '\\'], self::$ds, $name), self::$ds);
        if (!$this->isValid($relative)) {
            throw new Exception\FilesystemException(sprintf('Cannot create node with name `%s`', $name));
        }
        return $this->pathname . self::$ds . $relative;
    }

    private function isValid(string $name): bool
    {
        if (empty($name)) { return false; }
        $segments = explode(self::$ds, $name);
        foreach ($segments as $segment) {
            $isDotOnly = trim($segment, '.') === '';
            $isTrimmed = trim($segment) === $segment;
            if ($isDotOnly || !$isTrimmed) { return false; }
        }
        return true;
    }
}
