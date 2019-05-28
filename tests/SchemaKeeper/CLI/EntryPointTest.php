<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\CLI;

use SchemaKeeper\CLI\EntryPoint;
use SchemaKeeper\Tests\SchemaTestCase;

class EntryPointTest extends SchemaTestCase
{
    /**
     * @var EntryPoint
     */
    private $target;

    function setUp()
    {
        parent::setUp();

        $this->target = new EntryPoint();

        exec('rm -rf /tmp/dump');
    }

    function testOk()
    {
        $result = $this->target->run(['c' => '/data/.dev/keeper-config.php', 'd' => '/tmp/dump'], ['save']);
        self::assertEquals('Success: Dump saved /tmp/dump', $result->getMessage());
        self::assertSame(0, $result->getStatus());

        $result = $this->target->run(['c' => '/data/.dev/keeper-config.php', 'd' => '/tmp/dump'], ['verify']);
        self::assertEquals('Success: Dump verified /tmp/dump', $result->getMessage());
        self::assertSame(0, $result->getStatus());

        $result = $this->target->run(['c' => '/data/.dev/keeper-config.php', 'd' => '/tmp/dump'], ['deploy']);
        self::assertEquals('Success: Nothing to deploy /tmp/dump', $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    function testHelp()
    {
        $result = $this->target->run(['help' => 0], []);
        self::assertContains('Usage: schemakeeper [options] <command>', $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    function testVersion()
    {
        $result = $this->target->run(['version' => 0], []);
        self::assertEquals('', $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    function testConfigError()
    {
        $result = $this->target->run([], []);
        self::assertEquals('Failure: Config file not found or not readable ', $result->getMessage());

        self::assertSame(1, $result->getStatus());
    }

    function testUnrecognizedCommand()
    {
        $result = $this->target->run(['c' => '/data/.dev/keeper-config.php', 'd' => '/tmp/dump'], ['blabla']);
        self::assertEquals(
            'Failure: Unrecognized command blabla. Available commands: save, verify, deploy',
            $result->getMessage()
        );
        self::assertSame(1, $result->getStatus());
    }

    function testUnrecognizedOption()
    {
        $result = $this->target->run(['blabla' => 0], []);
        self::assertEquals('Unrecognized option: blabla', $result->getMessage());
        self::assertSame(1, $result->getStatus());
    }
}
