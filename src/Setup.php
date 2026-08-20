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
use Composer\Package\RootPackageInterface;
use Composer\Semver\Constraint\ConstraintInterface as Constraint;
use Shudd3r\Toolshed\Tools\Identifier;


abstract class Setup
{
    public function sharedTools(Composer $composer, IOInterface $io): SharedTools
    {
        $toolsDir = $this->directory($composer->getConfig()->get('home'))->subdirectory('shared-tools');
        $tools    = new Tools($this->processor(), $toolsDir);
        $registry = new UsageRegistry($this->refData($toolsDir->file('install-locations.json')));
        return new SharedTools($tools, $registry, $io);
    }

    public function requestedTools(Composer $composer, IOInterface $io): RequestedTools
    {
        $clientBinDir    = $this->directory($composer->getConfig()->get('bin-dir'));
        $toolIdentifiers = $this->toolIdentifiers($composer->getPackage(), $io);
        return new RequestedTools($clientBinDir, $toolIdentifiers);
    }

    abstract protected function processor(): ProcessExecutor;

    abstract protected function directory(string $pathname): Directory;

    abstract protected function refData(File $dataFile): RefData;

    /** @returns Identifier[] */
    private function toolIdentifiers(RootPackageInterface $package, IOInterface $io): array
    {
        $devLinks      = $package->getDevRequires();
        $phpConstraint = $this->phpConstraint($package);

        $toolIds = [];
        foreach ($package->getExtra()['shared-tools'] ?? [] as $toolName) {
            if (!isset($devLinks[$toolName])) {
                $message = '[SKIPPED] Shared dev tool `%s` not found in require-dev composer.json';
                $io->writeError(sprintf($message, $toolName));
                continue;
            }
            $toolIds[] = new Identifier($toolName, $devLinks[$toolName]->getConstraint(), $phpConstraint);
        }
        return $toolIds;
    }

    private function phpConstraint(RootPackageInterface $package): Constraint
    {
        $links = $package->getRequires();
        $php   = $links['php'] ?? null;
        return $php ? $php->getConstraint() : Identifier::parseConstraint('*');
    }
}
