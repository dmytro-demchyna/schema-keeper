<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\CLI;

use Mockery\MockInterface;
use SchemaKeeper\CLI\Runner;
use SchemaKeeper\Keeper;
use SchemaKeeper\Tests\SchemaTestCase;

class RunnerTest extends SchemaTestCase
{
    /**
     * @var Runner
     */
    private $target;

    /**
     * @var Keeper|MockInterface
     */
    private $keeper;

    function setUp()
    {
        parent::setUp();

        $this->keeper = \Mockery::mock(Keeper::class);
        $this->target = new Runner($this->keeper);
    }

    function testSave()
    {
        $this->keeper->shouldReceive('saveDump')->with('/tmp/dump')->once();

        $message = $this->target->run('save', '/tmp/dump');

        self::assertEquals('Dump saved to /tmp/dump', $message);
    }

    function testVerify()
    {
        $this->keeper->shouldReceive('verifyDump')->with('/tmp/dump')->once();

        $message = $this->target->run('verify', '/tmp/dump');

        self::assertEquals('Dump verified /tmp/dump', $message);
    }

    function testDeploy()
    {
        $this->keeper->shouldReceive('deployDump')->with('/tmp/dump')->andReturn([
            'created' => ['1', '11'],
            'changed' => ['2'],
            'deleted' => ['3'],
        ])->once();

        $message = $this->target->run('deploy', '/tmp/dump');

        self::assertEquals("Dump deployed /tmp/dump.\nDeleted 3\nCreated 1\nCreated 11\nChanged 2\n", $message);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage Dump and current database not equals: {"expected":"1","actual":"2"}
     */
    function testVerifyNotEquals()
    {
        $this->keeper->shouldReceive('verifyDump')->with('/tmp/dump')->andReturn([
            'expected' => '1',
            'actual' => '2',
        ])->once();

        $message = $this->target->run('verify', '/tmp/dump');

        self::assertEquals('Dump verified /tmp/dump', $message);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage Command blabla not exists
     */
    function testUndefinedFunction()
    {
        $this->target->run('blabla', '/tmp/dump');
    }
}
