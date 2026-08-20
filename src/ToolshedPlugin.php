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
use Composer\Package;


class ToolshedPlugin implements Plugin\PluginInterface, EventDispatcher\EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [Plugin\PluginEvents::COMMAND => 'execute'];
    }

    private Setup       $setup;
    private Composer    $composer;
    private IOInterface $io;

    public function __construct(?Setup $setup = null)
    {
        $this->setup = $setup ?? new Setup\LocalSetup();
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function execute(Plugin\CommandEvent $command): void
    {
        if (!isset($this->io)) { return; }

        $isInstall = in_array($command->getCommandName(), ['install', 'update'], true);
        $isActive  = $isInstall && !$command->getInput()->getOption('no-dev');
        if (!$isActive) { return; }

        $this->io->write('Activating');
        $requestedTools = $this->setup->requestedTools($this->composer, $this->io);
        $this->filterDevRequires($requestedTools, $this->composer->getPackage());
        $this->setup->sharedTools($this->composer, $this->io)->update($requestedTools);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $io->write('Deactivating');
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $io->write('Removing');
    }

    private function filterDevRequires(RequestedTools $requestedTools, Package\RootPackageInterface $package): void
    {
        $devRequires = $package->getDevRequires();
        foreach ($requestedTools->toolIdentifiers() as $tool) {
            unset($devRequires[$tool->packageName()]);
        }
        $package->setDevRequires($devRequires);
    }
}
