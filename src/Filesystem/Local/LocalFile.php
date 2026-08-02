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

use Shudd3r\Toolshed\Filesystem\File;
use Shudd3r\Toolshed\Filesystem\Exception;


class LocalFile extends LocalNode implements File
{
    public function exists(): bool
    {
        return is_file($this->pathname);
    }

    public function contents(): string
    {
        return $this->exists() ? file_get_contents($this->pathname) : '';
    }

    public function write(string $contents): void
    {
        if (is_dir($this->pathname) || ($this->exists() && !is_writable($this->pathname))) {
            $message = 'Cannot write to file `%s`';
            throw new Exception\FilesystemException(sprintf($message, $this->pathname));
        }

        is_dir(dirname($this->pathname)) || mkdir(dirname($this->pathname), 0700, true);
        file_put_contents($this->pathname, $contents);
    }

    public function remove(): void
    {
        $this->exists() && $this->removeLeafNode($this->pathname);
    }
}
