<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\MetaData;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\MetaData\FileMetaData;
use Shudd3r\Toolshed\Tests\Fixtures;


class FileMetaDataTest extends TestCase
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

    public function testInstallations_WhenMetaDataCannotBeRead_ReturnsEmptyArray()
    {
        $data = $this->data();
        $this->assertEmpty($data->installations());

        self::$temp->directory('shared-files');
        $this->assertEmpty($data->installations());

        self::$temp->file('shared-files/install-locations.json', '--- not json structure ---');
        $this->assertEmpty($data->installations());
    }

    public function testInstallations_ForValidMetaDataSource_ReturnsDecodedJsonStructure()
    {
        $file = ['foo.bar.1.2.3' => [self::$temp->directory('some/directory')]];
        $data = $this->data($file);
        $this->assertSame($file, $data->installations());
    }

    public function testSaveInstallations_CreatesValidMetaData()
    {
        $data = $this->data();
        $save = ['foo.bar.1.2.3' => [self::$temp->directory('some/directory')]];
        $data->saveInstallations($save);
        $this->assertSame($save, $data->installations());
    }

    public function testNotExistingLocations_AreRemovedOnRead()
    {
        $locations = [
            self::$temp->directory('new/directory'),
            self::$temp->file('not/directory'),
            self::$temp->pathname('not/existing/directory')
        ];

        $data = $this->data(['foo.bar.1.2.3' => $locations]);

        $expected = ['foo.bar.1.2.3' => [self::$temp->directory('new/directory')]];
        $this->assertSame($expected, $data->installations());

        $filename = self::$temp->pathname('shared-tools/install-locations.json');
        $this->assertSame($expected, json_decode(file_get_contents($filename), true));
    }

    private function data(?array $fileContents = null): FileMetaData
    {
        if ($fileContents !== null) {
            $contents = json_encode($fileContents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            self::$temp->file('shared-tools/install-locations.json', $contents);
        }
        return new FileMetaData(self::$temp->pathname('shared-tools'));
    }
}
