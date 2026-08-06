<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Sync;

use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\Tools\Identifier;


class UsageRegistry
{
    private RefData $refData;
    private ?array  $toolRefs = null;

    public function __construct(RefData $refData)
    {
        $this->refData = $refData;
    }

    public function update(RequestedTools $tools): void
    {
        $clientBinDir = str_replace('\\', '/', (string) $tools->clientBinDirectory());
        $toolVersions = array_map(fn (Identifier $id): string => $id->installName(), $tools->toolIdentifiers());

        $this->toolRefs ??= $this->refData->toolRefs();
        foreach ($this->toolRefs as $packageVer => &$locations) {
            $toolFound = in_array($packageVer, $toolVersions, true);
            $pathFound = in_array($clientBinDir, $locations, true);
            if ($toolFound && !$pathFound) {
                $locations[] = $clientBinDir;
                sort($locations);
            } elseif (!$toolFound && $pathFound) {
                $locations = array_values(array_diff($locations, [$clientBinDir]));
            }
        }

        foreach ($toolVersions as $packageVer) {
            if (isset($this->toolRefs[$packageVer])) { continue; }
            $this->toolRefs[$packageVer] = [$clientBinDir];
        }

        ksort($this->toolRefs);
    }

    public function unusedTools(): array
    {
        $this->toolRefs ??= $this->refData->toolRefs();
        $installNames = array_keys(array_filter($this->toolRefs, fn (array $locations) => $locations === []));
        return array_map(fn (string $installName) => Identifier::fromInstallName($installName), $installNames);
    }

    public function remove(Identifier $tool): void
    {
        $this->toolRefs ??= $this->refData->toolRefs();
        unset($this->toolRefs[$tool->installName()]);
    }

    public function __destruct()
    {
        isset($this->toolRefs) && $this->refData->save($this->toolRefs);
    }
}
