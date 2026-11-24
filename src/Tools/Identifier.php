<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tools;

use Composer\Semver\Constraint\ConstraintInterface as Constraint;
use Composer\Semver\Constraint\Constraint as SimpleConstraint;
use Composer\Semver\VersionParser;
use InvalidArgumentException;
use LogicException;


class Identifier
{
    /**
     * Static constructor from install directory name.
     *
     * @see self::installName()
     */
    public static function fromInstallName(string $installName): self
    {
        [$vendor, $package, $version] = explode('.', $installName, 3) + [null, '', ''];
        if (!$version || $version === 'unresolved') {
            throw new InvalidArgumentException('Install name must contain full package name & version number');
        }

        return self::fromStrings($vendor . '/' . $package, $version);
    }

    /** Static constructor with primitive types */
    public static function fromStrings(string $name, string $version = '*', string $phpVersion = '*'): self
    {
        return new self($name, self::parseConstraint($version), self::parseConstraint($phpVersion));
    }

    /** Helper version Constraint factory */
    public static function parseConstraint(string $version): Constraint
    {
        $parser = new VersionParser();
        return $parser->parseConstraints($version);
    }

    private string     $name;
    private Constraint $version;
    private Constraint $php;

    /**
     * @param string     $name
     * @param Constraint $version
     * @param Constraint $phpVersion
     */
    public function __construct(string $name, Constraint $version, Constraint $phpVersion)
    {
        $this->name    = $name;
        $this->version = $version;
        $this->php     = $phpVersion;
    }

    /** @return string Conventional Composer vendor/package name */
    public function packageName(): string
    {
        return $this->name;
    }

    /** @return string Resolved version string or 'unresolved' */
    public function version(): string
    {
        return $this->isResolved() ? $this->version->getPrettyString() : 'unresolved';
    }

    /** @return string Package name+version merged into nstall directory name */
    public function installName(): string
    {
        return str_replace('/', '.', $this->name) . '.' . $this->version();
    }

    /** @return bool True if identifier points to exact version */
    public function isResolved(): bool
    {
        return $this->isExactVersion($this->version);
    }

    /** @param Constraint $version Exact version constraint */
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

    /** @return array{require: array<string, string>} */
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
