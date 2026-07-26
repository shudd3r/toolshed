<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\MetaData;

use Shudd3r\Toolshed\MetaData;


class FileMetaData implements MetaData
{
    private string $toolsDirectory;
    private ?array $toolClientRefs = null;

    public function __construct(string $toolsDirectory)
    {
        $this->toolsDirectory = $toolsDirectory;
    }

    public function installations(): array
    {
        $installDataFile = $this->toolsDirectory . DIRECTORY_SEPARATOR . 'install-locations.json';
        $this->toolClientRefs = is_file($installDataFile)
            ? json_decode(file_get_contents($installDataFile), true) ?? []
            : [];
        return $this->existingInstallations($this->toolClientRefs);
    }

    public function saveInstallations(array $installations): void
    {
        if ($installations === $this->toolClientRefs) { return; }
        if (!is_dir($this->toolsDirectory)) {
            mkdir($this->toolsDirectory, 0700);
        }

        $installDataFile = $this->toolsDirectory . DIRECTORY_SEPARATOR . 'install-locations.json';
        file_put_contents($installDataFile, json_encode($installations, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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
