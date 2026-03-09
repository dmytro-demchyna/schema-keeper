# CLI reference

```
Usage: schemakeeper <command> <dump-dir> [options]

Example: schemakeeper dump /path/to/dump -h localhost -p 5432 -d mydb -U postgres

Available commands:
  dump         Dump database structure to dump directory
  verify       Verify database structure against dump

Connection options:
  -h, --host          Database host (required)
  -p, --port          Database port (required)
  -d, --dbname        Database name (required)
  -U, --username      Database user (required)
      --password      Database password (optional)
      --url           Connection URL (alternative to individual options)
                      Format: "postgresql://user:password@host:port/dbname"

Filter options:
      --skip-schema       Schema name to skip (repeatable, adds to defaults)
      --skip-extension    Extension name to skip (repeatable, adds to defaults)
      --skip-section      Section type to skip (repeatable)
                          Valid values: tables, views, materialized_views, types,
                          functions, triggers, sequences, procedures
      --only-schema       Include only this schema (repeatable, exclusive with --skip-schema)
      --only-section      Include only this section type (repeatable, exclusive with --skip-section)
      --no-default-skip   Disable default skip lists (only user-specified values apply)
                          Default schemas: information_schema, pg_catalog, pg_toast
                          Default temporary schemas: pg_temp_*, pg_toast_temp_*
                          Default extensions: plpgsql

      --help              Print this help message
      --version           Print version information
```

The `--url` option also accepts the `postgres://` scheme. Skipping `triggers` also removes trigger listings from table and view descriptions.
By default, CLI filtering also excludes temporary schemas. `--no-default-skip` disables that behavior too.

**Inclusive filters** (`--only-schema`, `--only-section`) are the inverse of `--skip-schema`/`--skip-section`. They are mutually exclusive with their skip counterparts but can be mixed across levels:

```bash
schemakeeper dump /path/to/dump \
  --url "postgresql://postgres@localhost:5432/mydb" \
  --only-schema public --only-schema billing \
  --only-section tables --only-section functions
```

**Full example with individual flags:**

```bash
schemakeeper dump /path/to/dump \
  -h localhost -p 5432 -d mydb -U postgres \
  --skip-section triggers --skip-section sequences
```

**Full example with URL:**

```bash
schemakeeper dump /path/to/dump \
  --url "postgresql://postgres@localhost:5432/mydb" \
  --skip-section triggers --skip-section sequences
```

**Exit codes:**

| Code | Meaning |
|------|------------|
| `0` | Success |
| `1` | Schema diff detected (dump and database differ) |
| `2` | Database connection error |
| `3` | Configuration or usage error (bad arguments, missing extensions) |

## Password handling

The `--password` option passes the password directly on the command line. When `--password` is omitted, the underlying libpq checks the `PGPASSWORD` environment variable and [`~/.pgpass`](https://www.postgresql.org/docs/current/libpq-pgpass.html) file automatically. A password can also be included in the `--url` connection string.

For CI, `PGPASSWORD` or `.pgpass` avoids exposing credentials in process listings and shell history.
