<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Tools\Tool;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Tools\Tool\Composer;
use Shudd3r\Toolshed\Tests\Doubles\FakeIO;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class ComposerTest extends TestCase
{
    private static TempFiles $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new TempFiles(basename(static::class));
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
    }

    public function testExecuteCommand_ReturnsOutput()
    {
        $composer = new Composer(new FakeIO());
        $result   = $composer->execute('--version', self::$temp->pathname(''));
        $this->assertStringContainsString('Composer version', $result);
        $this->assertStringContainsString('PHP version', $result);
    }

    public function testInvalidExecuteCommand_DisplaysError()
    {
        self::$temp->file('composer.json', '{}');
        $output   = new FakeIO();
        $composer = new Composer($output);
        $result   = $composer->execute('not-a-command', self::$temp->pathname(''));
        $this->assertEmpty($result);
        $this->assertStringContainsString('Command "not-a-command" is not defined', $output->messages[0]);
    }
}
