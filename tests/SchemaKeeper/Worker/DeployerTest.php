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
        $this->saver->save('/tmp/schema_keeper');
        $result = $this->target->deploy('/tmp/schema_keeper');

        self::assertEquals([], $result->getChanged());
        self::assertEquals([], $result->getCreated());
        self::assertEquals([], $result->getDeleted());
    }

    public function testCreateFunction()
    {
        $this->saver->save('/tmp/schema_keeper');

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

        $result = $this->target->deploy('/tmp/schema_keeper');

        $created = [
            'public.func_test()'
        ];

        self::assertEquals([], $result->getChanged());
        self::assertEquals($created, $result->getCreated());
        self::assertEquals([], $result->getDeleted());
    }

    public function testChangeFunction()
    {
        $this->saver->save('/tmp/schema_keeper');

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

        $result = $this->target->deploy('/tmp/schema_keeper');

        $changed = [
            'public.trig_test()'
        ];

        self::assertEquals($changed, $result->getChanged());
        self::assertEquals([], $result->getCreated());
        self::assertEquals([], $result->getDeleted());
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage These functions have diff between their definitions from dump and their definitions after deploy: public.trig_test()
     */
    public function testChangeFunctionWithDiff()
    {
        $this->saver->save('/tmp/schema_keeper');

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

        $this->target->deploy('/tmp/schema_keeper');
    }

    public function testDeleteFunction()
    {
        $this->saver->save('/tmp/schema_keeper');

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

        $result = $this->target->deploy('/tmp/schema_keeper');

        $deleted = [
            'public.func_test()'
        ];

        self::assertEquals([], $result->getChanged());
        self::assertEquals([], $result->getCreated());
        self::assertEquals($deleted, $result->getDeleted());
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

        $this->saver->save('/tmp/schema_keeper');

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

        $result = $this->target->deploy('/tmp/schema_keeper');

        $changed =
            [
                'public.func_test()'
            ];

        self::assertEquals($changed, $result->getChanged());
        self::assertEquals([], $result->getCreated());
        self::assertEquals([], $result->getDeleted());
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage TARGET: public.trig_test()
     */
    public function testError()
    {
        $this->saver->save('/tmp/schema_keeper');

        $function = 'fd';

        file_put_contents('/tmp/schema_keeper/structure/public/functions/trig_test().sql', $function);

        $this->target->deploy('/tmp/schema_keeper');
    }

    /**
     * @expectedException \SchemaKeeper\Exception\KeeperException
     * @expectedExceptionMessage Forbidden to remove all functions using SchemaKeeper
     */
    public function testEmptyDump()
    {
        $this->target->deploy('/tmp/schema_keeper');
    }
}
