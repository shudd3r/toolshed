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


class UsageRegistryTest extends TestCase
{
    public function testMetaDataDestructor_SavesChanges()
    {
        $tracker = $this->tracker([]);
        $tracker->update(['phpunit.phpunit.9.6.3'], 'some/path');
        $this->assertSame([], FakeRefData::$toolRefs);
        $this->assertData(['phpunit.phpunit.9.6.3' => ['some/path']], $tracker);
    }

    public function testNewToolsUpdate_AddsToolEntries()
    {
        $tracker = $this->tracker(['phpunit.phpunit.9.6.3' => ['some/path']]);
        $tracker->update(['vendor.package.dev-develop', 'polymorphine.dev.0.6.0'], 'different/path');
        $expected = [
            'phpunit.phpunit.9.6.3'      => ['some/path'],
            'polymorphine.dev.0.6.0'     => ['different/path'],
            'vendor.package.dev-develop' => ['different/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testExistingToolsUpdate_AddsLocations()
    {
        $tracker = $this->tracker(['phpunit.phpunit.9.6.3' => ['some/path']]);
        $tracker->update(['phpunit.phpunit.9.6.3', 'new.tool.1.2.3'], 'different/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path']
        ];
        $this->assertData($expected, $tracker);

        $tracker = $this->tracker();
        $tracker->update(['phpunit.phpunit.9.6.3', 'new.tool.1.2.3'], 'zzz/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path', 'zzz/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testPathForNotRequiredTool_IsRemoved()
    {
        $tracker = $this->tracker([
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path', 'zzz/path']
        ]);
        $tracker->update(['new.tool.1.2.3'], 'zzz/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path']
        ];
        $this->assertData($expected, $tracker);

        $tracker = $this->tracker();
        $tracker->update([], 'different/path');
        $expected = [
            'new.tool.1.2.3'        => ['zzz/path'],
            'phpunit.phpunit.9.6.3' => ['some/path']
        ];
        $this->assertData($expected, $tracker);
    }

    public function testUnusedTools_ReturnsListOfToolsWithoutLocations()
    {
        $tracker = $this->tracker([
            'new.tool.1.2.3'         => ['some/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3'  => ['some/path'],
            'polymorphine.dev.0.6.0' => ['some/path'],
            'zzold.tool.0.2.3'       => []
        ]);
        $this->assertSame(['zzold.tool.0.2.3'], $tracker->unusedTools());

        $tracker->update(['new.tool.1.2.3', 'polymorphine.dev.0.6.0'], 'some/path');
        $expected = [
            'new.tool.1.2.3'         => ['some/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3'  => [],
            'polymorphine.dev.0.6.0' => ['some/path'],
            'zzold.tool.0.2.3'       => []
        ];
        $this->assertSame(['phpunit.phpunit.9.6.3', 'zzold.tool.0.2.3'], $tracker->unusedTools());
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
        $tracker->remove('new.tool.1.2.3');
        $tracker->remove('no.entry.2.54.3-dev');
        $tracker->remove('polymorphine.dev.0.6.0');
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
}
