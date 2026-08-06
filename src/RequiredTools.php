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

use Shudd3r\Toolshed\Tools\Identifier;


class RequiredTools
{
    private string $packageBinDirectory;
    private array  $toolIdentifiers;

    /** @param array<Identifier> $toolIdentifiers */
    public function __construct(string $packageBinDirectory, array $toolIdentifiers = [])
    {
        $this->packageBinDirectory = $packageBinDirectory;
        $this->toolIdentifiers     = $this->indexedIdentifiers(...$toolIdentifiers);
    }

    public function packageBinDirectory(): string
    {
        return $this->packageBinDirectory;
    }

    /** @return array<Identifier> */
    public function toolIdentifiers(): array
    {
        return array_values($this->toolIdentifiers);
    }

    public function update(Identifier $tool): void
    {
        $this->toolIdentifiers[$tool->packageName()] = $tool;
    }

    private function indexedIdentifiers(Identifier ...$identifiers): array
    {
        $toolIdentifiers = [];
        foreach ($identifiers as $identifier) {
            $toolIdentifiers[$identifier->packageName()] = $identifier;
        }

        return $toolIdentifiers;
    }
}
