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
        $data = new FileMetaData(self::$temp->pathname('not-existing-directory'));
        $this->assertEmpty($data->installations());

        $data = new FileMetaData(self::$temp->directory('shared-files'));
        $this->assertEmpty($data->installations());

        self::$temp->file('shared-files/install-locations.json', '--- not json structure ---');
        $this->assertEmpty($data->installations());
    }

    public function testInstallations_ForValidMetaDataSource_ReturnsDecodedJsonStructure()
    {
        $data  = new FileMetaData(self::$temp->directory('shared-files'));
        $saved = ['foo.bar.1.2.3' => '/some/directory'];
        self::$temp->file('shared-files/install-locations.json', json_encode($saved));
        $this->assertSame($saved, $data->installations());
    }

    public function testSaveInstallations_CreatesValidMetaData()
    {
        $data  = new FileMetaData(self::$temp->pathname('not-existing-directory'));
        $saved = ['foo.bar.1.2.3' => '/some/directory'];
        $data->saveInstallations($saved);
        $this->assertSame($saved, $data->installations());
    }

    public function testCheckingIfLocationExists()
    {
        $data = new FileMetaData(self::$temp->pathname('fake-tools-directory'));
        $this->assertTrue($data->locationExists(self::$temp->directory('new/directory')));
        $this->assertFalse($data->locationExists(self::$temp->file('not/directory')));
        $this->assertFalse($data->locationExists(self::$temp->pathname('not/existing/directory')));
    }
}
