<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Worker;

use PDO;
use SchemaKeeper\Provider\ProviderFactory;
use SchemaKeeper\Tests\SchemaTestCase;
use SchemaKeeper\Worker\Deployer;
use SchemaKeeper\Worker\Saver;

class DeployerTest extends SchemaTestCase
{
    /**
     * @var Deployer
     */
    private $target;

    /**
     * @var Saver
     */
    private $saver;

    /**
     * @var PDO
     */
    private $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = $this->getConn();
        $params = $this->getDbParams();
        $providerFactory = new ProviderFactory();
        $provider = $providerFactory->createProvider($this->conn, $params);

        $this->target = new Deployer($provider);
        $this->saver = new Saver($provider);

        exec('rm -rf /tmp/schema_keeper');
        $this->conn->beginTransaction();
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
    }

    public function testOk()
    {
        $this->saver->execute('/tmp/schema_keeper');
        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'deleted' => [],
            'created' => [],
            'changed' => [],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testCreateFunction()
    {
        $this->saver->execute('/tmp/schema_keeper');

        $function = 'CREATE OR REPLACE FUNCTION public.func_test()
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RAISE NOTICE \'test\';
END;
$function$
';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/func_test().sql', $function);

        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'deleted' => [],
            'created' => [
                'public.func_test()'
            ],
            'changed' => [],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testChangeFunction()
    {
        $this->saver->execute('/tmp/schema_keeper');

        $function = 'CREATE OR REPLACE FUNCTION public.trig_test()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RAISE NOTICE \'test\';
   RETURN NEW;
END;
$function$
';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/trig_test().sql', $function);

        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'deleted' => [],
            'created' => [],
            'changed' => [
                'public.trig_test()'
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\DiffException
     * @expectedExceptionMessage These functions have diff between their definitions from dump and their definitions after deploy: public.trig_test()
     */
    public function testChangeFunctionWithDiff()
    {
        $this->saver->execute('/tmp/schema_keeper');

        $function = 'cREATE OR REPLACE FUNCTION public.trig_test()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RETURN NEW;
END;
$function$
';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/trig_test().sql', $function);

        $this->target->execute('/tmp/schema_keeper');
    }

    public function testDeleteFunction()
    {
        $this->saver->execute('/tmp/schema_keeper');

        $function = 'CREATE OR REPLACE FUNCTION public.func_test()
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RAISE NOTICE \'test\';
END;
$function$
';

        $this->conn->exec($function);

        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'deleted' => [
                'public.func_test()'
            ],
            'created' => [],
            'changed' => [],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testChangeFunctionReturnType()
    {
        $function = 'CREATE OR REPLACE FUNCTION public.func_test()
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RAISE NOTICE \'test\';
END;
$function$
';

        $this->conn->exec($function);

        $this->saver->execute('/tmp/schema_keeper');

        $changedFunction = 'CREATE OR REPLACE FUNCTION public.func_test()
 RETURNS boolean
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RETURN TRUE;
END;
$function$
';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/func_test().sql', $changedFunction);

        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'deleted' => [],
            'created' => [],
            'changed' => [
                'public.func_test()'
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage TARGET: public.trig_test()
     */
    public function testError()
    {
        $this->saver->execute('/tmp/schema_keeper');

        $function = 'fd';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/trig_test().sql', $function);

        $this->target->execute('/tmp/schema_keeper');
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage Forbidden to remove all functions using SchemaKeeper
     */
    public function testEmptyDump()
    {
        $this->target->execute('/tmp/schema_keeper');
    }
}
