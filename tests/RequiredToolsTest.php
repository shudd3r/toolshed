<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\RequiredTools;
use Composer\Semver\Constraint\Constraint;


class RequiredToolsTest extends TestCase
{
    public function testInstanceDataMethods()
    {
        $packageBinDirectory = __DIR__;
        $phpConstraint       = '^7.4';
        $toolConstraints = [
            'phpunit/phpunit'  => new Constraint('=', '1.2.3'),
            'polymorphine/dev' => new Constraint('=', '0.6.0')
        ];

        $tools = new RequiredTools($packageBinDirectory, $phpConstraint, $toolConstraints);

        $this->assertSame($packageBinDirectory, $tools->packageBinDirectory());
        $this->assertSame($phpConstraint, $tools->phpConstraint());
        $this->assertSame($toolConstraints, $tools->toolConstraints());
    }
}
