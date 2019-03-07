<?php

namespace SchemaKeeper\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

abstract class SchemaTestCase extends TestCase
{
    /**
     * @var \PDO
     */
    private static $conn;

    protected function tearDown()
    {
        parent::tearDown();

        $this->addToAssertionCount(
            \Mockery::getContainer()->mockery_getExpectationCount()
        );

        \Mockery::close();
    }

    /**
     * @return PDO
     */
    protected static function getConn()
    {
        if (!self::$conn) {
            self::$conn = self::createConn();
        }

        return self::$conn;
    }


    /**
     * @return PSQLParameters
     */
    protected static function getDbParams()
    {
        $dbParams = new PSQLParameters('postgres', 5432, 'schema_keeper', 'postgres', 'postgres');
        $dbParams->setSkippedSchemaNames([
            'information_schema',
            'pg_%'
        ]);

        return $dbParams;
    }

    /**
     * @return PDO
     */
    private static function createConn()
    {
        $dbParams = self::getDbParams();

        $dsn = 'pgsql:dbname=' . $dbParams->getDbName() . ';host=' . $dbParams->getHost();

        $conn = new \PDO($dsn, $dbParams->getUser(), $dbParams->getPassword(), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);

        return $conn;
    }
}
