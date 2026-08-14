<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Setup;

use Shudd3r\Toolshed\Setup;
use Composer\Util\ProcessExecutor;
use Shudd3r\Toolshed\Filesystem;
use Shudd3r\Toolshed\Sync\RefData;


class LocalSetup extends Setup
{
    protected function processor(): ProcessExecutor
    {
        return new ProcessExecutor();
    }

    protected function directory(string $pathname): Filesystem\Directory
    {
        is_dir($pathname) || mkdir($pathname, 0777, true);
        return Filesystem\Local\LocalDirectory::root($pathname);
    }

    protected function refData(Filesystem\File $dataFile): RefData
    {
        return new RefData\LocalRefData($dataFile);
    }
}
