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
use Shudd3r\Toolshed\Tools;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Tests\Doubles\FakeProcessExecutor;


class ToolsTest extends TestCase
{
    public function testInstallingKnownToolVersion()
    {
        $tools = $this->tools($processor, $toolsDir);
        $id    = Tools\Identifier::fromStrings('test-tools/package', '12.6.2');
        $processor->presetOutput(0, $id->packageName(), '12.6.2');

        $installed = $tools->install($id);

        $this->assertSame($id, $installed->identifier());
        $toolDir = $toolsDir->subdirectory($id->installName());
        $this->assertEquals($toolDir->subdirectory('vendor/bin'), $installed->binaries());
        $this->assertTrue($toolDir->file('composer.json')->exists());
        $this->assertSame([
            (string) $toolDir => 'composer update --no-progress 2>&1'
        ], $processor->commands);
    }

    /** @dataProvider unresolvedVersions */
    public function testInstallingRangeToolVersion(Tools\Identifier $id, string $outputVersion, string $resolveTo)
    {
        $tools = $this->tools($processor, $toolsDir);
        $processor->presetOutput(0, $id->packageName(), $outputVersion);

        $installed = $tools->install($id);

        $expectedId = $id->resolvedTo(Tools\Identifier::parseConstraint($resolveTo));
        $this->assertEquals($expectedId, $installed->identifier());

        $resolveDir = $toolsDir->subdirectory($id->installName());
        $this->assertFalse($resolveDir->exists());

        $toolDir = $toolsDir->subdirectory($expectedId->installName());
        $this->assertEquals($toolDir->subdirectory('vendor/bin'), $installed->binaries());
        $this->assertTrue($toolDir->file('composer.json')->exists());
        $this->assertSame([
            (string) $resolveDir => 'composer update --dry-run --no-install --no-progress 2>&1',
            (string) $toolDir    => 'composer update --no-progress 2>&1'
        ], $processor->commands);
    }

    public function testErrorOnToolInstall_ThrowsException()
    {
        $tools = $this->tools($processor, $toolsDir);
        $processor->presetOutput(2, 'package/name', '8.4.2');
        $id = Tools\Identifier::fromStrings('test-tools/package', '12.6.2');
        $this->expectException(Tools\Exception\ToolSetupException::class);
        $tools->install($id);
    }

    public function testOutputWithoutVersionPhrase_ThrowsException()
    {
        $tools = $this->tools($processor, $toolsDir);
        $processor->presetOutput(0, 'different/name', '8.4.2');
        $id = Tools\Identifier::fromStrings('test-tools/package', '^4.1');
        $this->expectException(Tools\Exception\ToolSetupException::class);
        $tools->install($id);
    }

    public static function unresolvedVersions(): array
    {
        return [
            'resolve to std'      => [Tools\Identifier::fromStrings('test-tools/package', '^3.0'), '3.5.0', '3.5.0'],
            'resolve to prefixed' => [Tools\Identifier::fromStrings('package/foo-bar', '^9.6'), 'v9.8.11', 'v9.8.11'],
            'resolve to branch'   => [Tools\Identifier::fromStrings('vendor/package', '@dev'), 'dev-master fbd47f7', 'dev-master']
        ];
    }

    private function tools(?FakeProcessExecutor &$processor = null, ?VirtualDirectory &$toolsDir = null): Tools
    {
        $toolsDir ??= VirtualDirectory::root('vfs://root')->subdirectory('shared-tools');
        return new Tools($processor ??= new FakeProcessExecutor(), $toolsDir);
    }
}
