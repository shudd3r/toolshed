<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Doubles;

use Shudd3r\Toolshed\Tools;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Tools\Tool;


class FakeTools extends Tools
{
    public array $installed = [];
    public array $removed   = [];

    private VirtualDirectory $directory;

    public function __construct()
    {
        $this->directory = VirtualDirectory::root('vfs://root/shared-tools');
        parent::__construct(new FakeProcessExecutor(), $this->directory);
    }

    public function install(Identifier $tool): Tool
    {
        $this->installed[] = $tool;
        return new Tool($tool, $this->directory);
    }

    public function remove(Identifier $tool): void
    {
        $this->removed[] = $tool;
    }
}
