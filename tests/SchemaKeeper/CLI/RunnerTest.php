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
use SchemaKeeper\Outside\DeployedFunctions;
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

        self::assertEquals('Dump saved /tmp/dump', $message);
    }

    function testVerify()
    {
        $this->keeper->shouldReceive('verifyDump')->with('/tmp/dump')->once();

        $message = $this->target->run('verify', '/tmp/dump');

        self::assertEquals('Dump verified /tmp/dump', $message);
    }

    function testDeploy()
    {
        $this->keeper->shouldReceive('deployDump')->with('/tmp/dump')->andReturn(new DeployedFunctions(['2'], ['1', '11'], ['3']))
            ->once();

        $message = $this->target->run('deploy', '/tmp/dump');

        self::assertEquals("Dump deployed /tmp/dump\n  Deleted 3\n  Created 1\n  Created 11\n  Changed 2", $message);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage Unrecognized command blabla. Available commands: save, verify, deploy
     */
    function testUndefinedFunction()
    {
        $this->target->run('blabla', '/tmp/dump');
    }
}
