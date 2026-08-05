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

use Shudd3r\Toolshed\Filesystem\Directory;
use Shudd3r\Toolshed\Filesystem\Exception;


abstract class RefData
{
    private Directory $toolsDirectory;
    private ?array    $toolClientRefs = null;

    public function __construct(Directory $toolsDirectory)
    {
        $this->toolsDirectory = $toolsDirectory;
    }

    /** @return array<string, array<string>> */
    public function toolRefs(): array
    {
        $installData = $this->toolsDirectory->file('install-locations.json')->contents();
        $this->toolClientRefs = $installData ? json_decode($installData, true) ?? [] : [];
        return $this->existingInstallations($this->toolClientRefs);
    }

    /**
     * @param array<string, array<string>> $toolRefs
     *
     * @throws Exception\FilesystemException
     */
    public function save(array $toolRefs): void
    {
        if ($toolRefs === $this->toolClientRefs) { return; }
        $contents = json_encode($toolRefs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->toolsDirectory->file('install-locations.json')->write($contents);
    }

    abstract protected function directoryExists(string $directoryPath): bool;

    private function existingInstallations(array $installations): array
    {
        foreach ($installations as &$locations) {
            $locations = array_values(array_filter($locations, [$this, 'directoryExists']));
        }

        return $installations;
    }
}
