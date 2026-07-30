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

use Composer\Util\ProcessExecutor;


class FakeProcessExecutor extends ProcessExecutor
{
    private const VALID_OUTPUT = <<<'CLI'
        Loading composer repositories with package information
        Updating dependencies
        Lock file operations: 28 installs, 0 updates, 0 removals
          - Locking acme/some-package (1.5.0)
          - Locking another/package-name (7.1.3)
          - Locking bar/baz (dev-main)
          - Locking foo/bar-baz (2.13.4)
          - Locking something/else (0.14.2-dev)
          - Locking test/package (9.10.11)
          - Locking vendor/super-tool (v3.1.8)
        2 package suggestions were added by new dependencies, use `composer suggest` to see details.
        No security vulnerability advisories found.
        CLI;

    private const INVALID_OUTPUT = <<<'CLI'
        Loading composer repositories with package information
        Updating dependencies
        Your requirements could not be resolved to an installable set of packages.
        
          Problem 1
            - Root composer.json requires test/package ^9.6, found test/package[9.5.11] but it does not match the constraint.
        CLI;

    public string $command = '';

    private int $errorCode;

    public function __construct(int $errorCode = 0)
    {
        $this->errorCode = $errorCode;
        parent::__construct();
    }

    public function execute($command, &$output = null, ?string $cwd = null): int
    {
        $this->command = $command;
        $output = $this->errorCode ? self::INVALID_OUTPUT : self::VALID_OUTPUT;
        return $this->errorCode;
    }
}
