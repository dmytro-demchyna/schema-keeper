<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Core;

use SchemaKeeper\Core\DumpEntryPoint;
use SchemaKeeper\Core\TestEntryPoint;
use SchemaKeeper\Tests\SchemaTestCase;

class TestEntryPointTest extends SchemaTestCase
{
    /**
     * @var TestEntryPoint
     */
    private $target;

    /**
     * @var DumpEntryPoint
     */
    private $dumpEntryPoint;

    public function setUp()
    {
        parent::setUp();

        $conn = $this->getConn();
        $params = $this->getDbParams();
        $this->target = new TestEntryPoint($conn, $params);
        $this->dumpEntryPoint = new DumpEntryPoint($conn, $params);

        exec('rm -rf /tmp/schema_keeper');
    }

    public function testOk()
    {
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');
        $this->target->execute('/tmp/schema_keeper');

        self::assertTrue(true);
    }

    public function testDiff()
    {
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');
        exec('rm -r /tmp/schema_keeper/structure/public/triggers');

        $expected = [
            'expected' => [],
            'actual' => [
                'triggers' => [
                    'public.test_table.test_trigger' => 'CREATE TRIGGER test_trigger BEFORE UPDATE ON test_table FOR EACH ROW EXECUTE PROCEDURE trig_test()',
                ],
            ],
        ];

        $actual = $this->target->execute('/tmp/schema_keeper');

        self::assertEquals($expected, $actual);
    }
}
