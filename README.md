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
```php
<?php

$keeper->saveDump('path_to_dump');
$keeper->verifyDump('path_to_dump');
$keeper->deployDump('path_to_dump');
```

### saveDump
`saveDump` writes a dump of the current database to the specified folder. For example, after calling 

```php
<?php

$keeper->saveDump('/tmp/schema_keeper');
```
 
the contents of the `/tmp/schema_keeper` folder will be as follows:

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

### verifyDump
`verifyDump` checks whether the database structure has changed after the dump has been saved. 

For example:
```php
<?php

$result = $keeper->verifyDump('/path_to_dump');

if ($result['expected'] !== $result['actual']) {
    echo 'There are changes...';
}
```

You can wrap `verifyDump` into the PHPUnit test:

```php
<?php

class SchemaTest extends \PHPUnit\Framework\TestCase
{
    function testOk()
    {
        // Initialize $keeper here...
        
        $result = $keeper->verifyDump('/path_to_dump');

        if ($result['expected'] !== $result['actual']) {
            $expectedFormatted = print_r($result['expected'], true);
            $actualFormatted = print_r($result['actual'], true);

            // assertEquals will show the detailed diff between the saved dump and actual database
            self::assertEquals($expectedFormatted, $actualFormatted);
        }

        self::assertTrue(true);
    }
}

```

### deployDump

The `deployDump` function automatically deploys changes in stored procedures to the actual database in accordance with the saved dump. 

The `deployDump` works exclusively with stored procedures. Other changes in the database structure must be deployed in the classical way - through migrations.

Example:

```php
<?php

// Initialize $keeper here...

$result = $keeper->deployDump('/path_to_dump');

print_r($result['deleted']); // These functions were deleted from the current database
print_r($result['created']); // These functions were created in the current database
print_r($result['changed']); // These functions were changed in the current database

if($result['expected'] !== $result['actual']) {
    throw new \Exception('Deploy failure');
}
```

You can wrap `deployDump` into transaction block:

```php

// Initialize $conn and $dbParams here...

$keeper = new Keeper($conn, $dbParams);

$conn->beginTransaction();

try {
    $result = $keeper->deployDump('/path_to_dump');

    if($result['expected'] !== $result['actual']) {
        throw new \Exception('Deploy failure');
    }

    echo "Success\n";

    $conn->commit();
} catch (\Exception $e) {
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

## Contributing
Please refer to [CONTRIBUTING.md](https://github.com/dmytro-demchyna/schema-keeper/blob/master/.github/CONTRIBUTING.md) for information on how to contribute to SchemaKeeper.