<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Doubles;

use Composer\Util\ProcessExecutor;
use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\File;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Setup;
use Shudd3r\Toolshed\Sync\RefData;


class FakeSetup extends Setup
{
    protected function processor(): ProcessExecutor
    {
        return new FakeProcessExecutor();
    }

    protected function directory(string $pathname): Directory
    {
        return VirtualDirectory::root($pathname);
    }

    protected function refData(File $dataFile): RefData
    {
        return new FakeRefData();
    }
}
