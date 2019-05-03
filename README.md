# SchemaKeeper

[![Minimum PHP Version](https://img.shields.io/badge/PHP-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/PostgreSQL-%3E%3D%209.4-8892BF.svg?style=flat-square)](https://www.postgresql.org/)

Please, read [this article](https://github.com/dmytro-demchyna/schema-keeper/wiki/Database-continuous-integration-using-SchemaKeeper) to better understand SchemaKeeper goals.

## Installation

```
$ composer require schema-keeper/schema-keeper
```

## Specification
**SchemaKeeper**  provides 3 functions:
* `$keeper->saveDump('path_to_dump')`
* `$keeper->verifyDump('path_to_dump')`
* `$keeper->deployDump('path_to_dump')`

### saveDump
`saveDump` writes a dump of the current database to the specified folder. For example, after calling `$keeper->saveDump('/tmp/schema_keeper')` the contents of the `/tmp/schema_keeper` folder will be as follows:

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

In the folder `structure`, folders are created for each scheme that exists in the database. In each of the schema folders, folders will be created for each section of the database structure (tables, triggers etc). For example, the `functions` folder will contain
files with the source code of each stored procedure from the current schema, and the `tables` folder contains files with the structure and relations of each table.

### verifyDump
After the dump is saved with `saveDump` function, the current state of the database becomes stored in the file system, so
it becomes possible to check whether the database structure has changed after the dump has been saved. For this purpose is provided
function `verifyDump`. For example, consider checking for changes using PHPUnit:

```php
<?php

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    function testOk()
    {
        // Initialize $conn and $dbParams here...
        
        $keeper = new \SchemaKeeper\Keeper($conn, $dbParams);
        $result = $keeper->verifyDump('/path_to_dump');

        if ($result['expected'] !== $result['actual']) {
            $expectedFormatted = print_r($result['expected'], true);
            $actualFormatted = print_r($result['actual'], true);

            self::assertEquals($expectedFormatted, $actualFormatted);
        }

        self::assertTrue(true);
    }
}

```

In case of inconsistency between the current database structure and the saved dump, assertEquals will show the difference.

### deployDump

The `deployDump` function automatically adjusts the stored procedures of the database in accordance with the dump.

`deployDump` works exclusively with stored procedures. Other changes in the database structure must be deployed in the classical way - through migrations.

Script that calls `deployDump` and displays the result:

```php
<?php

// Initialize $conn and $dbParams here...

$keeper = new Keeper($conn, $dbParams);

$conn->beginTransaction();

try {
    $result = $keeper->deployDump('/path_to_dump');

    foreach ($result['deleted'] as $nameDeleted) {
        echo "Deleted $nameDeleted\n";
    }

    foreach ($result['created'] as $nameCreated) {
        echo "Created $nameCreated\n";
    }

    foreach ($result['changed'] as $nameChanged) {
        echo "Changed $nameChanged\n";
    }

    if($result['expected'] !== $result['actual']) {
        throw new \Exception('Deploy failure');
    }

    echo "Schema sync successful\n";

    $conn->commit();
}
catch (\Exception $e) {
    $conn->rollBack();

    echo "$e\n";
}
```

## Configuration
> You must install `postgresql-client` on the machines where SchemaKeeper will be used, since the [psql](https://www.postgresql.org/docs/current/app-psql.html) is used to interact with the database in some cases.

```php
<?php

use SchemaKeeper\Keeper;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

$params = new PSQLParameters('localhost', 5432, 'dbname', 'username', 'password');
$dsn = 'pgsql:dbname=' . $params->getDbName() . ';host=' . $params->getHost();
$conn = new \PDO($dsn, $params->getUser(), $params->getPassword(), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

$keeper = new Keeper($conn, $params);
```