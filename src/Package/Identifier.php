<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Package;


class Identifier
{
    private string  $name;
    private string  $version;
    private ?string $php;

    public function __construct(string $name, string $version, ?string $phpVersion = null)
    {
        $this->name    = $name;
        $this->version = $version;
        $this->php     = $phpVersion;
    }

    public function __toString(): string
    {
        return str_replace('/', '.', $this->name) . '.' . ($this->isResolved() ? $this->version : 'unresolved');
    }

    public function isResolved(): bool
    {
        return ctype_digit(str_replace('.', '', $this->version));
    }

    public function resolvedTo(string $version): self
    {
        return new self($this->name, $version);
    }

    public function composerRequire(): array
    {
        $required = [$this->name => $this->version];
        if ($this->php) {
            $required = ['php' => $this->php] + $required;
        }
        return ['require' => $required];
    }
}
