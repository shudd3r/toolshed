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

use Shudd3r\Toolshed\Tools\Identifier;
use Shudd3r\Toolshed\Tools\Tool;


interface Tools
{
    public function install(Identifier $id): Tool;

    public function remove(Identifier $id): void;
}
