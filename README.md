# SchemaKeeper

[![Latest Stable Version](https://img.shields.io/packagist/v/schema-keeper/schema-keeper.svg?color=blue)](https://packagist.org/packages/schema-keeper/schema-keeper)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/schema-keeper/schema-keeper.svg?color=blue)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/postgreSQL-%3E%3D9.4-blue.svg)](https://www.postgresql.org/)
[![Build Status](https://img.shields.io/travis/com/dmytro-demchyna/schema-keeper/master.svg)](https://travis-ci.com/dmytro-demchyna/schema-keeper)
[![Coverage](https://img.shields.io/codecov/c/github/dmytro-demchyna/schema-keeper/master.svg)](https://codecov.io/gh/dmytro-demchyna/schema-keeper)

Track structure of your PostgreSQL database in VCS using SchemaKeeper.

SchemaKeeper provides 3 functions:
1. `save` &mdash; saves the structure dump of database objects as separate text files to the specified folder
1. `verify` &mdash; detects changes between the current database structure and saved dump
1. `deploy` &mdash; deploys changes in stored procedures to the actual database in accordance with the saved dump 

You can find more information about SchemaKeeper's workflow in the [wiki](https://github.com/dmytro-demchyna/schema-keeper/wiki/Database-continuous-integration-using-SchemaKeeper).

## Installation

```bash
$ composer require schema-keeper/schema-keeper
```

> You must install [psql](https://www.postgresql.org/docs/current/app-psql.html) on the machines where SchemaKeeper will be used.

## Basic Usage

Create the `config.php` file:

```php
<?php

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

$params = new PSQLParameters('localhost', 5432, 'dbname', 'username', 'password');
$params->setSkippedSchemas(['information_schema', 'pg_%']);

return $params;
```

Now you can interact with `schemakeeper` binary.

### save

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper save
```

The contents of the `/tmp/schema_keeper` (after calling `save`) will be as follows:

```
/tmp/schema_keeper:
    structure:
        public:
            functions:
                auth(int8).sql
                ...
            materialized_views:
                mat_view1.txt
                ...
            sequences:
                sequence1.txt
                ...
            tables:
                accounts.txt
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
        booking:
            views:
                tariffs.txt
                ...
        ...
    extensions:
        plpgsql.txt
        ...
```

As a result, we have a directory, containing database structure, divided into grouped files that are easy to add to the VCS.

Examples of conversion objects to files:

Object type         | Schema         | Name                                     | Relative file path
--------------------|----------------|------------------------------------------|--------------------------------------------
Table               | public         | accounts                                 | ./public/tables/accounts.txt
Stored procedure    | public         | auth(hash bigint)                        | ./public/functions/auth(int8).sql
View                | booking        | tariffs                                  | ./booking/views/tariffs.txt

As can be seen from the table above, the path to the file stores information about the type, scheme and name of the object. This approach makes easier navigation through the dump, as well as code review of changes.

File content is a textual representation of the structure of the specific database object. For example, the contents of a file for stored procedure will be it's complete definition, starting with the `CREATE OR REPLACE FUNCTION` block.

### verify

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper verify
```

Having the saved dump of the current database structure, we are able to check whether changes have been made to the database structure after creating the dump.

If there are no changes, `verify` will finished with exit-code 0, otherwise &mdash; with exit-code 1 and print information about changed objects.

An alternative way to check is to call the `save` again, specifying the same directory, and check for changes in the VCS. Since the objects from the database are stored in separate files, the VCS will show only the changed objects. The main disadvantage of this method is the need to overwrite files to see the changes.

### deploy

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper deploy
```

If deployment successful, command will finished with exit-code 0 and print names of affected functions, otherwise &mdash; with exit-code 1 and print information about error.

> The `deploy` is designed to work with stored procedures written in [PL/pgSQL](https://www.postgresql.org/docs/current/plpgsql.html). Using with other languages may be less effective or impossible.

You can edit source code of stored procedures in the same way as the rest of the application source code using the `deploy`. Modification of the stored procedure occurs by making changes to the corresponding file, which is automatically reflected in the version control system.

For example, to create a new stored procedure in the `public` schema, just create a new file with the `.sql` extension in the `public/functions` directory, place the source code of the stored procedure in it, including the `CREATE OR REPLACE FUNCTION` block, then call the `deploy`. Similarly occur changes or removal of the stored procedure. Thus, the code simultaneously enters both the VCS and the database.

> When creating a new stored procedure, there is no need to manually enter the correct file name. It is enough that the file has the extension `.sql`. The correct name can be obtained from output of the `deploy`, and used to rename the file.

The `deploy` changes the parameters of the function or the return type without additional actions, while with the classical approach it would be necessary to first perform `DROP FUNCTION`, and only then `CREATE OR REPLACE FUNCTION`.

If an error appears in the source code of the stored procedure, the `deploy` fails, displaying an error. The divergence between the dump and the current database for stored procedures is not possible if you use `deploy` on a permanent basis.

Unfortunately, in some situations `deploy` is not able to automatically apply changes. For example, if you try to delete trigger function, that is used by at least one trigger. Such situations are solved manually with the help of migration files.

If the `deploy` is responsible for transferring changes in stored procedures, then the migration files are used to transfer the remaining changes in the structure. For example, [doctrine/migrations](https://packagist.org/packages/doctrine/migrations) will do.

Migrations must be applied before `deploy` starts to make changes to the structure and resolve possible problem situations.

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