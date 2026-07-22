<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Package;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Package\Identifier;


class IdentifierTest extends TestCase
{
    public function testIdentifierRepresentations()
    {
        $id = new Identifier('phpunit/phpunit', '^9.5', '^7.4');
        $this->assertFalse($id->isResolved());
        $this->assertSame('phpunit.phpunit.unresolved', (string) $id);
        $unresolvedRequire = ['require' => ['php' => '^7.4', 'phpunit/phpunit' => '^9.5']];
        $this->assertEquals($unresolvedRequire, $id->composerRequire());

        $id = $id->resolvedTo('1.2.3');
        $this->assertTrue($id->isResolved());
        $this->assertSame('phpunit.phpunit.1.2.3', (string) $id);
        $this->assertEquals(new Identifier('phpunit/phpunit', '1.2.3'), $id);
        $resolvedRequire = ['require' => ['phpunit/phpunit' => '1.2.3']];
        $this->assertEquals($resolvedRequire, $id->composerRequire());
    }
}
