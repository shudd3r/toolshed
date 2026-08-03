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
use Shudd3r\Toolshed\Sync\RefData\FileRefData;
use Shudd3r\Toolshed\Filesystem\Virtual\VirtualDirectory;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class FileRefDataTest extends TestCase
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
        $data = $this->data($toolsDir);
        $this->assertFalse($toolsDir->exists());
        $this->assertEmpty($data->toolRefs());

        $toolsDir->create();
        $this->assertEmpty($data->toolRefs());

        $toolsDir->file('install-locations.json')->write('--- not json structure ---');
        $this->assertEmpty($data->toolRefs());
    }

    public function testToolRefsMethod_ForValidDataFile_ReturnsDecodedJsonStructure()
    {
        $data = $this->data($toolsDir);
        $refs = ['foo.bar.1.2.3' => [self::$temp->pathname('existing/directory')]];
        $this->writeData($toolsDir, $refs);
        $this->assertSame($refs, $data->toolRefs());
    }

    public function testSaveMethod_CreatesValidMetaData()
    {
        $data = $this->data($toolsDir);
        $data->save($refs = ['foo.bar.1.2.3' => [self::$temp->pathname('existing/directory')]]);
        $this->assertSame($refs, $data->toolRefs());
        $this->assertSame($refs, json_decode($toolsDir->file('install-locations.json')->contents(), true));
    }

    public function testNotExistingLocations_AreFilteredOnRead()
    {
        $locations = [
            self::$temp->pathname('foo/bar/file.txt'),
            self::$temp->pathname('existing/directory'),
            self::$temp->pathname('not/existing/node')
        ];

        $data = $this->data($toolsDir);
        $this->writeData($toolsDir, ['foo.bar.1.2.3' => $locations]);

        $expected = ['foo.bar.1.2.3' => [$locations[1]]];
        $this->assertSame($expected, $data->toolRefs());
    }

    private function data(?VirtualDirectory &$toolsDir = null): FileRefData
    {
        $toolsDir ??= VirtualDirectory::root('vfs://root')->subdirectory('shared-tools');
        return new FileRefData($toolsDir);
    }

    private function writeData(VirtualDirectory $toolsDir, array $data): void
    {
        $contents = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $toolsDir->file('install-locations.json')->write($contents);
    }
}
