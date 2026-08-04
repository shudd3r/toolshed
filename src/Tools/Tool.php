<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tools;

use Shudd3r\Toolshed\Filesystem\Directory;


interface Tool
{
    public function identifier(): Identifier;

    public function binaries(): Directory;
}
