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
use Shudd3r\Toolshed\ToolClients;
use Shudd3r\Toolshed\Tests\Doubles\FakeMetaData as Data;


class ToolClientsTest extends TestCase
{
    public function testMetaDataDestructor_SavesChanges()
    {
        $clients = $this->clients([]);
        $clients->update(['phpunit.phpunit.9.6.3'], 'some/path');
        $this->assertSame([], Data::$installations);
        $this->assertData(['phpunit.phpunit.9.6.3' => ['some/path']], $clients);
    }

    public function testNewToolsUpdate_AddsToolEntries()
    {
        $clients = $this->clients(['phpunit.phpunit.9.6.3' => ['some/path']]);
        $clients->update(['vendor.package.dev-develop', 'polymorphine.dev.0.6.0'], 'different/path');
        $expected = [
            'phpunit.phpunit.9.6.3'      => ['some/path'],
            'polymorphine.dev.0.6.0'     => ['different/path'],
            'vendor.package.dev-develop' => ['different/path']
        ];
        $this->assertData($expected, $clients);
    }

    public function testExistingToolsUpdate_AddsLocations()
    {
        $clients = $this->clients(['phpunit.phpunit.9.6.3' => ['some/path']]);
        $clients->update(['phpunit.phpunit.9.6.3', 'new.tool.1.2.3'], 'different/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path']
        ];
        $this->assertData($expected, $clients);

        $clients = $this->clients();
        $clients->update(['phpunit.phpunit.9.6.3', 'new.tool.1.2.3'], 'zzz/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path', 'zzz/path']
        ];
        $this->assertData($expected, $clients);
    }

    public function testPathForNotRequiredTool_IsRemoved()
    {
        $clients = $this->clients([
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path', 'zzz/path']
        ]);
        $clients->update(['new.tool.1.2.3'], 'zzz/path');
        $expected = [
            'new.tool.1.2.3'        => ['different/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3' => ['different/path', 'some/path']
        ];
        $this->assertData($expected, $clients);

        $clients = $this->clients();
        $clients->update([], 'different/path');
        $expected = [
            'new.tool.1.2.3'        => ['zzz/path'],
            'phpunit.phpunit.9.6.3' => ['some/path']
        ];
        $this->assertData($expected, $clients);
    }

    public function testUnusedTools_ReturnsListOfToolsWithoutLocations()
    {
        $clients = $this->clients([
            'new.tool.1.2.3'         => ['some/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3'  => ['some/path'],
            'polymorphine.dev.0.6.0' => ['some/path'],
            'zzold.tool.0.2.3'       => []
        ]);
        $this->assertSame(['zzold.tool.0.2.3'], $clients->unusedTools());

        $clients->update(['new.tool.1.2.3', 'polymorphine.dev.0.6.0'], 'some/path');
        $expected = [
            'new.tool.1.2.3'         => ['some/path', 'zzz/path'],
            'phpunit.phpunit.9.6.3'  => [],
            'polymorphine.dev.0.6.0' => ['some/path'],
            'zzold.tool.0.2.3'       => []
        ];
        $this->assertSame(['phpunit.phpunit.9.6.3', 'zzold.tool.0.2.3'], $clients->unusedTools());
        $this->assertData($expected, $clients);
    }

    public function testRemovingTools()
    {
        $clients = $this->clients([
            'new.tool.1.2.3'         => ['not/existing/path'],
            'phpunit.phpunit.9.6.3'  => ['not/existing/path', 'some/path'],
            'polymorphine.dev.0.6.0' => [],
            'zzold.tool.0.2.3'       => []
        ], ['not/existing/path']);
        $clients->remove('new.tool.1.2.3');
        $clients->remove('no.entry.2.54.3-dev');
        $clients->remove('polymorphine.dev.0.6.0');
        $expected = [
            'phpunit.phpunit.9.6.3' => ['not/existing/path', 'some/path'],
            'zzold.tool.0.2.3'      => []
        ];
        $this->assertData($expected, $clients);
    }

    private function assertData(array $expected, ToolClients &$toolClients): void
    {
        $toolClients = null;
        $this->assertSame($expected, Data::$installations);
    }

    private function clients(?array $installations = null): ToolClients
    {
        return new ToolClients(new Data($installations));
    }
}
