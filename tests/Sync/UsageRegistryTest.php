<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Sync;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Sync\UsageRegistry;
use Shudd3r\Toolshed\Tests\Doubles\FakeRefData;
use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\Filesystem\Virtual;
use Shudd3r\Toolshed\Tools\Identifier;


class UsageRegistryTest extends TestCase
{
    public function testMetaDataDestructor_SavesChanges()
    {
        $tracker = $this->tracker([]);
        $requested = $this->requested('/some/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3')
        ]);

        $tracker->update($requested);
        $this->assertSame([], FakeRefData::$toolRefs);
        $this->assertData(['phpunit.phpunit.9.6.3' => ['/some/path']], $tracker);
    }

    public function testNewToolsUpdate_AddsToolEntries()
    {
        $tracker = $this->tracker(['phpunit.phpunit.9.6.3' => ['/some/path']]);
        $requested = $this->requested('/different/path', [
            Identifier::fromStrings('vendor/package', 'dev-develop'),
            Identifier::fromStrings('polymorphine/dev', '0.6.0')
        ]);

        $tracker->update($requested);
        $expected = [
            'phpunit.phpunit.9.6.3'      => ['/some/path'],
            'polymorphine.dev.0.6.0'     => ['/different/path'],
            'vendor.package.dev-develop' => ['/different/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testExistingToolsUpdate_AddsLocations()
    {
        $tracker = $this->tracker(['phpunit.phpunit.9.6.3' => ['/some/path']]);
        $requested = $this->requested('/different/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3'),
            Identifier::fromStrings('new/tool', '1.2.3')
        ]);

        $tracker->update($requested);
        $expected = [
            'new.tool.1.2.3'        => ['/different/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path']
        ];
        $this->assertData($expected, $tracker);

        $tracker = $this->tracker();
        $requested = $this->requested('/zzz/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3'),
            Identifier::fromStrings('new/tool', '1.2.3')
        ]);

        $tracker->update($requested);
        $expected = [
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path', '/zzz/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testPathForNotRequiredTool_IsRemoved()
    {
        $tracker = $this->tracker([
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path', '/zzz/path']
        ]);
        $requested = $this->requested('/zzz/path', [
            Identifier::fromStrings('new/tool', '1.2.3')
        ]);

        $tracker->update($requested);
        $expected = [
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path']
        ];
        $this->assertData($expected, $tracker);

        $tracker   = $this->tracker();
        $requested = $this->requested('/different/path', []);
        $tracker->update($requested);
        $expected = [
            'new.tool.1.2.3'        => ['/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/some/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testUnusedTools_ReturnsListOfToolIdentifiersWithoutLocations()
    {
        $tracker = $this->tracker([
            'new.tool.1.2.3'         => ['/some/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3'  => ['/some/path'],
            'polymorphine.dev.0.6.0' => ['/some/path'],
            'zzold.tool.0.2.3'       => []
        ]);
        $this->assertEquals([Identifier::fromInstallName('zzold.tool.0.2.3')], $tracker->unusedTools());

        $requested = $this->requested('/some/path', [
            Identifier::fromStrings('new/tool', '1.2.3'),
            Identifier::fromStrings('polymorphine/dev', '0.6.0')
        ]);

        $tracker->update($requested);
        $unused = [
            Identifier::fromInstallName('phpunit.phpunit.9.6.3'),
            Identifier::fromInstallName('zzold.tool.0.2.3')
        ];
        $this->assertEquals($unused, $tracker->unusedTools());

        $expected = [
            'new.tool.1.2.3'         => ['/some/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3'  => [],
            'polymorphine.dev.0.6.0' => ['/some/path'],
            'zzold.tool.0.2.3'       => []
        ];
        $this->assertData($expected, $tracker);
    }

    public function testRemovingTools()
    {
        $tracker = $this->tracker([
            'new.tool.1.2.3'         => ['not/existing/path'],
            'phpunit.phpunit.9.6.3'  => ['not/existing/path', 'some/path'],
            'polymorphine.dev.0.6.0' => [],
            'zzold.tool.0.2.3'       => []
        ]);
        $tracker->remove(Identifier::fromInstallName('new.tool.1.2.3'));
        $tracker->remove(Identifier::fromInstallName('no.entry.2.54.3-dev'));
        $tracker->remove(Identifier::fromInstallName('polymorphine.dev.0.6.0'));
        $expected = [
            'phpunit.phpunit.9.6.3' => ['not/existing/path', 'some/path'],
            'zzold.tool.0.2.3'      => []
        ];
        $this->assertData($expected, $tracker);
    }

    private function assertData(array $expected, UsageRegistry &$toolClients): void
    {
        $toolClients = null;
        $this->assertSame($expected, FakeRefData::$toolRefs);
    }

    private function tracker(?array $installations = null): UsageRegistry
    {
        return new UsageRegistry(new FakeRefData($installations));
    }

    private function requested(string $directoryPath, array $identifiers): RequestedTools
    {
        return new RequestedTools(Virtual\VirtualDirectory::root($directoryPath), $identifiers);
    }
}
