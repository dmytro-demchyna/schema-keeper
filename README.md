# SchemaKeeper

[![Latest Stable Version](https://img.shields.io/packagist/v/schema-keeper/schema-keeper.svg?color=blue)](https://packagist.org/packages/schema-keeper/schema-keeper)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/schema-keeper/schema-keeper.svg?color=blue)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/postgreSQL-%3E%3D9.4-blue.svg)](https://www.postgresql.org/)
[![Build Status](https://img.shields.io/travis/com/dmytro-demchyna/schema-keeper/master.svg)](https://travis-ci.com/dmytro-demchyna/schema-keeper)
[![Coverage](https://img.shields.io/codecov/c/github/dmytro-demchyna/schema-keeper/master.svg)](https://codecov.io/gh/dmytro-demchyna/schema-keeper)

Please, read [wiki](https://github.com/dmytro-demchyna/schema-keeper/wiki/Database-continuous-integration-using-SchemaKeeper) to better understand SchemaKeeper goals.

## Installation

```bash
$ composer require schema-keeper/schema-keeper
```

> You must install [psql](https://www.postgresql.org/docs/current/app-psql.html) on the machines where SchemaKeeper will be used.

## CLI Usage

First create the `config.php` file:

```php
<?php

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

$params = new PSQLParameters('localhost', 5432, 'dbname', 'username', 'password');
$params->setSkippedSchemas(['information_schema', 'pg_%']);

return $params;
```

SchemaKeeper's binary provides 3 functions:
1. save
1. verify
1. deploy

### save

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper save
```

The `save` writes a dump of the current database to the specified folder. 
 
After calling the contents of the `/tmp/schema_keeper` will be as follows:

```
/tmp/schema_keeper:
    extensions:
        plpgsql.txt
        ...
    structure:
        public:
            functions:
                func1.sql
                ...
            materialized_views:
                mat_view1.txt
                ...
            sequences:
                sequence1.txt
                ...
            tables:
                table1.txt
                ...
            triggers:
                trigger1.sql
                ...
            types:
                type1.txt
                ...
            views:
                view1.txt
                ...
        another_schema:
            tables:
                table3.txt
                ...
            sequences:
                sequence3.txt
                ...
        ...
```

### verify

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper verify
```

The `verify` detects changes between the current database structure and saved dump. If there are no changes, `verify` will finished with exit-code 0, otherwise &mdash; with exit-code 1 and information about changed objects.

### deploy

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper deploy
```

The `deploy` automatically deploys changes in stored procedures to the actual database in accordance with the saved dump and print names of affected functions. If deployment successful, it will finished with exit-code 0, otherwise &mdash; with exit-code 1 and information about error.

You can change a source code of stored procedures directly in dump files and then deploy changes to your database. The `deploy` works exclusively with stored procedures. Other changes in the database structure must be deployed in the classical way &mdash; through migration files.

## Extended usage

You can inject SchemaKeeper to your own code.

```php
<?php

use SchemaKeeper\Keeper;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

$host = 'localhost';
$port = 5432;
$dbName = 'dbname';
$user = 'username';
$password = 'password';

$dsn = 'pgsql:dbname=' . $dbName . ';host=' . $host.';port='.$port;
$conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$params = new PSQLParameters($host, $port, $dbName, $user, $password);
$keeper = new Keeper($conn, $params);
```

```php
<?php

$keeper->saveDump('path_to_dump');
$keeper->verifyDump('path_to_dump');
$keeper->deployDump('path_to_dump');
```

You can wrap `verifyDump` into the PHPUnit test:

```php
<?php

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    function testOk()
    {
        // Initialize $keeper here...
        
        try {
            $keeper->verifyDump('/path_to_dump');
        } catch (\SchemaKeeper\Exception\NotEquals $e) {
            $expectedFormatted = print_r($e->getExpected(), true);
            $actualFormatted = print_r($e->getActual(), true);

            // assertEquals will show the detailed diff between the saved dump and actual database
            self::assertEquals($expectedFormatted, $actualFormatted);
        }
    }
}

```

You can wrap `deployDump` into transaction block:

```php
<?php

// Initialize $conn and $dbParams here...

$keeper = new \SchemaKeeper\Keeper($conn, $dbParams);

$conn->beginTransaction();

try {
    $result = $keeper->deployDump('/path_to_dump');
    
    // $result->getDeleted() - these functions were deleted from the current database
    // $result->getCreated() - these functions were created in the current database
    // $result->getChanged() - these functions were changed in the current database

    $conn->commit();
} catch (\Exception $e) {
    $conn->rollBack();
}
```

## Contributing
Please refer to [CONTRIBUTING.md](https://github.com/dmytro-demchyna/schema-keeper/blob/master/.github/CONTRIBUTING.md) for information on how to contribute to SchemaKeeper.