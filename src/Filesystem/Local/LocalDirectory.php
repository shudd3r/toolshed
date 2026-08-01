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
        $rootPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
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

    /** @throws Exception\FilesystemException */
    public function create(): void
    {
        if ($this->exists()) { return; }
        if (is_file($this->pathname)) {
            $message = 'Cannot create directory `%s`';
            throw new Exception\FilesystemException(sprintf($message, $this->pathname));
        }
        is_dir($this->pathname) || mkdir($this->pathname, 0700, true);
    }

    public function file(string $name): LocalFile
    {
        return new LocalFile($this->pathname($name), $this->rootLength);
    }

    public function subdirectory(string $name): LocalDirectory
    {
        return new self($this->pathname($name), $this->rootLength);
    }

    /** @param callable|null $filter fn(string) => bool */
    public function files(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $filter ??= static fn (string $pathname): bool => true;
        $mainFilter = fn (string $pathname): bool => is_file($pathname) && $filter($pathname);
        $filenames  = new CallbackFilterIterator($this->nodes($isRecursive), $mainFilter);

        foreach ($filenames as $pathname) {
            yield new LocalFile($pathname, $this->rootLength);
        }
    }

    /** @param callable|null $filter fn(string) => bool */
    public function subdirectories(bool $isRecursive = false, ?callable $filter = null): Generator
    {
        $filter ??= static fn (string $pathname): bool => true;
        $mainFilter  = static fn (string $pathname): bool => is_dir($pathname) && $filter($pathname);
        $directories = new CallbackFilterIterator($this->nodes($isRecursive), $mainFilter);

        foreach ($directories as $pathname) {
            yield new LocalDirectory($pathname, $this->rootLength);
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

    private function isRoot(): bool
    {
        return strlen($this->pathname) === $this->rootLength;
    }

    private function pathname(string $name): string
    {
        $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $name), DIRECTORY_SEPARATOR);
        if (!$this->isValid($relative)) {
            throw new Exception\FilesystemException(sprintf('Cannot create node with name `%s`', $name));
        }

        return $this->pathname . DIRECTORY_SEPARATOR . $relative;
    }

    private function isValid(string $name): bool
    {
        if (empty($name)) { return false; }
        $segments = explode(DIRECTORY_SEPARATOR, $name);
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
