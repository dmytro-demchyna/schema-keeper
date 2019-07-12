<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider\PostgreSQL;

use Exception;
use PDO;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Provider\IProvider;

class PSQLProvider implements IProvider
{
    /**
     * @var PDO
     */
    protected $conn;

    /**
     * @var PSQLClient
     */
    protected $psqlClient;

    /**
     * @var string[]
     */
    protected $skippedSchemaNames;

    /**
     * @var string[]
     */
    protected $skippedExtensionNames;

    /**
     * @var SavepointHelper
     */
    protected $savePointHelper;


    public function __construct(
        PDO $conn,
        PSQLClient $psqlClient,
        array $skippedSchemaNames,
        array $skippedExtensionNames
    ) {
        $this->conn = $conn;
        $this->psqlClient = $psqlClient;
        $this->skippedSchemaNames = $skippedSchemaNames;
        $this->skippedExtensionNames = $skippedExtensionNames;

        $this->savePointHelper = new SavepointHelper($conn);
    }

    public function getTables(): array
    {
        $sql = '
        SELECT concat_ws(\'.\', schemaname, tablename) AS table_path
          FROM pg_catalog.pg_tables 
          WHERE '.$this->expandLike('schemaname', $this->skippedSchemaNames).'
          
          ORDER BY table_path
         ';

        $stmt = $this->query($sql);

        $commands = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $table = $this->prepareName($row['table_path']);
            $cmd = '\d ' . $table;
            $commands[$table] = $cmd;
        }

        $actualTables = $this->psqlClient->runMultiple($commands);

        return $actualTables;
    }

    public function getViews(): array
    {
        $stmt = $this->query('
            SELECT (schemaname || \'.\' || viewname) AS view_path
            FROM pg_catalog.pg_views
            WHERE '.$this->expandLike('schemaname', $this->skippedSchemaNames).'
            ORDER BY view_path
        ');

        $commands = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $view = $this->prepareName($row['view_path']);
            $commands[$view] = '\d+ ' . $view;
        }

        $actualViews = $this->psqlClient->runMultiple($commands);

        return $actualViews;
    }

    public function getMaterializedViews(): array
    {
        $stmt = $this->query('
            SELECT (schemaname || \'.\' || matviewname) AS view_path
            FROM pg_catalog.pg_matviews
            WHERE '.$this->expandLike('schemaname', $this->skippedSchemaNames).'
            ORDER BY view_path
        ');

        $commands = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $view = $this->prepareName($row['view_path']);
            $commands[$view] = '\d+ ' . $view;
        }

        $actualViews = $this->psqlClient->runMultiple($commands);

        return $actualViews;
    }

    public function getTriggers(): array
    {
        $actualTriggers = [];

        $sql = "
            SELECT
              concat_ws('.', n.nspname, c.relname, t.tgname) as tg_path,
              pg_get_triggerdef(t.OID, true) AS tg_def
            FROM pg_trigger t
            INNER JOIN pg_class c
              ON c.OID = t.tgrelid
            INNER JOIN pg_namespace n
              ON n.OID = c.relnamespace
            WHERE
                t.tgisinternal = FALSE
            ORDER BY tg_path
        ";

        $stmt = $this->query($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $trigger = $this->prepareName($row['tg_path']);
            $definition = $row['tg_def'];

            $actualTriggers[$trigger] = $definition;
        }

        return $actualTriggers;
    }

    public function getFunctions(): array
    {
        $actualFunctions = [];

        $sql = '
            SELECT
              concat_ws(\'.\', n.nspname, p.proname) AS pro_path,
              ARRAY(
                  SELECT concat_ws(\'.\', n1.nspname, pgt.typname) as typname
                  FROM (SELECT
                          u       AS type_oid,
                          row_number()
                          OVER () AS row_number
                        FROM unnest(p.proargtypes) u) types
                    LEFT JOIN pg_type pgt ON 
                      pgt.OID = types.type_oid
                  LEFT JOIN pg_namespace n1
                    ON n1.OID = pgt.typnamespace 
                         AND n1.nspname NOT IN (\'pg_catalog\', \'public\')
                  ORDER BY types.row_number
              )                                    AS arg_types,
              pg_get_functiondef(p.oid)            AS pro_def
            FROM pg_catalog.pg_namespace n
              JOIN pg_catalog.pg_proc p
                ON p.pronamespace = n.oid
            WHERE '.$this->expandLike('n.nspname', $this->skippedSchemaNames).'
            ORDER BY pro_path
        ';

        $stmt = $this->query($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $argTypes = $row['arg_types'];
            $function = $this->prepareName($row['pro_path'] . $argTypes);
            $definition = $row['pro_def'];
            $actualFunctions[$function] = $definition;
        }

        return $actualFunctions;
    }

    public function getTypes(): array
    {
        $actualTypes = [];

        $sql = "
            SELECT 
                   concat_ws('.', n.nspname, t.typname) AS type_path,
                   t.typbyval
             FROM pg_catalog.pg_type t
             LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
             WHERE (t.typrelid = 0 OR (SELECT c.relkind = 'c'
                                       FROM pg_catalog.pg_class c
                                       WHERE c.oid = t.typrelid))
                   AND NOT EXISTS(SELECT 1
                                  FROM pg_catalog.pg_type el
                                  WHERE el.oid = t.typelem AND el.typarray = t.oid)
                   AND (".$this->expandLike('n.nspname', $this->skippedSchemaNames).")
              ORDER BY type_path
        ";

        $stmt = $this->query($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $type = $row['type_path'];
            if ($row['typbyval']) {
                $stmtEnum = $this->conn->query('select enum_range(null::' . $type . ')');
                $definition = $stmtEnum ? $stmtEnum->fetchColumn() : '';
            } else {
                $definition = $this->psqlClient->run('\d ' . $type);
            }

            $type = $this->prepareName($type);
            $actualTypes[$type] = (string) $definition;
        }

        return $actualTypes;
    }

    public function getSchemas(): array
    {
        $actualSchemas = [];

        $sql = '
            SELECT schema_name
            FROM information_schema.schemata
            WHERE '.$this->expandLike('schema_name', $this->skippedSchemaNames).'
            ORDER BY schema_name
        ';

        $stmt = $this->query($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $schema = $this->prepareName($row['schema_name']);
            $actualSchemas[$schema] = $schema;
        }

        return $actualSchemas;
    }

    public function getExtensions(): array
    {
        $actualExtensions = [];

        $sql = '
            SELECT
              ext.extname,
              nsp.nspname
            FROM pg_extension ext
              LEFT JOIN pg_namespace nsp
                ON nsp.OID = ext.extnamespace
            WHERE '.$this->expandLike('extname', $this->skippedExtensionNames).'
            ORDER BY extname;
        ';

        $stmt = $this->query($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $extension = $this->prepareName($row['extname']);
            $schema = $row['nspname'];
            $actualExtensions[$extension] = $schema;
        }

        return $actualExtensions;
    }

    public function getSequences(): array
    {
        $sql = "
            SELECT
                concat(s.sequence_schema, '.', s.sequence_name) as seq_path,
                data_type,
                start_value,
                minimum_value,
                maximum_value,
                increment,
                cycle_option
            FROM information_schema.sequences s
            ORDER BY  seq_path
        ";

        $stmt = $this->query($sql);

        $actualSequences = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $sequence = $this->prepareName($row['seq_path']);
            $actualSequences[$sequence] = (string) json_encode($row, JSON_PRETTY_PRINT);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Json error: ' . json_last_error_msg());
            }
        }

        return $actualSequences;
    }

    public function createFunction(string $definition): void
    {
        $this->conn->exec($definition);
    }

    public function deleteFunction(string $name): void
    {
        $sqlDelete = 'DROP FUNCTION ' . $name;
        $this->conn->exec($sqlDelete);
    }

    public function changeFunction(string $name, string $definition): void
    {
        $isTransaction = $this->conn->inTransaction();

        try {
            $this->savePointHelper->beginTransaction('before_change', $isTransaction);
            $this->conn->exec($definition);
            $this->savePointHelper->commit('before_change', $isTransaction);
        } catch (Exception $e) {
            $this->savePointHelper->rollback('before_change', $isTransaction);
            $this->conn->exec('DROP FUNCTION ' . $name);
            $this->conn->exec($definition);
        }
    }

    private function prepareName(string $name): string
    {
        return str_replace(['{', '}'], ['(', ')'], $name);
    }

    private function expandLike(string $columnName, array $patterns): string
    {
        $sql = '';

        foreach ($patterns as $pattern) {
            $sql .= " AND $columnName NOT LIKE '$pattern'";
        }

        if (!$sql) {
            $sql = ' AND TRUE';
        }

        $sql = trim($sql, " AND");

        return $sql;
    }

    private function query(string $sql): \PDOStatement
    {
        $stmt = $this->conn->query($sql);

        if ($stmt === false) {
            throw new KeeperException(print_r($this->conn->errorInfo(), true));
        }

        return $stmt;
    }
}
