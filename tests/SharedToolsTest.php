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
use Shudd3r\Toolshed\SharedTools;
use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\Sync\UsageRegistry;
use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory as Directory;
use Shudd3r\Toolshed\Tests\Doubles\FakeProcessExecutor as Processor;
use Shudd3r\Toolshed\Tests\Doubles\FakeRefData;
use Shudd3r\Toolshed\Tests\Doubles\FakeTools;
use Shudd3r\Toolshed\Tests\Doubles\FakeIO;


class SharedToolsTest extends TestCase
{
    private const LINK_FILE = <<<'LNK'
        #!/usr/bin/env php
        <?php
        $toolBinaries = getenv('COMPOSER_HOME') . '/shared-tools/foo.tool.2.0.0/vendor/bin/';
        return include $toolBinaries . basename(__FILE__) . '.php';
        LNK;

    public function testToolsRequestChangesState()
    {
        $sharedTools = $this->sharedTools($tools, $refData, $io);
        $request     = $this->request($clientBinDir, $requestedTools);

        $sharedTools->update($request);

        $this->assertSame($requestedTools, $tools->installed);
        $this->assertEquals([Identifier::fromInstallName('some.tool.2.3.4')], $tools->removed);
        $this->assertSame([
            '  - Updating tool <info>foo/tool</info> (<comment>1.2.3</comment>)',
            '  - Updating tool <info>bar/tool</info> (<comment>1.2.4</comment>)',
            '  - Removing unused tool <info>some/tool</info> (<comment>2.3.4</comment>)'
        ], $io->errors);

        $this->assertData([
            'foo.tool.1.2.2' => ['/another/client/vendor/bin'],
            'foo.tool.1.2.3' => ['/foo/client/vendor/bin'],
            'bar.tool.1.2.4' => ['/another/client/vendor/bin', '/foo/client/vendor/bin']
        ], $refData, $sharedTools);
    }

    public function testFailedToolInstallation()
    {
        $io          = FakeIO::verbose();
        $sharedTools = $this->sharedTools($tools, $refData, $io);
        $tools->throwExceptionFor($failed = Identifier::fromInstallName('foo.tool.1.2.3'));
        $requestedTools = [$failed, Identifier::fromInstallName('bar.tool.1.2.4')];
        $request        = $this->request($clientBinDir, $requestedTools);

        $sharedTools->update($request);

        $this->assertSame([$requestedTools[1]], $tools->installed);
        $this->assertEquals([Identifier::fromInstallName('some.tool.2.3.4')], $tools->removed);
        $this->assertSame([
            '  - Updating tool <info>foo/tool</info> ...FAILED',
            'This is exception message.',
            '  - Updating tool <info>bar/tool</info> (<comment>1.2.4</comment>)',
            '  - Removing unused tool <info>some/tool</info> (<comment>2.3.4</comment>)'
        ], $io->errors);

        $this->assertData([
            'foo.tool.1.2.2' => ['/another/client/vendor/bin'],
            'bar.tool.1.2.4' => ['/another/client/vendor/bin', '/foo/client/vendor/bin']
        ], $refData, $sharedTools);
    }

    public function testBinExecutables_AreLinked()
    {
        $sharedTools    = $this->sharedTools($tools, $refData, $io);
        $requestedTools = [Identifier::fromInstallName('foo.tool.2.0.0')];
        $clientBinDir   = $tools->root->subdirectory('client/vendor/bin');
        $request        = $this->request($clientBinDir, $requestedTools);
        $toolBinaries   = $tools->root->subdirectory('shared-tools/foo.tool.2.0.0/vendor/bin');

        $sharedTools->update($request);

        $phpContents = $toolBinaries->file('foo.php')->contents();
        $this->assertSame(substr(Processor::EXEC_FILE, 19), $phpContents);
        $this->assertTrue(strpos($phpContents, '<?php') === 0);
        $this->assertSame(self::LINK_FILE, $toolBinaries->file('foo')->contents());
        $this->assertSame(self::LINK_FILE, $clientBinDir->file('foo')->contents());
        $this->assertFalse($clientBinDir->file('foo.bat')->exists());
    }

    public function testBinExecutables_OnSubsequentInstalls_RemainUnchanged()
    {
        $tools          = FakeTools::forWindows();
        $sharedTools    = $this->sharedTools($tools, $refData, $io);
        $requestedTools = [Identifier::fromInstallName('foo.tool.2.0.0')];
        $clientBinDir   = $tools->root->subdirectory('client/vendor/bin');
        $request        = $this->request($clientBinDir, $requestedTools);
        $toolBinaries   = $tools->root->subdirectory('shared-tools/foo.tool.2.0.0/vendor/bin');

        $sharedTools->update($request);
        $sharedTools->update($request);

        $phpContents = $toolBinaries->file('foo.php')->contents();
        $this->assertSame(substr(Processor::EXEC_FILE, 19), $phpContents);
        $this->assertTrue(strpos($phpContents, '<?php') === 0);
        $this->assertSame(self::LINK_FILE, $toolBinaries->file('foo')->contents());
        $this->assertSame(self::LINK_FILE, $clientBinDir->file('foo')->contents());
        $this->assertSame(Processor::BAT_FILE, $clientBinDir->file('foo.bat')->contents());
    }

    private function assertData(array $expectedData, FakeRefData $refData, SharedTools &$tools): void
    {
        $tools = null;
        $this->assertEquals($expectedData, $refData->toolRefs());
    }

    private function sharedTools(?FakeTools &$tools, ?FakeRefData &$refData, ?FakeIO &$io): SharedTools
    {
        $tools ??= new FakeTools();
        $refData ??= new FakeRefData();
        $io ??= new FakeIO();

        $refData->save([
            'foo.tool.1.2.2'  => ['/foo/client/vendor/bin', '/another/client/vendor/bin'],
            'bar.tool.1.2.4'  => ['/another/client/vendor/bin'],
            'some.tool.2.3.4' => ['/foo/client/vendor/bin']
        ]);

        return new SharedTools($tools, new UsageRegistry($refData), $io);
    }

    private function request(?Directory &$clientBinDir, ?array &$tools): RequestedTools
    {
        $clientBinDir ??= Directory::root('/foo/client/vendor/bin', '/');
        $tools ??= [Identifier::fromInstallName('foo.tool.1.2.3'), Identifier::fromInstallName('bar.tool.1.2.4')];
        return new RequestedTools($clientBinDir, $tools);
    }
}
