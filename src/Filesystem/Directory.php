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

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Traversable;


class Directory extends Node
{
    public static function root(string $rootPath): self
    {
        $rootPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
        if (!is_dir($rootPath)) {
            throw new FilesystemException(sprintf('Root path does not exist `%s`', $rootPath));
        }
        return new self($rootPath, true);
    }

    private bool $isRoot;

    public function __construct(string $rootPath, bool $isRoot = false)
    {
        $this->isRoot = $isRoot;
        parent::__construct($rootPath);
    }

    public function exists(): bool
    {
        return is_dir($this->pathname);
    }

    /** @throws FilesystemException */
    public function create(): void
    {
        if ($this->exists()) { return; }
        if (is_file($this->pathname)) {
            $message = 'Cannot create directory `%s`';
            throw new FilesystemException(sprintf($message, $this->pathname));
        }
        is_dir($this->pathname) || mkdir($this->pathname, 0700, true);
    }

    public function subdirectory(string $name): Directory
    {
        return new self($this->pathname($name));
    }

    public function file(string $name): File
    {
        return new File($this->pathname($name));
    }

    public function remove(): void
    {
        if (!$this->exists()) { return; }
        if ($this->isRoot) {
            throw new FilesystemException('Cannot remove root directory');
        }

        foreach ($this->nodes() as $nodePath) {
            $this->removeLeafNode($nodePath);
        }

        rmdir($this->pathname);
    }

    protected function nodes(): Traversable
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($this->pathname, $flags);
        return new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
    }
}
