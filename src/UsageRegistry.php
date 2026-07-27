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


class UsageRegistry
{
    private RefData $refData;
    private ?array  $toolRefs = null;

    public function __construct(RefData $refData)
    {
        $this->refData = $refData;
    }

    public function update(array $toolVersions, string $clientBinDir): void
    {
        $this->toolRefs ??= $this->refData->toolRefs();
        $clientBinDir = str_replace('\\', '/', $clientBinDir);
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
        return array_keys(array_filter($this->toolRefs, fn (array $locations) => $locations === []));
    }

    public function remove(string $tool): void
    {
        $this->toolRefs ??= $this->refData->toolRefs();
        unset($this->toolRefs[$tool]);
    }

    public function __destruct()
    {
        if (!isset($this->toolRefs)) { return; }
        $this->refData->save($this->toolRefs);
    }
}
