<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider\PostgreSQL;

use PDO;

class SavepointHelper
{
    /**
     * @var PDO
     */
    private $conn;

    /**
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function beginTransaction($possibleSavePointName, $isTransaction)
    {
        if ($isTransaction) {
            return $this->conn->exec('SAVEPOINT '.$possibleSavePointName);
        }

        return $this->conn->beginTransaction();
    }

    public function commit($possibleSavePointName, $isTransaction)
    {
        if ($isTransaction) {
            return $this->conn->exec('RELEASE SAVEPOINT '.$possibleSavePointName);
        }

        return $this->conn->commit();
    }

    public function rollback($possibleSavePointName, $isTransaction)
    {
        if ($isTransaction) {
            return $this->conn->exec('ROLLBACK TO SAVEPOINT '.$possibleSavePointName);
        }

        return $this->conn->rollBack();
    }
}
