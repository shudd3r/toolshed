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

use Composer\Util\ProcessExecutor;
use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Tools\Tool;
use Shudd3r\Toolshed\Tools\Exception;


class Tools
{
    private ProcessExecutor $processor;
    private Directory       $toolsDir;

    public function __construct(ProcessExecutor $processor, Directory $toolsDir)
    {
        $this->processor = $processor;
        $this->toolsDir  = $toolsDir;
    }

    public function install(Identifier $tool): Tool
    {
        $tool->isResolved() && $this->toolsDir->subdirectory($tool->installName() . '/vendor/bin')->remove();

        $options   = $tool->isResolved() ? '' : '--dry-run --no-install ';
        $command   = 'composer update ' . $options . '--no-progress 2>&1';
        $errorCode = $this->processor->execute($command, $output, $this->workDir($tool));
        if ($errorCode) {
            $message = 'Installation of `%s` shared tool failed';
            throw new Exception\ToolSetupException(sprintf($message, $tool->packageName()), $errorCode);
        }

        $tool->isResolved() || $this->remove($tool);

        return $tool->isResolved()
            ? new Tool($tool, $this->toolsDir->subdirectory($tool->installName())->subdirectory('vendor/bin'))
            : $this->install($this->resolvedTool($tool, $output));
    }

    public function remove(Identifier $tool): void
    {
        $this->toolsDir->subdirectory($tool->installName())->remove();
    }

    private function workDir(Identifier $tool): string
    {
        $workDir      = $this->toolsDir->subdirectory($tool->installName());
        $composerJson = json_encode($tool->composerRequire(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $workDir->file('composer.json')->write($composerJson);

        return (string) $workDir;
    }

    private function resolvedTool(Identifier $tool, string $output): Identifier
    {
        $lockedVersion = $this->parsedVersion($tool->packageName(), $output);
        if (!$lockedVersion) {
            throw new Exception\ToolSetupException(sprintf('Cannot parse `%s` tool version', $tool->packageName()));
        }

        return $tool->resolvedTo(Identifier::parseConstraint($lockedVersion));
    }

    private function parsedVersion(string $packageName, string $output): string
    {
        $searchPhrase  = '- Locking ' . $packageName . ' (';
        $lockedPackage = strpos($output, $searchPhrase);
        if (!$lockedPackage) { return ''; }

        $versionStart = $lockedPackage + strlen($searchPhrase);
        $versionEnd   = strpos($output, ')', $versionStart);
        return explode(' ', substr($output, $versionStart, $versionEnd - $versionStart))[0];
    }
}
