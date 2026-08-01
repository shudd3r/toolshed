<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Sync\RefData;

use Shudd3r\Toolshed\Sync\RefData;
use Shudd3r\Toolshed\Filesystem\Directory;


class FileRefData implements RefData
{
    private Directory $toolsDirectory;
    private ?array    $toolClientRefs = null;

    public function __construct(Directory $toolsDirectory)
    {
        $this->toolsDirectory = $toolsDirectory;
    }

    public function toolRefs(): array
    {
        $installData = $this->toolsDirectory->file('install-locations.json')->contents();
        $this->toolClientRefs = $installData ? json_decode($installData, true) ?? [] : [];
        return $this->existingInstallations($this->toolClientRefs);
    }

    public function save(array $toolRefs): void
    {
        if ($toolRefs === $this->toolClientRefs) { return; }
        $contents = json_encode($toolRefs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->toolsDirectory->file('install-locations.json')->write($contents);
    }

    private function existingInstallations(array $installations): array
    {
        foreach ($installations as &$locations) {
            $remove = array_filter($locations, fn (string $location) => !is_dir($location));
            if (!$remove) { continue; }
            $locations = array_values(array_diff($locations, $remove));
        }

        return $installations;
    }
}
