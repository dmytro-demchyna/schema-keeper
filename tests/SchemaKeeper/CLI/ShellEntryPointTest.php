<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\CLI;

use SchemaKeeper\CLI\Version;
use SchemaKeeper\Tests\SchemaTestCase;

class ShellEntryPointTest extends SchemaTestCase
{
    function setUp()
    {
        parent::setUp();

        exec('rm -rf /tmp/dump');
    }

    function testOk()
    {
        exec('/data/bin/schemakeeper -c /data/.dev/cli-config.php -d /tmp/dump save', $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertEquals(Version::getVersionText() . 'Success: Dump saved /tmp/dump', $output);
        self::assertSame(0, $status);
    }

    function testHelp()
    {
        exec('/data/bin/schemakeeper --help', $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertContains('Usage: schemakeeper [options] <command>', $output);
        self::assertSame(0, $status);
    }

    function testVersion()
    {
        exec('/data/bin/schemakeeper --version', $output, $status);
        $output = implode(PHP_EOL, $output);

        self::assertEquals(Version::getVersionText(), $output);
        self::assertSame(0, $status);
    }

    function testError()
    {
        exec('/data/bin/schemakeeper -c /data/.dev/cli-config.php -d /tmp/dump verify', $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertEquals(Version::getVersionText() . 'Failure: Dump is empty /tmp/dump', $output);
        self::assertSame(1, $status);
    }
}
