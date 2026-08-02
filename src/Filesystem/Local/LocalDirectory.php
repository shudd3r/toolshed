<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Filesystem\Local;

use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use CallbackFilterIterator;
use Generator;
use Iterator;


class LocalDirectory extends LocalNode implements Directory
{
    public static function root(string $rootPath): self
    {
        $rootPath = str_replace(['/', '\\'], self::$ds, $rootPath);
        if (!is_dir($rootPath)) {
            throw new Exception\FilesystemException(sprintf('Root path does not exist `%s`', $rootPath));
        }
        return new self($rootPath, strlen($rootPath));
    }

    public function name(): string
    {
        return $this->isRoot() ? '.' : parent::name();
    }

    public function exists(): bool
    {
        return is_dir($this->pathname);
    }

    public function create(): void
    {
        if (is_file($this->pathname)) {
            $message = 'Cannot create directory `%s`';
            throw new Exception\FilesystemException(sprintf($message, $this->pathname));
        }
        $this->exists() || mkdir($this->pathname, 0700, true);
    }

    public function file(string $name): LocalFile
    {
        return new LocalFile($this->pathname($name), $this->rootLength);
    }

    public function subdirectory(string $name): LocalDirectory
    {
        return new self($this->pathname($name), $this->rootLength);
    }

    public function files(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $typeFilter = fn (string $pathname): bool => is_file($pathname);
        $filenames  = new CallbackFilterIterator($this->nodes($isRecursive), $typeFilter);

        foreach ($filenames as $pathname) {
            $file = new LocalFile($pathname, $this->rootLength);
            if ($filter && !$filter($file)) { continue; }
            yield $file;
        }
    }

    public function subdirectories(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $typeFilter  = static fn (string $pathname): bool => is_dir($pathname);
        $directories = new CallbackFilterIterator($this->nodes($isRecursive), $typeFilter);

        foreach ($directories as $pathname) {
            $directory = new LocalDirectory($pathname, $this->rootLength);
            if ($filter && !$filter($directory)) { continue; }
            yield $directory;
        }
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        if ($this->isRoot()) {
            throw new Exception\FilesystemException('Cannot remove root directory');
        }

        foreach ($this->nodes(true) as $nodePath) {
            $this->removeLeafNode($nodePath);
        }

        rmdir($this->pathname);
    }

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

    private function nodes(bool $isRecursive): Iterator
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        if ($isRecursive) {
            $nodes = new RecursiveDirectoryIterator($this->pathname, $flags);
            return new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
        }
        return new FilesystemIterator($this->pathname, $flags);
    }
}
