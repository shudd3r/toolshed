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


class ToolshedPluginTest extends TestCase
{
    public function testSubscribedEvents_ReturnsEmptyArray()
    {
        $this->assertSame([], ToolshedPlugin::getSubscribedEvents());
    }

    public function testPluginMethods_OutputCorrespondingMessages()
    {
        $plugin = new ToolshedPlugin();
        $plugin->activate(new Composer(), $io = new Doubles\FakeIO());
        $this->assertSame(['Activating'], $io->messages);
        $plugin->deactivate(new Composer(), $io);
        $this->assertSame(['Activating', 'Deactivating'], $io->messages);
        $plugin->uninstall(new Composer(), $io);
        $this->assertSame(['Activating', 'Deactivating', 'Removing'], $io->messages);
    }
}
