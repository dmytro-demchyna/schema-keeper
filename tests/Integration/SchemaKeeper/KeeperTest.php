<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Integration\SchemaKeeper;

use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\{Keeper, KeeperFactory};
use SchemaKeeper\Exception\{KeeperException, NotEquals};
use SchemaKeeper\Provider\PostgreSQL\SqlHelper;
use SchemaKeeper\Tests\Integration\PostgreSqlTestCase;

class KeeperTest extends PostgreSqlTestCase
{
    private Keeper $keeper;

    private FilesystemHelper $helper;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = $this->getConn();
        $params = $this->getDbParams();
        $factory = new KeeperFactory();
        $this->keeper = $factory->create($conn, $params);

        $this->helper = new FilesystemHelper();
        $this->tmpDir = sys_get_temp_dir() . '/schema_keeper_verifier_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->helper->rmDirIfExisted($this->tmpDir);

        parent::tearDown();
    }

    public function testOk(): void
    {
        $this->expectNotToPerformAssertions();

        $this->keeper->saveDump($this->tmpDir);
        $this->keeper->verifyDump($this->tmpDir);
    }

    public function testDiff(): void
    {
        $this->keeper->saveDump($this->tmpDir);
        $this->helper->rmDirIfExisted($this->tmpDir . '/structure/public/triggers');

        $catch = false;
        $execKeyword = self::getExecKeyword();

        try {
            $this->keeper->verifyDump($this->tmpDir);
        } catch (NotEquals $e) {
            $catch = true;
            $expectedTriggers = [
                'triggers' => [
                    'public.test_child.test_constraint_trigger' => 'CREATE CONSTRAINT TRIGGER test_constraint_trigger AFTER INSERT ON test_child DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_child.test_trigger_multi_event' => 'CREATE TRIGGER test_trigger_multi_event BEFORE INSERT OR UPDATE ON test_child FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_table.test_trigger' => 'CREATE TRIGGER test_trigger BEFORE UPDATE ON test_table FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_table.test_trigger_after_delete' => 'CREATE TRIGGER test_trigger_after_delete AFTER DELETE ON test_table FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_table.test_trigger_after_insert' => 'CREATE TRIGGER test_trigger_after_insert AFTER INSERT ON test_table FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_table.test_trigger_statement' => 'CREATE TRIGGER test_trigger_statement AFTER INSERT ON test_table FOR EACH STATEMENT EXECUTE ' . $execKeyword . ' trig_statement()',
                    'public.test_table.test_trigger_truncate' => 'CREATE TRIGGER test_trigger_truncate AFTER TRUNCATE ON test_table FOR EACH STATEMENT EXECUTE ' . $execKeyword . ' trig_statement() [disabled]',
                    'public.test_table.test_trigger_update_of' => 'CREATE TRIGGER test_trigger_update_of BEFORE UPDATE OF email, status ON test_table FOR EACH ROW EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_table.test_trigger_when' => 'CREATE TRIGGER test_trigger_when BEFORE UPDATE ON test_table FOR EACH ROW WHEN (old.status IS DISTINCT FROM new.status) EXECUTE ' . $execKeyword . ' trig_test()',
                    'public.test_view_check.test_view_check_insert' => 'CREATE TRIGGER test_view_check_insert INSTEAD OF INSERT ON test_view_check FOR EACH ROW EXECUTE ' . $execKeyword . ' view_insert_handler()',
                ],
            ];

            self::assertEquals('Dump and current database are not equal', $e->getMessage());
            self::assertEquals([], $e->getExpected());
            self::assertEquals($expectedTriggers, $e->getActual());
        }

        self::assertTrue($catch);
    }

    public function testEmptyDump(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Dump is empty: ' . $this->tmpDir);

        $this->keeper->verifyDump($this->tmpDir);
    }

    private static function getExecKeyword(): string
    {
        return self::getServerVersionNum() >= SqlHelper::PG_VERSION_12 ? 'FUNCTION' : 'PROCEDURE';
    }
}
