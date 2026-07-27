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

use Shudd3r\Toolshed\Sync\RefData;


class FakeRefData implements RefData
{
    public static array $toolRefs = [];

    public function __construct(?array $toolRefs = null)
    {
        self::$toolRefs = $toolRefs ?? self::$toolRefs;
    }

    public function toolRefs(): array
    {
        return self::$toolRefs;
    }

    public function save(array $toolRefs): void
    {
        self::$toolRefs = $toolRefs;
    }
}
