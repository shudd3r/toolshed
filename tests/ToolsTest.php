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
    private static FakeProcessExecutor $processor;
    private static VirtualDirectory    $toolsDir;

    public static function setUpBeforeClass(): void
    {
        self::$processor = new FakeProcessExecutor();
        self::$toolsDir  = VirtualDirectory::root('vfs://root');
    }

    /** @dataProvider resolvedVersions */
    public function testInstall_ReturnsResolvedTool(Tools\Identifier $id, string $outputVersion, string $resolvedVersion)
    {
        self::$processor->presetOutput(0, $id->packageName(), $outputVersion);
        $this->assertInstalled($id, $resolvedVersion, $this->tools()->install($id));
    }

    public function testErrorOnToolInstall_ThrowsException()
    {
        self::$processor->presetOutput(2, 'package/name', '8.4.2');
        $id = Tools\Identifier::fromStrings('test-tools/package', '12.6.2');
        $this->expectException(Tools\Exception\ToolSetupException::class);
        $this->tools()->install($id);
    }

    public function testOutputWithoutVersionPhrase_ThrowsException()
    {
        self::$processor->presetOutput(0, 'different/name', '8.4.2');
        $id = Tools\Identifier::fromStrings('test-tools/package', '^4.1');
        $this->expectException(Tools\Exception\ToolSetupException::class);
        $this->tools()->install($id);
    }

    public static function resolvedVersions(): array
    {
        return [
            'exact version'       => [Tools\Identifier::fromStrings('test-tools/package', '12.6.2'), '12.6.2', '12.6.2'],
            'resolve to std'      => [Tools\Identifier::fromStrings('test-tools/package', '^3.0'), '3.5.0', '3.5.0'],
            'resolve to prefixed' => [Tools\Identifier::fromStrings('package/foo-bar', '^9.6'), 'v9.8.11', 'v9.8.11'],
            'resolve to branch'   => [Tools\Identifier::fromStrings('vendor/package', '@dev'), 'dev-master fbd47f7', 'dev-master']
        ];
    }

    private function assertInstalled(Tools\Identifier $initial, string $version, Tools\Tool $installed): void
    {
        $commands = [];
        if ($initial->isResolved()) {
            $this->assertSame($expectedId = $initial, $installed->identifier());
        } else {
            $expectedId = $initial->resolvedTo(Tools\Identifier::parseConstraint($version));
            $this->assertEquals($expectedId, $installed->identifier());

            $resolveDir = self::$toolsDir->subdirectory('shared-tools/' . $initial->installName());
            $this->assertFalse($resolveDir->exists());

            $commands[(string) $resolveDir] = 'composer update --dry-run --no-install --no-progress 2>&1';
        }

        $expectedToolDir = self::$toolsDir->subdirectory('shared-tools/' . $expectedId->installName());
        $this->assertEquals($expectedToolDir->subdirectory('vendor/bin'), $installed->binaries());
        $this->assertTrue($expectedToolDir->file('composer.json')->exists());

        $commands[(string) $expectedToolDir] = 'composer update --no-progress 2>&1';
        $this->assertSame($commands, self::$processor->commands);
    }

    private function tools(): Tools
    {
        self::$toolsDir->subdirectory('shared-tools')->remove();
        self::$processor->commands = [];
        return new Tools(self::$processor, self::$toolsDir->subdirectory('shared-tools'));
    }
}
