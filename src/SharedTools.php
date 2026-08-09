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

use Shudd3r\Toolshed\Sync\UsageRegistry;
use Composer\IO\IOInterface;


class SharedTools
{
    private Tools         $tools;
    private UsageRegistry $registry;
    private IOInterface   $io;

    public function __construct(Tools $tools, UsageRegistry $registry, IOInterface $io)
    {
        $this->tools    = $tools;
        $this->registry = $registry;
        $this->io       = $io;
    }

    public function update(RequestedTools $requestedTools): void
    {
        foreach ($requestedTools->toolIdentifiers() as $requestedTool) {
            $message = '<info> - Updating tool %s</info>';
            $this->io->write(sprintf($message, $requestedTool->packageName()));
            $tool = $this->tools->install($requestedTool);
            $requestedTools->update($tool->identifier());
        }

        $this->registry->update($requestedTools);

        foreach ($this->registry->unusedTools() as $unusedTool) {
            $message = '<info> - Removing unused tool %s</info>';
            $this->io->write(sprintf($message, $unusedTool->packageName()));
            $this->tools->remove($unusedTool);
            $this->registry->remove($unusedTool);
        }
    }
}
