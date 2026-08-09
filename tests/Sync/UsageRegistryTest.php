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
        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/some/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3')
        ]));

        $this->assertSame([], $data->toolRefs());
        unset($tracker);
        $this->assertSame(['phpunit.phpunit.9.6.3' => ['/some/path']], $data->toolRefs());
    }

    public function testNewToolsUpdate_AddsToolEntries()
    {
        $data    = new FakeRefData(['phpunit.phpunit.9.6.3' => ['/some/path']]);
        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/different/path', [
            Identifier::fromStrings('vendor/package', 'dev-develop'),
            Identifier::fromStrings('polymorphine/dev', '0.6.0')
        ]));

        $this->assertData([
            'phpunit.phpunit.9.6.3'      => ['/some/path'],
            'polymorphine.dev.0.6.0'     => ['/different/path'],
            'vendor.package.dev-develop' => ['/different/path']
        ], $data, $tracker);
    }

    public function testExistingToolsUpdate_AddsLocations()
    {
        $data    = new FakeRefData(['phpunit.phpunit.9.6.3' => ['/some/path']]);
        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/different/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3'),
            Identifier::fromStrings('new/tool', '1.2.3')
        ]));

        $this->assertData([
            'new.tool.1.2.3'        => ['/different/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path']
        ], $data, $tracker);

        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/zzz/path', [
            Identifier::fromStrings('phpunit/phpunit', '9.6.3'),
            Identifier::fromStrings('new/tool', '1.2.3')
        ]));

        $this->assertData([
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path', '/zzz/path']
        ], $data, $tracker);
    }

    public function testPathForNotRequiredTool_IsRemoved()
    {
        $data = new FakeRefData([
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path', '/zzz/path']
        ]);
        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/zzz/path', [
            Identifier::fromStrings('new/tool', '1.2.3')
        ]));

        $this->assertData([
            'new.tool.1.2.3'        => ['/different/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/different/path', '/some/path']
        ], $data, $tracker);

        $tracker = $this->tracker($data);

        $tracker->update($this->requested('/different/path', []));

        $this->assertData([
            'new.tool.1.2.3'        => ['/zzz/path'],
            'phpunit.phpunit.9.6.3' => ['/some/path']
        ], $data, $tracker);
    }

    public function testUnusedTools_ReturnsListOfToolIdentifiersWithoutLocations()
    {
        $data = new FakeRefData([
            'new.tool.1.2.3'         => ['/some/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3'  => ['/some/path'],
            'polymorphine.dev.0.6.0' => ['/some/path'],
            'zzold.tool.0.2.3'       => []
        ]);
        $tracker = $this->tracker($data);
        $this->assertEquals([
            Identifier::fromInstallName('zzold.tool.0.2.3')
        ], $tracker->unusedTools());

        $tracker->update($this->requested('/some/path', [
            Identifier::fromStrings('new/tool', '1.2.3'),
            Identifier::fromStrings('polymorphine/dev', '0.6.0')
        ]));

        $this->assertEquals([
            Identifier::fromInstallName('phpunit.phpunit.9.6.3'),
            Identifier::fromInstallName('zzold.tool.0.2.3')
        ], $tracker->unusedTools());
        $this->assertData([
            'new.tool.1.2.3'         => ['/some/path', '/zzz/path'],
            'phpunit.phpunit.9.6.3'  => [],
            'polymorphine.dev.0.6.0' => ['/some/path'],
            'zzold.tool.0.2.3'       => []
        ], $data, $tracker);
    }

    public function testRemovingTools()
    {
        $data = new FakeRefData([
            'new.tool.1.2.3'         => ['not/existing/path'],
            'phpunit.phpunit.9.6.3'  => ['not/existing/path', 'some/path'],
            'polymorphine.dev.0.6.0' => [],
            'zzold.tool.0.2.3'       => []
        ]);
        $tracker = $this->tracker($data);

        $tracker->remove(Identifier::fromInstallName('new.tool.1.2.3'));
        $tracker->remove(Identifier::fromInstallName('no.entry.2.54.3-dev'));
        $tracker->remove(Identifier::fromInstallName('polymorphine.dev.0.6.0'));

        $this->assertData([
            'phpunit.phpunit.9.6.3' => ['not/existing/path', 'some/path'],
            'zzold.tool.0.2.3'      => []
        ], $data, $tracker);
    }

    private function assertData(array $expected, FakeRefData $data, UsageRegistry &$toolClients): void
    {
        $toolClients = null;
        $this->assertSame($expected, $data->toolRefs());
    }

    private function tracker(?FakeRefData &$data = null): UsageRegistry
    {
        return new UsageRegistry($data ??= new FakeRefData([]));
    }

    private function requested(string $directoryPath, array $identifiers): RequestedTools
    {
        return new RequestedTools(Virtual\VirtualDirectory::root($directoryPath), $identifiers);
    }
}
