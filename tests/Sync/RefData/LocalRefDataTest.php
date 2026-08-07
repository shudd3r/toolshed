<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Sync\RefData;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualFile;
use Shudd3r\Toolshed\Sync\RefData\LocalRefData;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class LocalRefDataTest extends TestCase
{
    private static TempFiles $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new TempFiles(basename(static::class));
        self::$temp->directory('existing/directory');
        self::$temp->file('foo/bar/file.txt');
    }

    public static function tearDownAfterClass(): void
    {
        self::$temp->clear();
    }

    public function testToolRefsMethod_WhenFileCannotBeRead_ReturnsEmptyArray()
    {
        $data = $this->data($dataFile);
        $this->assertFalse($dataFile->exists());
        $this->assertEmpty($data->toolRefs());

        $dataFile->write('--- not json structure ---');
        $this->assertEmpty($data->toolRefs());
    }

    public function testToolRefsMethod_ForValidDataFile_ReturnsDecodedJsonStructure()
    {
        $data = $this->data($dataFile);
        $refs = ['foo.bar.1.2.3' => [self::$temp->pathname('existing/directory')]];
        $this->writeData($dataFile, $refs);
        $this->assertSame($refs, $data->toolRefs());
    }

    public function testSaveMethod_CreatesValidMetaData()
    {
        $data = $this->data($dataFile);
        $data->save($refs = ['foo.bar.1.2.3' => [self::$temp->pathname('existing/directory')]]);
        $this->assertSame($refs, $data->toolRefs());
        $this->assertSame($refs, json_decode($dataFile->contents(), true));
    }

    public function testNotExistingLocations_AreFilteredOnRead()
    {
        $locations = [
            self::$temp->pathname('foo/bar/file.txt'),
            self::$temp->pathname('existing/directory'),
            self::$temp->pathname('not/existing/node')
        ];

        $data = $this->data($dataFile);
        $this->writeData($dataFile, ['foo.bar.1.2.3' => $locations]);

        $expected = ['foo.bar.1.2.3' => [$locations[1]]];
        $this->assertSame($expected, $data->toolRefs());
    }

    private function data(?VirtualFile &$dataFile = null): LocalRefData
    {
        $dataFile ??= VirtualDirectory::root('vfs://root/shared-tools')->file('install-locations.json');
        return new LocalRefData($dataFile);
    }

    private function writeData(VirtualFile $dataFile, array $data): void
    {
        $contents = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $dataFile->write($contents);
    }
}
