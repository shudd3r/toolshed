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

use Composer\IO\IOInterface as Output;
use Composer\Util\ProcessExecutor;


class Composer
{
    private Output $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function execute(string $command, string $packageDir): string
    {
        $command  = 'composer ' . $command . ' 2>&1';
        $composer = new ProcessExecutor($this->output);
        $status   = $composer->execute($command, $output, $packageDir);

        if ($status) {
            $this->output->writeError("<error>Command failed:</error>\n" . $output);
            return '';
        }

        return $output;
    }
}
