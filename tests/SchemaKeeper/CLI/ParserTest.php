<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\CLI;

use SchemaKeeper\CLI\Parser;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;
use SchemaKeeper\Tests\SchemaTestCase;

class ParserTest extends SchemaTestCase
{
    /**
     * @var Parser
     */
    private $target;

    function setUp()
    {
        parent::setUp();

        $this->target = new Parser();
    }

    function testOk()
    {
        $parsed = $this->target->parse(['c' => '/data/.dev/cli-config.php', 'd' => '/tmp/dump'], ['save']);

        self::assertInstanceOf(PSQLParameters::class, $parsed->getParams());
        self::assertEquals('/tmp/dump', $parsed->getPath());
        self::assertEquals('save', $parsed->getCommand());
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage  Config file not found or not readable /data/dummyconfig
     */
    function testConfigNotExisted()
    {
        $this->target->parse(['c' => '/data/dummyconfig'], []);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage  Config file must return instance of SchemaKeeper\Provider\PostgreSQL\PSQLParameters
     */
    function testConfigBadInstance()
    {
        file_put_contents('/tmp/dummyconfig', '');

        $this->target->parse(['c' => '/tmp/dummyconfig'], []);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage  Destination path not specified
     */
    function testWithoutDestinationPath()
    {
        $this->target->parse(['c' => '/data/.dev/cli-config.php'], []);
    }
}
