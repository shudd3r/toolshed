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


class Directory
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath);
    }

    public function isFile(string $name): bool
    {
        return is_file($this->pathname($name));
    }

    public function isDir(string $name = ''): bool
    {
        return is_dir($this->pathname($name));
    }

    /** @throws FilesystemException */
    public function createSubdirectory(string $name): void
    {
        $pathname = $this->pathname($name);
        if (is_file($pathname)) {
            $message = 'Cannot create `%s` directory. File of this name already exists: `%s`';
            throw new FilesystemException(sprintf($message, $name, $pathname));
        }
        is_dir($pathname) || mkdir($this->pathname($name), 0700, true);
    }

    /** @throws FilesystemException */
    public function fileWrite(string $name, string $contents): void
    {
        $pathname = $this->pathname($name);
        if (is_dir($pathname)) {
            $message = 'Cannot create `%s` file. Directory of this name already exists: `%s`';
            throw new FilesystemException(sprintf($message, $name, $pathname));
        }
        is_dir(dirname($pathname)) || mkdir(dirname($pathname), 0700, true);
        file_put_contents($pathname, $contents);
    }

    public function fileContents(string $name): string
    {
        $pathname = $this->pathname($name);
        return is_file($pathname) ? file_get_contents($pathname) : '';
    }

    public function pathname(string $name = ''): string
    {
        if (empty($name)) { return $this->rootPath; }
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $name);
        return $this->rootPath . DIRECTORY_SEPARATOR . $relative;
    }

    public function remove(string $name = ''): void
    {
        $pathname = $this->pathname($name);
        if (is_file($pathname)) {
            $this->removeLeafNode($pathname);
            return;
        }

        if (!is_dir($pathname)) { return; }
        foreach ($this->directoryNodes($pathname) as $nodePath) {
            $this->removeLeafNode($nodePath);
        }
        rmdir($pathname);
    }

    private function directoryNodes(string $pathname): Traversable
    {
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME;
        $nodes = new RecursiveDirectoryIterator($pathname, $flags);
        return new RecursiveIteratorIterator($nodes, RecursiveIteratorIterator::CHILD_FIRST);
    }

    private function removeLeafNode(string $pathname): void
    {
        $isWinOS = DIRECTORY_SEPARATOR === '\\';
        $isFile  = $isWinOS ? is_file($pathname) : is_file($pathname) || is_link($pathname);
        $isFile ? unlink($pathname) : rmdir($pathname);
    }
}
