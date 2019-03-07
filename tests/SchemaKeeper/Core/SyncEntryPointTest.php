<?php

namespace SchemaKeeper\Tests\Core;

use PDO;
use SchemaKeeper\Core\DumpEntryPoint;
use SchemaKeeper\Core\SyncEntryPoint;
use SchemaKeeper\Tests\SchemaTestCase;

class SyncEntryPointTest extends SchemaTestCase
{
    /**
     * @var SyncEntryPoint
     */
    private $target;

    /**
     * @var DumpEntryPoint
     */
    private $dumpEntryPoint;

    /**
     * @var PDO
     */
    private $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = $this->getConn();
        $params = $this->getDbParams();
        $this->target = new SyncEntryPoint($this->conn, $params);
        $this->dumpEntryPoint = new DumpEntryPoint($this->conn, $params);

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
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');
        $actual = $this->target->execute('/tmp/schema_keeper');

        $expected = [
            'expected' => null,
            'actual' => null,
            'deleted' => [],
            'created' => [],
            'changed' => [],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testCreateFunction()
    {
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');

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
            'expected' => null,
            'actual' => null,
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
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');

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
            'expected' => null,
            'actual' => null,
            'deleted' => [],
            'created' => [],
            'changed' => [
                'public.trig_test()'
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testChangeFunctionWithDiff()
    {
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');

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

        $actual = $this->target->execute('/tmp/schema_keeper');

        $functionAfterCompilation = 'CREATE OR REPLACE FUNCTION public.trig_test()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RETURN NEW;
END;
$function$
';

        $expected = [
            'expected' => [
                'functions' => [
                    'public.trig_test()' => $function,
                ]
            ],
            'actual' => [
                'functions' => [
                    'public.trig_test()' => $functionAfterCompilation,
                ]
            ],
            'deleted' => [],
            'created' => [],
            'changed' => [
                'public.trig_test()'
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testDeleteFunction()
    {
        $this->dumpEntryPoint->execute('/tmp/schema_keeper');

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
            'expected' => null,
            'actual' => null,
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

        $this->dumpEntryPoint->execute('/tmp/schema_keeper');

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
            'expected' => null,
            'actual' => null,
            'deleted' => [],
            'created' => [],
            'changed' => [
                'public.func_test()'
            ],
        ];

        self::assertEquals($expected, $actual);
    }
}
