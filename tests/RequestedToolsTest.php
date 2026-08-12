<?php declare(strict_types=1);

/*
 * This file is part of Shudd3r/Toolshed package.
 *
 * (c) shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Shudd3r\Toolshed\Tests;

use PHPUnit\Framework\TestCase;
use Shudd3r\Toolshed\RequestedTools;
use Shudd3r\Toolshed\Filesystem\Virtual;
use Shudd3r\Toolshed\Tools\Identifier;


class RequestedToolsTest extends TestCase
{
    public function testInstanceDataMethods()
    {
        $request = $this->request($clientBinDirectory, $toolIdentifiers);

        $this->assertSame($clientBinDirectory, $request->clientBinDirectory());
        $this->assertSame($toolIdentifiers, $request->toolIdentifiers());

        $request->update($resolved = Identifier::fromStrings('phpunit/phpunit', '9.8.11'));
        $this->assertSame([$resolved] + $toolIdentifiers, $request->toolIdentifiers());
    }

    public function testUpdateWithUnresolvedIdentifier_RemovesItFromList()
    {
        $request = $this->request($clientBinDirectory, $toolIdentifiers);

        $request->update(Identifier::fromStrings('phpunit/phpunit'));
        $this->assertSame([$toolIdentifiers[1]], $request->toolIdentifiers());
    }

    private function request(?Virtual\VirtualDirectory &$clientBinDirectory, ?array &$toolIdentifiers): RequestedTools
    {
        $clientBinDirectory ??= Virtual\VirtualDirectory::root('/usr/home/project/vendor/bin');
        $toolIdentifiers ??= [
            Identifier::fromStrings('phpunit/phpunit', '^9.5', '^7.4 || ^8.0'),
            Identifier::fromStrings('polymorphine/dev', '0.6.0')
        ];

        return new RequestedTools($clientBinDirectory, $toolIdentifiers);
    }
}
