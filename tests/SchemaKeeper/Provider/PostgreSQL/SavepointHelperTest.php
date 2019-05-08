<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Provider\PostgreSQL;

use Mockery\MockInterface;
use PDO;
use SchemaKeeper\Provider\PostgreSQL\SavepointHelper;
use SchemaKeeper\Tests\SchemaTestCase;

class SavepointHelperTest extends SchemaTestCase
{
    /**
     * @var SavepointHelper
     */
    private $target;

    /**
     * @var PDO|MockInterface
     */
    private $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = \Mockery::mock(PDO::class);

        $this->target = new SavepointHelper($this->conn);
    }

    public function testBeginTransaction()
    {
        $this->conn->shouldNotReceive('exec');
        $this->conn->shouldReceive('beginTransaction')->once();

        $this->target->beginTransaction('test', false);
    }

    public function testBeginTransactionUsingSavepoint()
    {
        $this->conn->shouldReceive('exec')->with('SAVEPOINT test')->once();
        $this->conn->shouldNotReceive('beginTransaction');

        $this->target->beginTransaction('test', true);
    }

    public function testCommit()
    {
        $this->conn->shouldNotReceive('exec');
        $this->conn->shouldReceive('commit')->once();

        $this->target->commit('test', false);
    }

    public function testCommitUsingSavepoint()
    {
        $this->conn->shouldReceive('exec')->with('RELEASE SAVEPOINT test')->once();
        $this->conn->shouldNotReceive('commit');

        $this->target->commit('test', true);
    }

    public function testRollback()
    {
        $this->conn->shouldNotReceive('exec');
        $this->conn->shouldReceive('rollback')->once();

        $this->target->rollback('test', false);
    }

    public function testRollbackUsingSavepoint()
    {
        $this->conn->shouldReceive('exec')->with('ROLLBACK TO SAVEPOINT test')->once();
        $this->conn->shouldNotReceive('rollback');

        $this->target->rollback('test', true);
    }
}
