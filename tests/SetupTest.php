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
use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\Filesystem;
use Shudd3r\Toolshed\Tools;
use Shudd3r\Toolshed\Sync;
use Composer\Composer;
use Composer\Package;
use Composer\Config;
use Composer\Semver;
use Composer\Util;


class SetupTest extends TestCase
{
    public function testSharedToolsInstantiation()
    {
        $setup    = new Setup\LocalSetup();
        $io       = new Doubles\FakeIO();
        $composer = new Composer();
        $testDir  = new Fixtures\TempFiles(static::class);
        $composer->setConfig($config = new Config());
        $config->merge(['config' => ['home' => $testDir->directory()]]);

        $sharedTools = $setup->sharedTools($composer, $io);

        $toolsDir = Filesystem\Local\LocalDirectory::root($testDir->directory())->subdirectory('shared-tools');
        $this->assertEquals(new SharedTools(
            new Tools(new Util\ProcessExecutor(), $toolsDir),
            new Sync\UsageRegistry(new Sync\RefData\LocalRefData($toolsDir->file('install-locations.json'))),
            $io
        ), $sharedTools);
    }

    public function testRequestedToolsBuilding()
    {
        $setup    = new Setup\LocalSetup();
        $composer = new Composer();
        $io       = new Doubles\FakeIO();
        $testDir  = new Fixtures\TempFiles(static::class);
        $composer->setConfig($config = new Config());
        $composer->setPackage($package = new Package\RootPackage('client/project', '1.0.0', '1.0.0'));
        $config->merge(['config' => ['bin-dir' => $testDir->pathname('client/project/vendor/bin')]]);
        $package->setExtra(['shared-tools' => ['foo/bar', 'bar/baz', 'unlisted/package']]);
        $package->setRequires(['php' => $this->link('php', '6.0')]);
        $package->setDevRequires([
            'foo/bar' => $this->link('foo/bar', '^12.4'),
            'bar/baz' => $this->link('bar/baz', '7.4.*')
        ]);

        $requestedTools = $setup->requestedTools($composer, $io);

        $clientDir = Filesystem\Local\LocalDirectory::root($testDir->pathname('client/project/vendor/bin'));
        $this->assertEquals(new RequestedTools($clientDir, [
            Tools\Identifier::fromStrings('foo/bar', '^12.4', '6.0'),
            Tools\Identifier::fromStrings('bar/baz', '7.4.*', '6.0')
        ]), $requestedTools);
        $error = '[SKIPPED] Shared dev tool `unlisted/package` not found in require-dev composer.json';
        $this->assertSame([$error], $io->errors);
    }

    private function link(string $name, string $version): Package\Link
    {
        return new Package\Link('shudd3r/toolshed', $name, $this->constraint($version));
    }

    private function constraint(string $version): Semver\Constraint\ConstraintInterface
    {
        return (new Semver\VersionParser())->parseConstraints($version);
    }
}
