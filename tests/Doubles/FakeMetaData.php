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

use Shudd3r\Toolshed\MetaData;


class FakeMetaData implements MetaData
{
    public static array $installations    = [];
    public static array $missingLocations = [];

    public function __construct(?array $installations = null, ?array $missingLocations = null)
    {
        self::$installations    = $installations ?? self::$installations;
        self::$missingLocations = $missingLocations ?? self::$missingLocations;
    }

    public function installations(): array
    {
        return self::$installations;
    }

    public function saveInstallations(array $installations): void
    {
        self::$installations = $installations;
    }

    public function locationExists(string $location): bool
    {
        return !in_array($location, self::$missingLocations, true);
    }
}
