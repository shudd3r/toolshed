<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests\Tools\Tool;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\Tools\Tool\Composer;
use Shudd3r\Toolshed\Tools\Tool\Identifier;
use Shudd3r\Toolshed\Tests\Fixtures\TempFiles;


class ComposerTest extends TestCase
{
    private static TempFiles $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = new TempFiles(basename(static::class));
    }

    protected function tearDown(): void
    {
        self::$temp->clear();
    }

    public function testInstall_ForNotExistingToolDirectory()
    {
        $composer = $this->composer();
        $tool     = Identifier::fromStrings('some/package', '^9.6');

        $this->assertEquals(1, $composer->install($tool));
        $this->assertEquals('Tool directory does not exist', $composer->output());
        $this->assertFileDoesNotExist(self::$temp->pathname('some.package.9.7.11/composer.lock'));
    }

    public function testInstall_ForDirectoryWithoutComposerJson()
    {
        $composer = $this->composer();
        $tool     = Identifier::fromStrings('some/package', '9.7.11');

        self::$temp->directory('some.package.9.7.11');

        $this->assertEquals(1, $composer->install($tool));
        $this->assertStringContainsString('No composer.json in current directory', $composer->output());
        $this->assertFileDoesNotExist(self::$temp->pathname('some.package.9.7.11/composer.lock'));
    }

    public function testInstall_ForUnresolvedToolVersion()
    {
        $composer = $this->composer();
        $tool     = Identifier::fromStrings('some/package', '^9.6');

        $packageJson = ['name' => 'some/package', 'description' => 'This is some package', 'version' => '9.7.3'];
        self::$temp->file('repo/composer.json', $this->json($packageJson));
        $composerJson = ['repositories' => [['type' => 'path', 'url' => '../repo']]] + $tool->composerRequire();
        self::$temp->file('some.package.unresolved/composer.json', $this->json($composerJson));

        $this->assertEquals(0, $composer->install($tool));
        $this->assertStringContainsString('- Locking some/package (9.7.3)', $composer->output());
        $this->assertFileDoesNotExist(self::$temp->pathname('some.package.unresolved/composer.lock'));
    }

    public function testInstall_ForResolvedToolVersion()
    {
        $composer = $this->composer();
        $tool     = Identifier::fromStrings('some/package', '9.7.11');

        $packageJson = ['name' => 'some/package', 'description' => 'This is some package', 'version' => '9.7.11'];
        self::$temp->file('repo/composer.json', $this->json($packageJson));
        $composerJson = ['repositories' => [['type' => 'path', 'url' => '../repo']]] + $tool->composerRequire();
        self::$temp->file('some.package.9.7.11/composer.json', $this->json($composerJson));

        $this->assertEquals(0, $composer->install($tool));
        $this->assertStringContainsString('- Locking some/package (9.7.11)', $composer->output());
        $this->assertFileExists(self::$temp->pathname('some.package.9.7.11/composer.lock'));
    }

    private function composer(): Composer
    {
        return new Composer(self::$temp->pathname(''));
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
