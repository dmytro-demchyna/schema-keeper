<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Filesystem;

use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class FilesystemHelperNameEncodingTest extends UnitTestCase
{
    private FilesystemHelper $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new FilesystemHelper();
    }

    public function testEncodeNamePassesNormalNames(): void
    {
        self::assertSame('valid_name', $this->target->encodeName('valid_name'));
        self::assertSame('func(integer, text)', $this->target->encodeName('func(integer, text)'));
        self::assertSame('name-with-dashes', $this->target->encodeName('name-with-dashes'));
        self::assertSame('name with spaces', $this->target->encodeName('name with spaces'));
    }

    public function testEncodeNameSlash(): void
    {
        self::assertSame('has~Sslash', $this->target->encodeName('has/slash'));
        self::assertSame('a~Sb~Sc', $this->target->encodeName('a/b/c'));
    }

    public function testEncodeNameBackslash(): void
    {
        self::assertSame('has~Bbackslash', $this->target->encodeName('has\backslash'));
    }

    public function testEncodeNameTilde(): void
    {
        self::assertSame('has~~tilde', $this->target->encodeName('has~tilde'));
        self::assertSame('100~~', $this->target->encodeName('100~'));
    }

    public function testEncodeNameNullByte(): void
    {
        self::assertSame('evil~0name', $this->target->encodeName("evil\0name"));
    }

    public function testEncodeNameLeadingDot(): void
    {
        self::assertSame('~Dhidden', $this->target->encodeName('.hidden'));
        self::assertSame('~D.', $this->target->encodeName('..'));
        self::assertSame('~Dgitkeep', $this->target->encodeName('.gitkeep'));
    }

    public function testEncodeNameWindowsUnsafeChars(): void
    {
        self::assertSame('has~Ccolon', $this->target->encodeName('has:colon'));
        self::assertSame('has~Aasterisk', $this->target->encodeName('has*asterisk'));
        self::assertSame('has~Qquestion', $this->target->encodeName('has?question'));
        self::assertSame('has~Equote', $this->target->encodeName('has"quote'));
        self::assertSame('has~Lless', $this->target->encodeName('has<less'));
        self::assertSame('has~Ggreater', $this->target->encodeName('has>greater'));
        self::assertSame('has~Ppipe', $this->target->encodeName('has|pipe'));
    }

    public function testEncodeNameReservedWindowsNames(): void
    {
        self::assertSame('~NCON', $this->target->encodeName('CON'));
        self::assertSame('~NNUL', $this->target->encodeName('NUL'));
        self::assertSame('~Ncom1', $this->target->encodeName('com1'));
        self::assertSame('CONSOLE', $this->target->encodeName('CONSOLE'));
        self::assertSame('~NCON.foo', $this->target->encodeName('CON.foo'));
        self::assertSame('~NLPT1.trigger', $this->target->encodeName('LPT1.trigger'));
    }

    public function testEncodeNameLengthLimit(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Encoded name exceeds 250 bytes');
        $this->target->encodeName(str_repeat('a', 251));
    }

    public function testEncodeNameEmptyThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Empty name for filesystem');
        $this->target->encodeName('');
    }

    public function testDecodeNameRoundTrip(): void
    {
        $names = [
            'normal_name',
            'func(integer, text)',
            'has/slash',
            'has\backslash',
            'has~tilde',
            '.hidden',
            '..',
            "evil\0name",
            '100~',
            'a/b/c',
            'has:colon',
            'has*asterisk',
            'has?question',
            'has"quote',
            'has<less',
            'has>greater',
            'has|pipe',
            'CON',
            'NUL',
            'com1',
            'LPT9',
            'CON.foo',
            'LPT1.trigger',
        ];

        foreach ($names as $name) {
            self::assertSame($name, $this->target->decodeName($this->target->encodeName($name)));
        }
    }

    public function testDecodeNameUnknownEscapeThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid encoded name');
        $this->target->decodeName('foo~Xbar');
    }

    public function testDecodeNameTrailingTildeThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid encoded name');
        $this->target->decodeName('foo~');
    }

    public function testDecodeNameDInMiddleThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid encoded name');
        $this->target->decodeName('foo~Dbar');
    }
}
