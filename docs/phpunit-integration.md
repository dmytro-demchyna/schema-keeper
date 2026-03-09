# PHPUnit integration

SchemaKeeper can also be used as a PHP library. Add a test that fails when the database differs from the committed dump. `KeeperFactory::createWithDefaults()` matches the CLI defaults, including temporary schema filtering. The `NotEquals` exception carries structured expected/actual data, so PHPUnit renders a clear diff of what changed:

```php
use PHPUnit\Framework\TestCase;
use SchemaKeeper\Exception\NotEquals;
use SchemaKeeper\KeeperFactory;

class DatabaseSchemaTest extends TestCase
{
    public function testDatabaseMatchesDump(): void
    {
        $dsn = 'pgsql:host=localhost;port=5432;dbname=mydb';
        $conn = new PDO($dsn, 'postgres', 'secret', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $keeper = (new KeeperFactory())->createWithDefaults($conn);

        try {
            $keeper->verifyDump('/path/to/dump');
        } catch (NotEquals $e) {
            $expectedFormatted = print_r($e->getExpected(), true);
            $actualFormatted = print_r($e->getActual(), true);

            self::assertEquals($expectedFormatted, $actualFormatted);
        }
    }
}
```
