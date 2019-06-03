# SchemaKeeper

[![Latest Stable Version](https://img.shields.io/packagist/v/schema-keeper/schema-keeper.svg?color=blue)](https://packagist.org/packages/schema-keeper/schema-keeper)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/schema-keeper/schema-keeper.svg?color=blue)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/postgreSQL-%3E%3D9.4-blue.svg)](https://www.postgresql.org/)
[![Build Status](https://img.shields.io/travis/com/dmytro-demchyna/schema-keeper/master.svg)](https://travis-ci.com/dmytro-demchyna/schema-keeper)
[![Coverage](https://img.shields.io/codecov/c/github/dmytro-demchyna/schema-keeper/master.svg)](https://codecov.io/gh/dmytro-demchyna/schema-keeper)

Track a structure of your PostgreSQL database in a VCS using SchemaKeeper.

SchemaKeeper provides 3 functions:
1. `save` &mdash; saves a database structure as separate text files to a specified directory
1. `verify` &mdash; detects changes between an actual database structure and the saved one
1. `deploy` &mdash; deploys stored procedures to a database from the saved structure

You can find extra information about SchemaKeeper here: 

- [reddit](https://www.reddit.com/r/PHP/comments/btz1gi/stop_guessing_a_structure_of_your_postgresql) (en)
- [github wiki](https://github.com/dmytro-demchyna/schema-keeper/wiki/Database-continuous-integration-using-SchemaKeeper) (en)
- [habr](https://habr.com/ru/post/447746/) (ru)

Table of contents:
- [Installation](#installation)
    - [Composer](#composer)
    - [PHAR](#phar)
    - [Docker](#docker)
- [Basic usage](#basic-usage)
    - [save](#save)
    - [verify](#verify)
    - [deploy](#deploy)
- [Extended usage](#extended-usage)
    - [PHPUnit](#phpunit)
    - [Custom transaction block](#custom-transaction-block)
- [Contributing](#contributing)

## Installation

> If you choose the installation via Composer or PHAR, please, install [psql](https://www.postgresql.org/docs/current/app-psql.html) app on machines where SchemaKeeper will be used. A Docker build includes pre-installed [psql](https://www.postgresql.org/docs/current/app-psql.html).

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

Now you can use the `schemakeeper` binary. It returns exit-code `0` on success and exit-code `1` on failure.

### save

```bash
$ schemakeeper -c config.php -d /project_path/db_name save
```

The command above saves a database structure to a `/project_path/db_name` directory. 

- /project_path/db_name:
    - structure:
        - public:
            - functions:
                - func1(int8).sql
            - materialized_views:
                - mat_view1.txt
            - sequences:
                - sequence1.txt
            - tables:
                - table1.txt
            - triggers:
                - trigger1.sql
            - types:
                - type1.txt
            - views:
                - view1.txt
        - schema2:
            - views:
                - view2.txt
        - ...
    - extensions:
        - plpgsql.txt

Examples of conversion database structure to files:

Object type         | Schema         | Name                                     | Relative file path                 | File content
--------------------|----------------|------------------------------------------|------------------------------------|---------------
Table               | public         | table1                                   | ./public/tables/table1.txt         | A description of the table structure
Stored procedure    | public         | func1(param bigint)                      | ./public/functions/func1(int8).sql | A definition of the stored procedure, including a `CREATE OR REPLACE FUNCTION` block
View                | schema2        | view2                                    | ./schema2/views/view2.txt          | A description of the view structure
...                 | ...            | ...                                      | ...                                | ...

The file path stores information about a type, a scheme and a name of a object. This approach makes an easier navigation through the database structure, as well as code review of changes in VCS.

### verify

```bash
$ schemakeeper -c config.php -d /project_path/db_name verify
```

The command above compares an actual database structure with the previously saved in `/project_path/db_name` one and displays an information about changed objects.

If changes exists, the `verify` will returns an exit-code `1`.

An alternative way to find changes is to call the `save` again, specifying the same directory `/project_path/db_name`, and check changes in the VCS. Since objects from the database are stored in separate files, the VCS will show only changed objects. A main disadvantage of this way &mdash; a need to overwrite files.

### deploy

```bash
$ schemakeeper -c config.php -d /project_path/db_name deploy
```

The command above deploys stored procedures from the `/project_path/db_name` to the actual database.

You can edit a source code of stored procedures in the same way as a rest of an application source code. Modification of a stored procedure occurs by making changes to the corresponding file in the `/project_path/db_name` directory, which is automatically reflected in the VCS.

For example, to create a new stored procedure in the `public` schema, just create a new file with a `.sql` extension in the `/project_path/db_name/structure/public/functions` directory, place a source code of the stored procedure into it, including a `CREATE OR REPLACE FUNCTION` block, then call the `deploy`. Similarly occur modifying or removal of stored procedures. Thus, the code simultaneously enters both the VCS and the database.

The `deploy` changes parameters of a function or a return type without additional actions, while with a classical approach it would be necessary to first perform `DROP FUNCTION`, and only then `CREATE OR REPLACE FUNCTION`.

Unfortunately, in some situations `deploy` is not able to automatically apply changes. For example, if you try to delete a trigger function, that is used by at least one trigger. Such situations must be solved manually using migration files.

The `deploy` transfers changes only from stored procedures. To transfer other changes, please, use migration files (for example, [doctrine/migrations](https://packagist.org/packages/doctrine/migrations)).

Migrations must be applied before the `deploy` to resolve possible problem situations.

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

### PHPUnit

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

### Custom transaction block

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