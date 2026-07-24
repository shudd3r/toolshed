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
use Shudd3r\Toolshed\Tools\Tool\Identifier;


class FakeTools implements Tools
{
    private FakeTool $tool;

    public function __construct(FakeTool $tool)
    {
        $this->tool = $tool;
    }

    public function tool(Identifier $id): FakeTool
    {
        return $this->tool->identifier() === $id ? $this->tool : new FakeTool($id);
    }
}
