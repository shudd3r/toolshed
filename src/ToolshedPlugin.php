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
    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $io->write('Activating');
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
