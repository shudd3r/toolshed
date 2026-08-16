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
use Composer\Plugin;
use Symfony\Component\Console\Output;


class ToolshedPluginTest extends TestCase
{
    /** @dataProvider nonInstallCommands */
    public function testForNonInstallCommands_PluginIsNotActivated(string $command, bool $noDev)
    {
        $plugin = new ToolshedPlugin();
        $plugin->activate(new Composer(), $io = new Doubles\FakeIO());

        $plugin->setCommand($this->command($command, $noDev));
        $plugin->manageSharedTools();

        $this->assertSame([], $io->messages);
    }

    /** @dataProvider installCommands */
    public function testForInstallCommands_PluginIsActivated(string $command)
    {
        $plugin = new ToolshedPlugin();
        $plugin->activate(new Composer(), $io = new Doubles\FakeIO());

        $plugin->setCommand($this->command($command));
        $plugin->manageSharedTools();

        $this->assertSame(['Activating'], $io->messages);
    }

    public function testSubscribedEvents_MatchPluginMethods()
    {
        $plugin = new ToolshedPlugin();
        $events = ToolshedPlugin::getSubscribedEvents();
        foreach ($events as $method) {
            $this->assertTrue(method_exists($plugin, $method));
            $this->assertTrue(is_callable([$plugin, $method]));
        }

        $eventNames = [Plugin\PluginEvents::COMMAND, Plugin\PluginEvents::PRE_POOL_CREATE];
        $this->assertSame($eventNames, array_keys($events));
    }

    public function testPluginMethods_OutputCorrespondingMessages()
    {
        $composer = new Composer();
        $io       = new Doubles\FakeIO();
        $plugin   = new ToolshedPlugin();

        $plugin->deactivate($composer, $io);
        $this->assertSame(['Deactivating'], $io->messages);

        $plugin->uninstall($composer, $io);
        $this->assertSame(['Deactivating', 'Removing'], $io->messages);
    }

    public static function nonInstallCommands(): array
    {
        return [['non-install', false], ['install', true], ['update', true]];
    }

    public static function installCommands(): array
    {
        return [['install'], ['update']];
    }

    private function command(string $command, bool $noDev = false): Plugin\CommandEvent
    {
        return new Plugin\CommandEvent(Plugin\PluginEvents::COMMAND, $command, new Doubles\FakeInput($noDev), new Output\NullOutput());
    }
}
