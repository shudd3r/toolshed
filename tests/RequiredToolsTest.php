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
use Shudd3r\Toolshed\Package\Identifier;


class RequiredToolsTest extends TestCase
{
    public function testInstanceDataMethods()
    {
        $packageBinDirectory = __DIR__;
        $phpConstraint       = Identifier::parseConstraint('^7.4 || ^8.0');
        $toolIdentifiers = [
            new Identifier('phpunit/phpunit', Identifier::parseConstraint('^9.5'), $phpConstraint),
            new Identifier('polymorphine/dev', Identifier::parseConstraint('0.6.0'), $phpConstraint)
        ];

        $tools = new RequiredTools($packageBinDirectory, $toolIdentifiers);
        $this->assertSame($packageBinDirectory, $tools->packageBinDirectory());
        $this->assertSame($toolIdentifiers, $tools->toolIdentifiers());
    }
}
