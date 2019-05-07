<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\CLI;

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
        $output = shell_exec('/data/bin/schemakeeper -c /data/.dev/cli-config.php -d /tmp/dump save');
        self::assertEquals('Dump saved to /tmp/dump', $output);

        $output = shell_exec('/data/bin/schemakeeper -c /data/.dev/cli-config.php -d /tmp/dump verify');
        self::assertEquals('Dump verified /tmp/dump', $output);

        $output = shell_exec('/data/bin/schemakeeper -c /data/.dev/cli-config.php -d /tmp/dump deploy');
        self::assertEquals("Dump deployed /tmp/dump.\n", $output);
    }

    function testHelp()
    {
        $output = shell_exec('/data/bin/schemakeeper --help');
        self::assertContains('Usage: schemakeeper [options] <command>', $output);
    }
}
