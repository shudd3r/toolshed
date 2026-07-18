<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\SharedFiles;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\SharedFiles\LocalSharedFiles;
use Shudd3r\Toolshed\Tests\Fixtures;


class LocalSharedFilesTest extends TestCase
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

    public function testInstallations_WhenDataFileCannotBeRead_ReturnsEmptyArray()
    {
        $files = new LocalSharedFiles(self::$temp->pathname('not-existing-directory'));
        $this->assertEmpty($files->installations());

        $files = new LocalSharedFiles(self::$temp->directory('shared-files'));
        $this->assertEmpty($files->installations());

        self::$temp->file('shared-files/install-locations.json', 'not json structure');
        $this->assertEmpty($files->installations());
    }

    public function testInstallations_ForValidDataFile_ReturnsDecodedJsonStructure()
    {
        $files = new LocalSharedFiles(self::$temp->directory('shared-files'));
        $data  = ['foo.bar.1.2.3' => '/some/directory'];
        self::$temp->file('shared-files/install-locations.json', json_encode($data));
        $this->assertSame($data, $files->installations());
    }

    public function testSaveInstallations_CreatesValidDataFile()
    {
        $files = new LocalSharedFiles(self::$temp->pathname('not-existing-directory'));
        $data  = ['foo.bar.1.2.3' => '/some/directory'];
        $files->saveInstallations($data);
        $this->assertSame($data, $files->installations());
    }
}
