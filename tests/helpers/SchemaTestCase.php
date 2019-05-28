<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

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
        $dbParams = include '/data/.dev/keeper-config.php';

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
