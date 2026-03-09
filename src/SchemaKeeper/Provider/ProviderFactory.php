<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider;

use PDO;
use SchemaKeeper\Dto\{Parameters, Section};
use SchemaKeeper\Provider\PostgreSQL\{
    ConstraintHelper,
    ExtensionDescriber,
    FunctionDescriber,
    MaterializedViewDescriber,
    PostgreSqlProvider,
    ProcedureDescriber,
    RelationTriggerHelper,
    SchemaDescriber,
    SequenceDescriber,
    SqlHelper,
    TableDescriber,
    TextTableRenderer,
    TriggerDescriber,
    TypeDescriber,
    ViewDescriber,
};
use SchemaKeeper\Exception\KeeperException;

final class ProviderFactory
{
    public function createProvider(PDO $conn, Parameters $parameters): IProvider
    {
        /** @psalm-suppress PossiblyInvalidCast */
        $driver = (string) $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'pgsql':
                return $this->createPostgreSqlProvider($conn, $parameters);
            default:
                throw new KeeperException('Unsupported PDO driver: ' . $driver);
        }
    }

    private function createPostgreSqlProvider(PDO $conn, Parameters $parameters): PostgreSqlProvider
    {
        $conn->exec('SET search_path = pg_catalog, public');

        $stmt = $conn->query('SHOW server_version_num');
        /** @phpstan-ignore-next-line */
        $versionNum = (int) $stmt->fetchColumn();

        if ($versionNum < SqlHelper::PG_VERSION_10) {
            /** @psalm-suppress PossiblyInvalidCast */
            $rawVersion = (string) $conn->getAttribute(PDO::ATTR_SERVER_VERSION);

            throw new KeeperException('PostgreSQL 10 or higher is required, got ' . $rawVersion);
        }

        $skippedSchemas = $parameters->getSkippedSchemas();
        $skippedExtensions = $parameters->getSkippedExtensions();
        $skippedSections = $parameters->getSkippedSections();
        $onlySchemas = $parameters->getOnlySchemas();
        $skipTemp = $parameters->shouldSkipTemporarySchemas();

        $sqlHelper = new SqlHelper($conn, $skippedSchemas, $skippedExtensions, $skipTemp, $onlySchemas, $versionNum);
        $tableRenderer = new TextTableRenderer();
        $triggerHelper = new RelationTriggerHelper($conn);
        $constraintHelper = new ConstraintHelper($conn);
        $schemaDescriber = new SchemaDescriber($conn, $sqlHelper);
        $extensionDescriber = new ExtensionDescriber($conn, $sqlHelper);

        $describers = [
            Section::TABLES => new TableDescriber(
                $conn,
                $sqlHelper,
                $tableRenderer,
                $triggerHelper,
                $constraintHelper,
                $skippedSections,
            ),
            Section::VIEWS => new ViewDescriber(
                $conn,
                $sqlHelper,
                $tableRenderer,
                $triggerHelper,
                $skippedSections,
            ),
            Section::MATERIALIZED_VIEWS => new MaterializedViewDescriber($conn, $sqlHelper, $tableRenderer),
            Section::TYPES => new TypeDescriber($conn, $sqlHelper, $tableRenderer, $constraintHelper),
            Section::FUNCTIONS => new FunctionDescriber($conn, $sqlHelper),
            Section::TRIGGERS => new TriggerDescriber($conn, $sqlHelper),
            Section::SEQUENCES => new SequenceDescriber($conn, $sqlHelper),
            Section::PROCEDURES => new ProcedureDescriber($conn, $sqlHelper),
        ];

        return new PostgreSqlProvider($describers, $schemaDescriber, $extensionDescriber);
    }
}
