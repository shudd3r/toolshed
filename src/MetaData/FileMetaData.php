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

    public function __construct(string $toolsDirectory)
    {
        $this->toolsDirectory = $toolsDirectory;
    }

    public function installations(): array
    {
        $installDataFile = $this->toolsDirectory . DIRECTORY_SEPARATOR . 'install-locations.json';
        $installations = is_file($installDataFile)
            ? json_decode(file_get_contents($installDataFile), true) ?? []
            : [];

        $update = false;
        foreach ($installations as &$locations) {
            $remove = $this->missingLocations($locations);
            if (!$remove) { continue; }
            $locations = array_values(array_diff($locations, $remove));
            $update    = true;
        }

        if ($update) { $this->saveInstallations($installations); }

        return $installations;
    }

    public function saveInstallations(array $installations): void
    {
        if (!is_dir($this->toolsDirectory)) {
            mkdir($this->toolsDirectory, 0700);
        }

        $installDataFile = $this->toolsDirectory . DIRECTORY_SEPARATOR . 'install-locations.json';
        file_put_contents($installDataFile, json_encode($installations, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    private function missingLocations(array $locations): array
    {
        $missingLocations = [];
        foreach ($locations as $location) {
            if ($this->locationExists($location)) { continue; }
            $missingLocations[] = $location;
        }
        return $missingLocations;
    }

    private function locationExists(string $location): bool
    {
        return is_dir($location);
    }
}
