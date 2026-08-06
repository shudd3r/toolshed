<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Tools\Identifier;
use InvalidArgumentException;
use LogicException;


class IdentifierTest extends TestCase
{
    public function testStaticConstructors()
    {
        $id = new Identifier('package/name', Identifier::parseConstraint('1.2.3'), Identifier::parseConstraint('*'));
        $this->assertEquals(Identifier::fromStrings('package/name', '1.2.3'), $id);
        $this->assertEquals(Identifier::fromInstallName('package.name.1.2.3'), $id);
    }

    public function testInstantiationWithNameWithoutVersion_ThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Identifier::fromInstallName('foo.bar');
    }

    /** @dataProvider exactConstraints */
    public function testExactConstraintsIdentifier(string $version)
    {
        $id = $this->id($version);
        $this->assertTrue($id->isResolved());
        $require = ['require' => ['vendor/package' => $version]];
        $this->assertSame($require, $id->composerRequire());
        $this->assertSame('vendor.package.' . $version, $id->installName());
        $this->assertSame('vendor/package', $id->packageName());
    }

    /** @dataProvider exactConstraints */
    public function testResolveTo_ForResolvedIdentifier_ThrowsException(string $version)
    {
        $id = $this->id($version);
        $this->expectException(LogicException::class);
        $id->resolvedTo(Identifier::parseConstraint('1.2.3'));
    }

    /** @dataProvider rangeConstraints */
    public function testUnresolvedIdentifier(string $version)
    {
        $id = $this->id($version);
        $this->assertFalse($id->isResolved());
        $require = ['require' => ['php' => '^7.4 || ^8.0', 'vendor/package' => $version]];
        $this->assertSame($require, $id->composerRequire());
        $this->assertSame('vendor.package.unresolved', $id->installName());
        $this->assertEquals($this->id('1.2.3'), $id->resolvedTo(Identifier::parseConstraint('1.2.3')));
        $this->assertSame('vendor/package', $id->packageName());
    }

    /** @dataProvider rangeConstraints */
    public function testResolveTo_ForRangeConstraint_ThrowsException(string $version)
    {
        $id = $this->id('^5.1 || ^6.0');
        $this->expectException(InvalidArgumentException::class);
        $id->resolvedTo(Identifier::parseConstraint($version));
    }

    public function exactConstraints(): array
    {
        return [['dev-master'], ['dev-branch'], ['0.1-beta'], ['1.2.3.4'], ['35.2.19']];
    }

    public function rangeConstraints(): array
    {
        return [['1.0 || 2.0'], ['~1.4.0'], ['>= 5.1.4 <= 7.5'], ['^7.3 || ^8.0']];
    }

    private function id(string $version): Identifier
    {
        return Identifier::fromStrings('vendor/package', $version, '^7.4 || ^8.0');
    }
}
