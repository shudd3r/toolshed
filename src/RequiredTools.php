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


class RequiredTools
{
    private string $packageBinDirectory;
    private string $phpConstraint;
    private array  $toolConstraints;

    public function __construct(string $packageBinDirectory, string $phpConstraint, array $toolConstraints)
    {
        $this->packageBinDirectory = $packageBinDirectory;
        $this->phpConstraint       = $phpConstraint;
        $this->toolConstraints     = $toolConstraints;
    }

    public function packageBinDirectory(): string
    {
        return $this->packageBinDirectory;
    }

    public function phpConstraint(): string
    {
        return $this->phpConstraint;
    }

    public function toolConstraints(): array
    {
        return $this->toolConstraints;
    }
}
