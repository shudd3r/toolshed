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
    private ProcessExecutor $processor;
    private string          $toolsDir;
    private string          $output = '';

    public function __construct(ProcessExecutor $processor, string $toolsDir)
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

        return $workDir ? $this->processor->execute($command, $this->output, $workDir) : 1;
    }

    public function output(): string
    {
        return $this->output;
    }

    private function validWorkDir(Identifier $tool): ?string
    {
        $workDir = $this->toolsDir . DIRECTORY_SEPARATOR . $tool;

        if (!is_dir($workDir)) {
            $this->output = sprintf('Tool directory `%s` does not exist', $tool);
            return null;
        }

        if (!is_file($workDir . DIRECTORY_SEPARATOR . 'composer.json')) {
            $this->output = sprintf('No composer.json in `%s` tool directory', $tool);
            return null;
        }

        return $workDir;
    }
}
