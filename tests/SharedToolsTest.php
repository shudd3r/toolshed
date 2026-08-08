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
use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\SharedTools;
use Shudd3r\Toolshed\Sync\UsageRegistry;
use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Filesystem\Virtual;
use Shudd3r\Toolshed\Tests\Doubles\FakeRefData;
use Shudd3r\Toolshed\Tests\Doubles\FakeTools;


class SharedToolsTest extends TestCase
{
    public function testToolsRequestChangesState()
    {
        $sharedTools = $this->sharedTools($tools, $refData);
        $refData->save([
            'foo.tool.1.2.2'  => ['/foo/client', '/another/client'],
            'bar.tool.1.2.4'  => ['/another/client'],
            'some.tool.2.3.4' => ['/foo/client']
        ]);
        $requestedTools = [
            Identifier::fromInstallName('foo.tool.1.2.3'),
            Identifier::fromInstallName('bar.tool.1.2.4')
        ];
        $request = new RequestedTools(Virtual\VirtualDirectory::root('/foo/client', '/'), $requestedTools);

        $sharedTools->update($request);

        $this->assertSame($requestedTools, $tools->installed);
        $this->assertEquals([Identifier::fromInstallName('some.tool.2.3.4')], $tools->removed);

        unset($sharedTools);
        $this->assertEquals([
            'foo.tool.1.2.2' => ['/another/client'],
            'foo.tool.1.2.3' => ['/foo/client'],
            'bar.tool.1.2.4' => ['/another/client', '/foo/client']
        ], $refData->toolRefs());
    }

    private function sharedTools(?FakeTools &$tools = null, ?FakeRefData &$refData = null): SharedTools
    {
        return new SharedTools($tools ??= new FakeTools(), new UsageRegistry($refData ??= new FakeRefData()));
    }
}
