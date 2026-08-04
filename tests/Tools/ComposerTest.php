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
use Shudd3r\Toolshed\Tools\Composer;
use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Tests\Doubles\FakeProcessExecutor;


class ComposerTest extends TestCase
{
    public function testInstall_ForInvalidToolDirectory_ReturnsErrorCode()
    {
        $composer = $this->composer($processor, $toolsDir);
        $toolsDir->subdirectory('some.package.9.7.11')->create();

        $tool = Identifier::fromStrings('some/package', '^9.6');
        $this->assertEquals(1, $composer->install($tool));
        $expectedOutput = 'Tool directory `some.package.unresolved` does not exist';
        $this->assertEquals($expectedOutput, $composer->output());

        $tool = Identifier::fromStrings('some/package', '9.7.11');
        $this->assertEquals(1, $composer->install($tool));
        $expectedOutput = 'No composer.json in `some.package.9.7.11` tool directory';
        $this->assertEquals($expectedOutput, $composer->output());
    }

    public function testInstall_ForUnresolvedToolVersion()
    {
        $composer = $this->composer($processor, $toolsDir);
        $toolsDir->file('test.package.unresolved/composer.json')->write('{}');

        $tool = Identifier::fromStrings('test/package', '^9.6');
        $this->assertEquals(0, $composer->install($tool));
        $expectedOutput = '- Locking test/package (9.10.11)';
        $this->assertStringContainsString($expectedOutput, $composer->output());
        $expectedCommand = 'composer update --dry-run --no-install --no-progress 2>&1';
        $this->assertSame($expectedCommand, $processor->command);
    }

    public function testInstall_ForResolvedToolVersion()
    {
        $composer = $this->composer($processor, $toolsDir);
        $toolsDir->file('test.package.9.7.11/composer.json')->write('{}');

        $tool = Identifier::fromStrings('test/package', '9.7.11');
        $this->assertEquals(0, $composer->install($tool));
        $expectedOutput = '- Locking test/package (9.10.11)';
        $this->assertStringContainsString($expectedOutput, $composer->output());
        $expectedCommand = 'composer update --no-progress 2>&1';
        $this->assertSame($expectedCommand, $processor->command);
    }

    private function composer(?FakeProcessExecutor &$processor = null, ?VirtualDirectory &$directory = null): Composer
    {
        $processor ??= new FakeProcessExecutor();
        $directory ??= VirtualDirectory::root('vfs://root', '/');
        return new Composer($processor, $directory);
    }
}
