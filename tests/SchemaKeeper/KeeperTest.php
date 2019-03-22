<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests;

use SchemaKeeper\Keeper;

class KeeperTest extends SchemaTestCase
{
    /**
     * @var Keeper
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $conn = $this->getConn();
        $params = $this->getDbParams();
        $this->target = new Keeper($conn, $params);

        exec('rm -rf /tmp/schema_keeper');
    }

    public function testOk()
    {
        $this->target->saveDump('/tmp/schema_keeper');

        $conn = $this->getConn();
        $conn->beginTransaction();
        $this->target->deployDump('/tmp/schema_keeper');
        $conn->rollBack();

        $this->target->verifyDump('/tmp/schema_keeper');

        self::assertTrue(true);
    }
}
