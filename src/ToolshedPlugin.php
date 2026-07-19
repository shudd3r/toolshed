<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed;

use Composer\Plugin;
use Composer\EventDispatcher;
use Composer\Composer;
use Composer\IO\IOInterface;


class ToolshedPlugin implements Plugin\PluginInterface, EventDispatcher\EventSubscriberInterface
{
    private static bool $isPluginActive = false;

    public static function getSubscribedEvents(): array
    {
        if (!self::$isPluginActive) { return []; }

        return [
            Plugin\PluginEvents::COMMAND         => 'verifyInstallCommand',
            Plugin\PluginEvents::PRE_POOL_CREATE => 'manageSharedTools'
        ];
    }

    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $sharedTools = $composer->getPackage()->getExtra()['shared-tools'] ?? [];
        self::$isPluginActive = !empty($sharedTools);
        if (!self::$isPluginActive) { return; }

        $this->io = $io;
    }

    public function verifyInstallCommand(Plugin\CommandEvent $event): void
    {
        $installCommand = in_array($event->getCommandName(), ['install', 'update'], true);
        self::$isPluginActive = $installCommand && !$event->getInput()->getOption('no-dev');
    }

    public function manageSharedTools(): void
    {
        if (!self::$isPluginActive) { return; }
        $this->io->write('Activating');
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $io->write('Deactivating');
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $io->write('Removing');
    }
}
