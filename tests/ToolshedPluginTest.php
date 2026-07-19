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
use Composer\Plugin;
use Symfony\Component\Console\Output\NullOutput;


class ToolshedPluginTest extends TestCase
{
    public function testSubscribedEvents_ForNotActivatedPlugin_ReturnsEmptyArray()
    {
        $this->assertSame([], ToolshedPlugin::getSubscribedEvents());
    }

    public function testSubscribedEvents_ForActivatedPlugin_ReturnsEventHandlerMethods()
    {
        $eventHandlers = [
            Plugin\PluginEvents::COMMAND         => 'verifyInstallCommand',
            Plugin\PluginEvents::PRE_POOL_CREATE => 'manageSharedTools'
        ];

        $plugin = new ToolshedPlugin();
        $plugin->activate($this->composer(), new Doubles\FakeIO());
        $this->assertSame($eventHandlers, ToolshedPlugin::getSubscribedEvents());
    }

    public function testPluginMethods_OutputCorrespondingMessages()
    {
        $composer = $this->composer();
        $io       = new Doubles\FakeIO();
        $plugin   = new ToolshedPlugin();

        $plugin->activate($composer, $io);
        $this->assertSame([], $io->messages);

        $plugin->manageSharedTools();
        $this->assertSame(['Activating'], $io->messages);

        $plugin->deactivate($composer, $io);
        $this->assertSame(['Activating', 'Deactivating'], $io->messages);

        $plugin->uninstall($composer, $io);
        $this->assertSame(['Activating', 'Deactivating', 'Removing'], $io->messages);
    }

    public function testPackageWithoutSharedToolsListed_IsNotActivated()
    {
        $inactivePackages = [
            'no shared-tools section' => $this->composer(null),
            'no shared-tools listed'  => $this->composer([])
        ];

        $io     = new Doubles\FakeIO();
        $plugin = new ToolshedPlugin();
        foreach ($inactivePackages as $case => $package) {
            $plugin->activate($package, $io);
            $this->assertEmpty(ToolshedPlugin::getSubscribedEvents(), 'Failed for ' . $case);
        }
    }

    public function testManageSharedTools_ForInactivePlugin_WillExitWithoutMessage()
    {
        $composer = $this->composer([]);
        $io       = new Doubles\FakeIO();
        $plugin   = new ToolshedPlugin();
        $plugin->activate($composer, $io);
        $plugin->manageSharedTools();
        $this->assertSame([], $io->messages);
    }

    /** @dataProvider nonInstallCommands */
    public function testForNonInstallCommands_PluginIsNotActivated(string $command, bool $noDev)
    {
        $plugin = new ToolshedPlugin();
        $io     = new Doubles\FakeIO();
        $plugin->activate($this->composer(), $io);

        $command = new Plugin\CommandEvent('not-install', $command, new Doubles\FakeInput($noDev), new NullOutput());
        $plugin->verifyInstallCommand($command);

        $plugin->manageSharedTools();
        $this->assertSame([], $io->messages);
    }

    /** @dataProvider installCommands */
    public function testForInstallCommands_PluginIsActivated(string $command)
    {
        $plugin = new ToolshedPlugin();
        $io     = new Doubles\FakeIO();
        $plugin->activate($this->composer(), $io);

        $command = new Plugin\CommandEvent('not-install', $command, new Doubles\FakeInput(), new NullOutput());
        $plugin->verifyInstallCommand($command);

        $plugin->manageSharedTools();
        $this->assertSame(['Activating'], $io->messages);
    }

    public function nonInstallCommands(): array
    {
        return [
            ['non-install', false],
            ['install', true],
            ['update', true]
        ];
    }

    public function installCommands(): array
    {
        return [['install'], ['update']];
    }

    private function composer(?array $sharedTools = ['phpunit/phpunit']): Composer
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
