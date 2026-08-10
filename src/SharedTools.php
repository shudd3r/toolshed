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
use Shudd3r\Toolshed\Tools\Exception\ToolSetupException;
use Shudd3r\Toolshed\Tools\Identifier;


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
            $message = '  - Updating tool <info>%s</info>';
            $this->io->writeError(sprintf($message, $requestedTool->packageName()), false);
            try {
                $tool = $this->tools->install($requestedTool);
                $requestedTools->update($tool->identifier());
                $this->io->writeError(sprintf(' (<comment>%s</comment>)', $tool->identifier()->version()));
            } catch (ToolSetupException $ex) {
                $this->io->writeError(' ...FAILED');
                $this->io->writeError($ex->getMessage(), true, IOInterface::VERBOSE);
                $requestedTools->update(Identifier::fromStrings($requestedTool->packageName()));
            }
        }

        $this->registry->update($requestedTools);

        foreach ($this->registry->unusedTools() as $unusedTool) {
            $message = '  - Removing unused tool <info>%s</info> (<comment>%s</comment>)';
            $this->io->writeError(sprintf($message, $unusedTool->packageName(), $unusedTool->version()));
            $this->tools->remove($unusedTool);
            $this->registry->remove($unusedTool);
        }
    }
}
