# SchemaKeeper

[![Latest Stable Version](https://img.shields.io/packagist/v/schema-keeper/schema-keeper.svg?color=blue)](https://packagist.org/packages/schema-keeper/schema-keeper)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/schema-keeper/schema-keeper.svg?color=blue)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/postgreSQL-%3E%3D9.4-blue.svg)](https://www.postgresql.org/)
[![Build Status](https://img.shields.io/travis/com/dmytro-demchyna/schema-keeper/master.svg)](https://travis-ci.com/dmytro-demchyna/schema-keeper)
[![Coverage](https://img.shields.io/codecov/c/github/dmytro-demchyna/schema-keeper/master.svg)](https://codecov.io/gh/dmytro-demchyna/schema-keeper)

Track a structure of the your PostgreSQL database in VCS using SchemaKeeper.

SchemaKeeper provides 3 functions:
1. `save` &mdash; saves a structure of a database objects as a separate text files to a specified directory
1. `verify` &mdash; detects changes between an actual database structure and the saved via `save` one
1. `deploy` &mdash; deploys stored procedures to a database from the saved via `save` structure

You can find extra information about SchemaKeeper here: 

- [reddit](https://www.reddit.com/r/PHP/comments/btz1gi/stop_guessing_a_structure_of_your_postgresql) (en)
- [github wiki](https://github.com/dmytro-demchyna/schema-keeper/wiki/Database-continuous-integration-using-SchemaKeeper) (en)
- [habr](https://habr.com/ru/post/447746/) (ru)

## Installation

> If you choose an installation via Composer or PHAR, please install [psql](https://www.postgresql.org/docs/current/app-psql.html) app on a machines where SchemaKeeper will be used. A Docker build includes pre-installed [psql](https://www.postgresql.org/docs/current/app-psql.html).

### Composer
```bash
$ composer require schema-keeper/schema-keeper
```

### PHAR

```bash
$ wget https://github.com/dmytro-demchyna/schema-keeper/releases/latest/download/schemakeeper.phar
```

### Docker

```bash
$ docker pull dmytrodemchyna/schema-keeper
```

## Basic Usage

Create a `config.php` file:

```php
<?php

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

// Connection parameters
$params = new PSQLParameters('localhost', 5432, 'dbname', 'username', 'password');

// These schemas will be ignored
$params->setSkippedSchemas(['information_schema', 'pg_%']);

// These extensions will be ignored
$params->setSkippedExtensions(['pgtap']);

// The path to psql executable
$params->setExecutable('/bin/psql');

return $params;
```

Now you can use `schemakeeper` binary. It returns exit-code `0` on success and exit-code `1` on failure.

### save

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper save
```

After calling `save` we will get a directory, containing database structure, divided into grouped files that are easy to add to the VCS:

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

Examples of conversion to files:

Object type         | Schema         | Name                                     | Relative file path                 | File content
--------------------|----------------|------------------------------------------|------------------------------------|---------------
Table               | public         | accounts                                 | ./public/tables/accounts.txt       | Description of a table structure
Stored procedure    | public         | auth(hash bigint)                        | ./public/functions/auth(int8).sql  | Definition of a stored procedure, including the `CREATE OR REPLACE FUNCTION` block
View                | booking        | tariffs                                  | ./booking/views/tariffs.txt        | Description of a view structure

The file path stores information about the type, scheme and name of the object. This approach makes easier navigation through the dump, as well as code review of changes.

### verify

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper verify
```

Having the saved database structure, we are able to `verify` saved dump for changes that were made after `save`.

The `verify` will display information about changed objects.

An alternative way to check is to call the `save` again, specifying the same directory, and check for changes in the VCS. Since the objects from the database are stored in separate files, the VCS will show only the changed objects. The main disadvantage of this method is the need to overwrite files to see the changes.

### deploy

```bash
$ schemakeeper -c config.php -d /tmp/schema-keeper deploy
```

You can edit source code of stored procedures in the same way as the rest of the application source code. Modification of the stored procedure occurs by making changes to the corresponding file in the dump directory, which is automatically reflected in the version control system.

For example, to create a new stored procedure in the `public` schema, just create a new file with the `.sql` extension in the `public/functions` directory, place the source code of the stored procedure in it, including the `CREATE OR REPLACE FUNCTION` block, then call the `deploy`. Similarly occur changes or removal of the stored procedure. Thus, the code simultaneously enters both the VCS and the database.

The `deploy` changes the parameters of the function or the return type without additional actions, while with the classical approach it would be necessary to first perform `DROP FUNCTION`, and only then `CREATE OR REPLACE FUNCTION`.

Unfortunately, in some situations `deploy` is not able to automatically apply changes. For example, if you try to delete trigger function, that is used by at least one trigger. Such situations are solved manually with the help of migration files.

The `deploy` is responsible for transferring changes in stored procedures, but the migration files are used to transfer the remaining changes in the structure. For example, [doctrine/migrations](https://packagist.org/packages/doctrine/migrations).

Migrations must be applied before `deploy` to make changes to the structure and resolve possible problem situations.

> The `deploy` is designed to work with stored procedures written in [PL/pgSQL](https://www.postgresql.org/docs/current/plpgsql.html). Using with other languages may be less effective or impossible.


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

You can wrap `verifyDump` into a PHPUnit test:

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

You can wrap `deployDump` into a custom transaction block:

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