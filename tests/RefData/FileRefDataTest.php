<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\RefData;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\RefData\FileRefData;
use Shudd3r\Toolshed\Tests\Fixtures;


class FileRefDataTest extends TestCase
{
    private static Fixtures\TempFiles $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new Fixtures\TempFiles(basename(static::class));
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
    }

    public function testToolRefsMethod_WhenFileCannotBeRead_ReturnsEmptyArray()
    {
        $data = $this->data();
        $this->assertEmpty($data->toolRefs());

        self::$temp->directory('shared-files');
        $this->assertEmpty($data->toolRefs());

        self::$temp->file('shared-files/install-locations.json', '--- not json structure ---');
        $this->assertEmpty($data->toolRefs());
    }

    public function testToolRefsMethod_ForValidDataFile_ReturnsDecodedJsonStructure()
    {
        $refs = ['foo.bar.1.2.3' => [self::$temp->directory('some/directory')]];
        $data = $this->data($refs);
        $this->assertSame($refs, $data->toolRefs());
    }

    public function testSaveMethod_CreatesValidMetaData()
    {
        $data = $this->data();
        $refs = ['foo.bar.1.2.3' => [self::$temp->directory('some/directory')]];
        $data->save($refs);
        $this->assertSame($refs, $data->toolRefs());
    }

    public function testNotExistingLocations_AreFilteredOnRead()
    {
        $locations = [
            self::$temp->directory('new/directory'),
            self::$temp->file('not/directory'),
            self::$temp->pathname('not/existing/directory')
        ];

        $data = $this->data(['foo.bar.1.2.3' => $locations]);

        $expected = ['foo.bar.1.2.3' => [self::$temp->directory('new/directory')]];
        $this->assertSame($expected, $data->toolRefs());
    }

    private function data(?array $fileContents = null): FileRefData
    {
        if ($fileContents !== null) {
            $contents = json_encode($fileContents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            self::$temp->file('shared-tools/install-locations.json', $contents);
        }
        return new FileRefData(self::$temp->pathname('shared-tools'));
    }
}
