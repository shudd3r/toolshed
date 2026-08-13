<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;
use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\File;
use Shudd3r\Toolshed\Sync\RefData;
use Shudd3r\Toolshed\Sync\UsageRegistry;


abstract class Setup
{
    public function sharedTools(Composer $composer, IOInterface $io): SharedTools
    {
        $toolsDir = $this->directory($composer->getConfig()->get('home'))->subdirectory('shared-tools');
        $tools    = new Tools($this->processor(), $toolsDir);
        $registry = new UsageRegistry($this->refData($toolsDir->file('install-locations.json')));
        return new SharedTools($tools, $registry, $io);
    }

    abstract protected function processor(): ProcessExecutor;

    abstract protected function directory(string $pathname): Directory;

    abstract protected function refData(File $dataFile): RefData;
}
