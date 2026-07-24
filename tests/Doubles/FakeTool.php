<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Doubles;

use Shudd3r\Toolshed\Tools\Tool;


class FakeTool implements Tool
{
    public bool $synchronized = false;
    public bool $exists       = false;

    private Tool\Identifier $identifier;
    private array           $binaries;

    public function __construct(?Tool\Identifier $identifier = null, array $binaries = [])
    {
        $this->identifier = $identifier ?? Tool\Identifier::fromString('vendor.package.unresolved');
        $this->binaries   = $binaries;
    }

    public function identifier(): Tool\Identifier
    {
        return $this->identifier;
    }

    public function synchronize(string $projectBinDirectory): void
    {
        if (!$this->identifier->isResolved()) {
            $this->identifier = $this->identifier->resolvedTo(Tool\Identifier::parseConstraint('8.2.3'));
        }

        $this->exists       = true;
        $this->synchronized = true;
    }

    public function remove(string $projectBinDirectory): void
    {
        $this->exists = false;
    }

    public function binaries(): array
    {
        return $this->identifier->isResolved() ? $this->binaries : [];
    }
}
