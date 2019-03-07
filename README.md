# SchemaKeeper

**SchemaKeeper** is created for simplify development of PHP projects, which uses PostgreSQL database.

Detailed documentation will be available soon...

#### Example

```php
<?php
$params = new \SchemaKeeper\Provider\PostgreSQL\PSQLParameters('localhost', 5432, 'schema_keeper', 'postgres', 'postgres');
$dsn = 'pgsql:dbname=' . $params->getDbName() . ';host=' . $params->getHost();

$conn = new \PDO($dsn, $params->getUser(), $params->getPassword(), [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
]);

$keeper = new \SchemaKeeper\Keeper($conn, $params);

// Make structure dump from current database and save it in filesystem
$keeper->writeDump('/tmp/schema_keeper');

// Compare current dump with dump previously saved in filesystem
$keeper->verifyDump('/tmp/schema_keeper');

// Deploy functions from dump previously saved in filesystem
$keeper->deployDump('/tmp/schema_keeper');

```