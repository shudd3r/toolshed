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
use Composer\Config;
use Composer\Package;
use Composer\Semver;
use Symfony\Component;


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
        $plugin = new ToolshedPlugin(new Doubles\FakeSetup());
        $plugin->activate($composer = $this->composer(), $io = new Doubles\FakeIO());

        $plugin->setCommand($this->command($command));
        $plugin->manageSharedTools();

        $this->assertSame(['Activating'], $io->messages);
        $this->assertSame([
            '[SKIPPED] Shared dev tool `not/dev` not found in require-dev composer.json',
            '  - Updating tool <info>foo/bar</info> ...FAILED',
            '  - Updating tool <info>bar/baz</info> (<comment>1.8.0</comment>)'
        ], $io->errors);
        $this->assertSame(['not/tool'], array_keys($composer->getPackage()->getDevRequires()));
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
        $input  = new Doubles\FakeInput($noDev);
        $output = new Component\Console\Output\NullOutput();
        return new Plugin\CommandEvent(Plugin\PluginEvents::COMMAND, $command, $input, $output);
    }

    private function composer(): Composer
    {
        $composer = new Composer();

        $composer->setConfig($config = new Config());
        $config->setBaseDir('/client/project');
        $config->merge(['config' => ['home' => '/composer/global']]);

        $composer->setPackage($package = new Package\RootPackage('test/package', '1.0.0', '1.0.0'));
        $package->setDevRequires([
            'not/tool' => new Package\Link('test/package', 'not/tool', $this->constraint('4.*')),
            'foo/bar'  => new Package\Link('test/package', 'foo/bar', $this->constraint('^2.6')),
            'bar/baz'  => new Package\Link('test/package', 'bar/baz', $this->constraint('1.8.0'))
        ]);
        $package->setExtra(['shared-tools' => ['foo/bar', 'bar/baz', 'not/dev']]);

        return $composer;
    }

    private function constraint(string $version): Semver\Constraint\ConstraintInterface
    {
        return (new Semver\VersionParser())->parseConstraints($version);
    }
}
