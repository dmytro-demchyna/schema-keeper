<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Provider\PostgreSQL;

use SchemaKeeper\Provider\PostgreSQL\PSQLClient;
use SchemaKeeper\Tests\SchemaTestCase;

class PSQLClientTest extends SchemaTestCase
{
    /**
     * @var PSQLClient
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $dbParams = $this->getDbParams();

        $this->target = new PSQLClient(
            $dbParams->getDbName(),
            $dbParams->getHost(),
            $dbParams->getPort(),
            $dbParams->getUser(),
            $dbParams->getPassword()
        );
    }

    public function testOneCommandOk()
    {
        $expected = "12345";

        $actual = $this->target->run("\qecho -n 12345");

        self::assertEquals($expected, $actual);
    }

    public function testMultipleCommands()
    {
        $expected = [
            "12345",
            "54321"
        ];

        $actual = $this->target->runMultiple(["\qecho -n 12345", "\qecho -n 54321"]);

        self::assertEquals($expected, $actual);
    }

    public function testBatchCommands()
    {
        $commands = [];

        for($i = 0; $i < 502; $i++) {
            $commands[] =  '\qecho -n num'.$i;
        }

        $actual = $this->target->runMultiple($commands);

        self::assertCount(502, $actual);
        self::assertEquals('num501', $actual[501]);
    }

    public function testEmptyCommands()
    {
        $expected = [];

        $actual = $this->target->runMultiple([]);

        self::assertEquals($expected, $actual);
    }
}
