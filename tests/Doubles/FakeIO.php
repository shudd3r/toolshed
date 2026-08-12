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

use Composer\IO\NullIO;


class FakeIO extends NullIO
{
    public array $messages = [];
    public array $errors   = [];

    private bool $newMsgline   = true;
    private bool $newErrorLine = true;
    private int  $verbosity;

    public function __construct(int $verbosity = self::NORMAL)
    {
        $this->verbosity = $verbosity;
    }

    public function write($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        if ($this->verbosity < $verbosity) {
            $this->resetNewLines();
            return;
        }
        $this->writeInto($messages, $this->newMsgline, $this->messages);
        $this->newMsgline   = $newline;
        $this->newErrorLine = true;
    }

    public function writeError($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        if ($this->verbosity < $verbosity) {
            $this->resetNewLines();
            return;
        }
        $this->writeInto($messages, $this->newErrorLine, $this->errors);
        $this->newErrorLine = $newline;
        $this->newMsgline   = true;
    }

    private function writeInto($messages, bool $newline, &$thisMessages): void
    {
        if (!$newline && $thisMessages) {
            $this->joinLastLine(is_array($messages) ? array_shift($messages) : $messages, $thisMessages);
            if (is_string($messages) || !$messages) { return; }
        }

        $thisMessages[] = $messages;
    }

    private function joinLastLine(string $message, &$thisMessages): void
    {
        $lastMessage = array_pop($thisMessages);
        if (is_array($lastMessage)) {
            $lastLine = array_pop($lastMessage);
            $lastMessage[] = $lastLine . $message;
        } else {
            $lastMessage .= $message;
        }
        $thisMessages[] = $lastMessage;
    }

    private function resetNewLines(): void
    {
        $this->newMsgline   = true;
        $this->newErrorLine = true;
    }
}
