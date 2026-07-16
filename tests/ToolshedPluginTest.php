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
use Shudd3r\Toolshed\ToolshedPlugin;
use Composer\Composer;
use Composer\Package;


class ToolshedPluginTest extends TestCase
{
    public function testSubscribedEvents_ReturnsEmptyArray()
    {
        $this->assertSame([], ToolshedPlugin::getSubscribedEvents());
    }

    public function testPluginMethods_OutputCorrespondingMessages()
    {
        $composer = $this->composer(['phpunit/phpunit']);
        $io       = new Doubles\FakeIO();
        $plugin   = new ToolshedPlugin();

        $plugin->activate($composer, $io);
        $this->assertSame(['Activating'], $io->messages);

        $plugin->deactivate($composer, $io);
        $this->assertSame(['Activating', 'Deactivating'], $io->messages);

        $plugin->uninstall($composer, $io);
        $this->assertSame(['Activating', 'Deactivating', 'Removing'], $io->messages);
    }

    public function testPackageWithoutSharedToolsListed_IsNotActivated()
    {
        $inactivePackages = [
            'no shared-tools section' => $this->composer(),
            'no shared-tools listed'  => $this->composer([])
        ];

        $io     = new Doubles\FakeIO();
        $plugin = new ToolshedPlugin();
        foreach ($inactivePackages as $case => $package) {
            $plugin->activate($package, $io);
            $this->assertEmpty($io->messages, 'Failed for ' . $case);
        }
    }

    private function composer(?array $sharedTools = null): Composer
    {
        $package  = new Package\RootPackage('test/package', '1.0.0', '1.0.0');
        $composer = new Composer();
        $composer->setPackage($package);

        if ($sharedTools !== null) {
            $package->setExtra(['shared-tools' => $sharedTools]);
        }

        return $composer;
    }
}
