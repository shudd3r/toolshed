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


class ToolClients
{
    private MetaData $data;
    private array    $tracked;
    private array    $updated;

    public function __construct(MetaData $data)
    {
        $this->data    = $data;
        $this->tracked = $data->installations();
        $this->updated = $this->tracked;
    }

    public function update(array $toolVersions, string $clientBinDir): void
    {
        $clientBinDir = str_replace('\\', '/', $clientBinDir);
        foreach ($this->updated as $packageVer => &$locations) {
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
            if (isset($this->updated[$packageVer])) { continue; }
            $this->updated[$packageVer] = [$clientBinDir];
        }

        ksort($this->updated);
    }

    public function unusedTools(): array
    {
        return array_keys(array_filter($this->updated, fn (array $locations) => $locations === []));
    }

    public function remove(string $tool): void
    {
        unset($this->updated[$tool]);
    }

    public function __destruct()
    {
        if ($this->tracked === $this->updated) { return; }
        $this->data->saveInstallations($this->updated);
    }
}
