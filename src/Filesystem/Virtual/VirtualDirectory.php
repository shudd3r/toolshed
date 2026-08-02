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

use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\File;
use Shudd3r\Toolshed\Filesystem\Node;
use Shudd3r\Toolshed\Filesystem\Exception;
use Generator;


class VirtualDirectory extends Directory
{
    use CreatePathMethod;

    public static array $nodes = [];

    public static function root(string $rootPath = '/', string $ds = DIRECTORY_SEPARATOR): self
    {
        self::$ds    = $ds;
        self::$nodes = [];
        return new self($rootPath, strlen($rootPath));
    }

    public function exists(): bool
    {
        return $this->isRoot() || (self::$nodes[$this->name()] ?? null) === $this;
    }

    public function create(): void
    {
        $name   = $this->name();
        $exists = $this->exists();
        if ($exists) { return; }
        if (array_key_exists($name, self::$nodes)) {
            throw new Exception\FilesystemException('File already exists');
        }

        $this->createPathFor($name);
        self::$nodes[$name] = $this;
    }

    public function file(string $name): VirtualFile
    {
        $file = new VirtualFile($this->pathname($name), $this->rootLength);
        $node = self::$nodes[$file->name()] ?? $file;
        return $node instanceof VirtualFile ? $node : $file;
    }

    public function subdirectory(string $name): VirtualDirectory
    {
        $file = new VirtualDirectory($this->pathname($name), $this->rootLength);
        $node = self::$nodes[$file->name()] ?? $file;
        return $node instanceof VirtualDirectory ? $node : $file;
    }

    /** @return Generator<File> */
    public function files(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $filter ??= static fn (VirtualFile $node) => true;
        $typeFilter = static fn (Node $node): bool => ($node instanceof VirtualFile) && $filter($node);
        return $this->nodes(!$isRecursive, $typeFilter);
    }

    /** @return Generator<Directory> */
    public function subdirectories(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $filter ??= static fn (Directory $node) => true;
        $typeFilter = static fn (Node $node): bool => ($node instanceof VirtualDirectory) && $filter($node);
        return $this->nodes(!$isRecursive, $typeFilter);
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        if ($this->isRoot()) {
            throw new Exception\FilesystemException('Cannot remove root directory');
        }
        foreach ($this->nodes() as $name => $node) {
            unset(self::$nodes[$name]);
        }

        unset(self::$nodes[$this->name()]);
    }

    private function nodes(bool $direct = false, ?callable $filter = null): Generator
    {
        foreach (self::$nodes as $name => $node) {
            if (!$this->containsNode($name, $direct)) { continue; }
            if ($filter && $filter($node) === false) { continue; }
            yield $name => $node;
        }
    }

    private function containsNode(string $name, bool $directly): bool
    {
        $isRoot   = $this->isRoot();
        $contains = $isRoot || strncmp($name, $this->name(), strlen($this->name())) === 0;
        if (!$directly || !$contains) { return $contains; }
        return strpos($name, '/', $isRoot ? 0 : strlen($this->name()) + 1) === false;
    }
}
