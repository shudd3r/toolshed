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

use Composer\Semver\Constraint\ConstraintInterface as Constraint;
use Composer\Semver\Constraint\Constraint as SimpleConstraint;
use Composer\Semver\VersionParser;
use InvalidArgumentException;
use LogicException;


class Identifier
{
    public static function fromString(string $identifier): self
    {
        [$vendor, $package, $version] = explode('.', $identifier, 3) + ['', '', '*'];
        $constraint = self::parseConstraint($version === 'unresolved' ? '*' : $version);
        return new self($vendor . '/' . $package, $constraint, self::parseConstraint('*'));
    }

    public static function parseConstraint(string $version): Constraint
    {
        $parser = new VersionParser();
        return $parser->parseConstraints($version);
    }

    private string     $name;
    private Constraint $version;
    private Constraint $php;

    public function __construct(string $name, Constraint $version, Constraint $phpVersion)
    {
        $this->name    = $name;
        $this->version = $version;
        $this->php     = $phpVersion;
    }

    public function __toString(): string
    {
        $version = $this->isResolved() ? $this->version->getPrettyString() : 'unresolved';
        return str_replace('/', '.', $this->name) . '.' . $version;
    }

    public function isResolved(): bool
    {
        return $this->isExactVersion($this->version);
    }

    public function resolvedTo(Constraint $version): self
    {
        if ($this->isResolved()) {
            $message = 'Version already resolved for `%s` package.';
            throw new LogicException(sprintf($message, $this->name));
        }

        if (!$this->isExactVersion($version)) {
            $message = 'Exact version constraint required for `%s` package - "%s" given';
            throw new InvalidArgumentException(sprintf($message, $this->name, $version->getPrettyString()));
        }
        return new self($this->name, $version, $this->php);
    }

    public function composerRequire(): array
    {
        $required = $this->isResolved() ? [] : ['php' => $this->php->getPrettyString()];
        return ['require' => $required + [$this->name => $this->version->getPrettyString()]];
    }

    private function isExactVersion(Constraint $version): bool
    {
        return $version instanceof SimpleConstraint && $version->getOperator() === '==';
    }
}
