<?php

namespace SchemaKeeper\Tests\Core;

use SchemaKeeper\Core\DumpEntryPoint;
use SchemaKeeper\Tests\SchemaTestCase;

class DumpEntryPointTest extends SchemaTestCase
{
    /**
     * @var DumpEntryPoint
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $conn = $this->getConn();
        $params = $this->getDbParams();
        $this->target = new DumpEntryPoint($conn, $params);

        exec('rm -rf /tmp/schema_keeper');
    }

    public function testOk()
    {
        $this->target->execute('/tmp/schema_keeper');

        self::assertEquals([
            '/tmp/schema_keeper/extensions',
            '/tmp/schema_keeper/structure',
        ], glob('/tmp/schema_keeper/*'));

        self::assertEquals([
            '/tmp/schema_keeper/extensions/plpgsql.txt',
        ], glob('/tmp/schema_keeper/extensions/*'));

        self::assertEquals('pg_catalog', file_get_contents('/tmp/schema_keeper/extensions/plpgsql.txt'));

        self::assertEquals([
            '/tmp/schema_keeper/structure/public',
            '/tmp/schema_keeper/structure/test_schema',
        ], glob('/tmp/schema_keeper/structure/*'));

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/functions',
            '/tmp/schema_keeper/structure/public/materialized_views',
            '/tmp/schema_keeper/structure/public/sequences',
            '/tmp/schema_keeper/structure/public/tables',
            '/tmp/schema_keeper/structure/public/triggers',
            '/tmp/schema_keeper/structure/public/types',
            '/tmp/schema_keeper/structure/public/views',
        ], glob('/tmp/schema_keeper/structure/public/*'));

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/tables/test_table.txt',
        ], glob('/tmp/schema_keeper/structure/public/tables/*'));

        $expectedTable = '                         Table "public.test_table"
 Column |  Type  |                        Modifiers                        
--------+--------+---------------------------------------------------------
 id     | bigint | not null default nextval(\'test_table_id_seq\'::regclass)
 values | text   | 
Indexes:
    "test_table_pkey" PRIMARY KEY, btree (id)
Triggers:
    test_trigger BEFORE UPDATE ON test_table FOR EACH ROW EXECUTE PROCEDURE trig_test()

';

        $actualTable = file_get_contents('/tmp/schema_keeper/structure/public/tables/test_table.txt');
        self::assertEquals($expectedTable, $actualTable);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/sequences/test_table_id_seq.txt',
        ], glob('/tmp/schema_keeper/structure/public/sequences/*'));

        $expectedSequence = '{
    "seq_path": "public.test_table_id_seq",
    "data_type": "bigint",
    "start_value": "1",
    "minimum_value": "1",
    "maximum_value": "9223372036854775807",
    "increment": "1",
    "cycle_option": "NO"
}';

        $actualSequence = file_get_contents('/tmp/schema_keeper/structure/public/sequences/test_table_id_seq.txt');
        self::assertEquals($expectedSequence, $actualSequence);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/views/test_view.txt',
        ], glob('/tmp/schema_keeper/structure/public/views/*'));

        $expectedView = '               View "public.test_view"
 Column |  Type  | Modifiers | Storage  | Description 
--------+--------+-----------+----------+-------------
 id     | bigint |           | plain    | 
 values | text   |           | extended | 
View definition:
 SELECT test_table.id,
    test_table."values"
   FROM test_table;

';
        $actualView = file_get_contents('/tmp/schema_keeper/structure/public/views/test_view.txt');

        self::assertEquals($expectedView, $actualView);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/materialized_views/test_mat_view.txt',
        ], glob('/tmp/schema_keeper/structure/public/materialized_views/*'));

        $actualMaterializedView = file_get_contents('/tmp/schema_keeper/structure/public/materialized_views/test_mat_view.txt');
        $expectedMaterializedView = '              Materialized view "public.test_mat_view"
 Column |  Type  | Modifiers | Storage  | Stats target | Description 
--------+--------+-----------+----------+--------------+-------------
 id     | bigint |           | plain    |              | 
 values | text   |           | extended |              | 
View definition:
 SELECT test_table.id,
    test_table."values"
   FROM test_table;

';
        self::assertEquals($expectedMaterializedView, $actualMaterializedView);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/functions/trig_test().sql',
        ], glob('/tmp/schema_keeper/structure/public/functions/*'));

        $actualFunction = file_get_contents('/tmp/schema_keeper/structure/public/functions/trig_test().sql');
        $expectedFunction = 'CREATE OR REPLACE FUNCTION public.trig_test()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RETURN NEW;
END;
$function$
';

        self::assertEquals($expectedFunction, $actualFunction);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/triggers/test_table.test_trigger.sql',
        ], glob('/tmp/schema_keeper/structure/public/triggers/*'));

        $actualTrigger = file_get_contents('/tmp/schema_keeper/structure/public/triggers/test_table.test_trigger.sql');
        $expectedTrigger = 'CREATE TRIGGER test_trigger BEFORE UPDATE ON test_table FOR EACH ROW EXECUTE PROCEDURE trig_test()';

        self::assertEquals($expectedTrigger, $actualTrigger);

        self::assertEquals([
            '/tmp/schema_keeper/structure/public/types/test_enum_type.txt',
            '/tmp/schema_keeper/structure/public/types/test_type.txt',
        ], glob('/tmp/schema_keeper/structure/public/types/*'));

        $actualType = file_get_contents('/tmp/schema_keeper/structure/public/types/test_type.txt');
        $expectedType = '   Composite type "public.test_type"
 Column |       Type        | Modifiers 
--------+-------------------+-----------
 id     | bigint            | 
 values | character varying | 

';

        self::assertEquals($expectedType, $actualType);

        $actualEnumType = file_get_contents('/tmp/schema_keeper/structure/public/types/test_enum_type.txt');
        $expectedEnumType = '{enum1,enum2}';

        self::assertEquals($expectedEnumType, $actualEnumType);
    }
}
