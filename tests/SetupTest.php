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
use Shudd3r\Toolshed\Setup;
use Shudd3r\Toolshed\SharedTools;
use Composer\Composer;
use Composer\Config;


class SetupTest extends TestCase
{
    public function testLocalInstance()
    {
        $setup    = new Setup\LocalSetup();
        $io       = new Doubles\FakeIO();
        $composer = new Composer();
        $testDir  = new Fixtures\TempFiles(static::class);
        $composer->setConfig($config = new Config());
        $config->merge(['config' => ['home' => $testDir->directory()]]);

        $this->assertInstanceOf(SharedTools::class, $setup->sharedTools($composer, $io));
    }

    public function testClientBinDirectory()
    {
        $setup    = new Setup\LocalSetup();
        $composer = new Composer();
        $testDir  = new Fixtures\TempFiles(static::class);
        $composer->setConfig($config = new Config());
        $config->merge(['config' => ['bin-dir' => $testDir->pathname('client/project/vendor/bin')]]);

        $this->assertTrue($setup->clientBinDirectory($composer)->exists());
    }
}
