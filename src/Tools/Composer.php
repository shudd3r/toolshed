<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tools;

use Composer\Util\ProcessExecutor;
use Shudd3r\Toolshed\Filesystem\Directory;


class Composer
{
    private ProcessExecutor $processor;
    private Directory       $toolsDir;
    private string          $output = '';

    public function __construct(ProcessExecutor $processor, Directory $toolsDir)
    {
        $this->processor = $processor;
        $this->toolsDir  = $toolsDir;
    }

    public function install(Identifier $tool): int
    {
        $this->output = '';

        $options = $tool->isResolved() ? '' : '--dry-run --no-install ';
        $command = 'composer update ' . $options . '--no-progress 2>&1';
        $workDir = $this->validWorkDir($tool);

        return $workDir ? $this->processor->execute($command, $this->output, (string) $workDir) : 1;
    }

    public function output(): string
    {
        return $this->output;
    }

    private function validWorkDir(Identifier $tool): ?Directory
    {
        $workDir = $this->toolsDir->subdirectory($tool->installName());
        if ($workDir->file('composer.json')->exists()) { return $workDir; }

        $this->output = $workDir->exists()
            ? sprintf('No composer.json in `%s` tool directory', $tool->installName())
            : sprintf('Tool directory `%s` does not exist', $tool->installName());

        return null;
    }
}
