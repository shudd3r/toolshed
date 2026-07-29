<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tools\Tool;

use Composer\Util\ProcessExecutor;


class Composer
{
    private string $toolsDir;
    private string $output = '';

    public function __construct(string $toolsDir)
    {
        $this->toolsDir = $toolsDir;
    }

    public function install(Identifier $tool): int
    {
        $this->output = '';
        $options  = $tool->isResolved() ? '' : '--dry-run --no-install ';
        $command  = 'composer update ' . $options . '--no-progress 2>&1';
        $composer = new ProcessExecutor();
        $workDir  = $this->toolsDir . DIRECTORY_SEPARATOR . $tool;

        if (!is_dir($workDir)) {
            $this->output = 'Tool directory does not exist';
            return 1;
        }

        return $composer->execute($command, $this->output, $workDir);
    }

    public function output(): string
    {
        return $this->output;
    }
}
