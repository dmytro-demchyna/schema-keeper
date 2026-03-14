# SchemaKeeper

[![CI](https://github.com/dmytro-demchyna/schema-keeper/actions/workflows/ci.yml/badge.svg)](https://github.com/dmytro-demchyna/schema-keeper/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/schema-keeper/schema-keeper.svg?color=blue)](https://packagist.org/packages/schema-keeper/schema-keeper)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://php.net/)
[![Minimum PostgreSQL Version](https://img.shields.io/badge/postgreSQL-%3E%3D10-blue.svg)](https://www.postgresql.org/)
[![License](https://img.shields.io/packagist/l/schema-keeper/schema-keeper.svg)](https://packagist.org/packages/schema-keeper/schema-keeper)

**Track your PostgreSQL database structure in a version control system.**

SchemaKeeper is a read-only tool that saves each tracked schema object as a separate file, so schema changes become small, reviewable diffs:

```bash
schemakeeper dump /path/to/dump     # dump structure to files
schemakeeper verify /path/to/dump   # verify files against database
```

```
/path/to/dump
├── extensions/
│   ├── pgcrypto.txt
│   └── btree_gist.txt
└── structure/
    ├── public/
    │   ├── tables/
    │   │   ├── users.txt
    │   ├── views/
    │   │   └── active_users.txt
    │   ├── materialized_views/
    │   │   └── monthly_stats.txt
    │   ├── functions/
    │   │   └── validate_email(text).sql
    │   ├── procedures/
    │   │   └── refresh_cache(int4).sql
    │   ├── triggers/
    │   │   └── orders.audit_trigger.sql
    │   ├── types/
    │   │   └── order_status.txt
    │   └── sequences/
    │       └── orders_id_seq.txt
    └── billing/
        ├── tables/
        │   └── invoices.txt
        └── functions/
            └── calc_tax(numeric).sql
```

Run `verify` in CI to catch drift before it reaches production &mdash; SchemaKeeper complements your migration tool, it doesn't replace it.

## Why not just migrations?

- **Untracked changes**: A teammate runs `ALTER TABLE` directly in production. Migrations won't catch it. SchemaKeeper will.
- **Environment drift**: Staging has an extra index, dev is missing a trigger. You only find out when something breaks. SchemaKeeper surfaces every difference.
- **Schema review in PRs**: Migrations show *what you intended*. SchemaKeeper shows *what actually happened* &mdash; every column, constraint, and function definition. All reviewable in a normal `git diff`.

## How is this different from `pg_dump -s`?

`pg_dump -s` produces a single monolithic file where small changes create noisy diffs &mdash; objects shift around, unrelated sections move, and reviewers scroll through walls of text to find the one thing that actually changed.

SchemaKeeper is built specifically for Git + CI:

- **Focused files.** Most tracked objects are stored separately, so diffs stay small and focused. Table-local indexes, constraints, partitions, and trigger listings stay with their parent table or view.
- **Deterministic output.** Identical database state produces identical files, so `git diff` reflects real changes, not reordering noise.
- **Drift detection.** `schemakeeper verify` compares a live database against the committed snapshot and prints unified diffs. Exit code `1` on mismatch.

In short: `pg_dump -s` is for *recreating* schemas; SchemaKeeper is for *tracking and reviewing* them.

## Installation

### Requirements

- PHP >= 7.4
- `ext-pdo` + `ext-pdo_pgsql`
- PostgreSQL 10+

### Composer

```bash
composer require schema-keeper/schema-keeper
```

### PHAR

```bash
wget https://github.com/dmytro-demchyna/schema-keeper/releases/latest/download/schemakeeper.phar
chmod +x schemakeeper.phar
./schemakeeper.phar --version
```

> **Note:** Examples below use `schemakeeper` as the command name.
> Replace with `vendor/bin/schemakeeper` or `./schemakeeper.phar` depending on your installation method.

## Quick start

**1. Dump your database**

```bash
schemakeeper dump /path/to/dump -h localhost -p 5432 -d mydb -U postgres
```

If the database requires a password, see [Password handling](docs/cli-reference.md#password-handling).

**2. Commit the result**

```bash
git add /path/to/dump
git commit -m "Add database structure dump"
```

**3. Add verification to CI**

Add `verify` to your CI pipeline (against the test database, after applying migrations). This ensures every migration is accompanied by an up-to-date dump:

```yaml
- name: Verify database structure
  run: schemakeeper verify /path/to/dump -h localhost -p 5432 -d mydb -U postgres
  env:
    PGPASSWORD: ${{ secrets.DB_PASSWORD }}
```

Prefer PHPUnit over CLI? See [PHPUnit integration](docs/phpunit-integration.md) to run verification as a test.

**4. Monitor production for drift**

Run `verify` against your production database on a schedule to catch untracked DDL &mdash; the `ALTER TABLE` someone ran directly without a migration:

```bash
schemakeeper verify /path/to/dump -h prod-host -p 5432 -d mydb -U postgres
```

`verify` exits with code `1` on mismatch. Set it up as a cron job or a post-deployment step on any machine with database access.

## What a failed verify looks like

When the database doesn't match the committed dump, `schemakeeper verify` prints unified diffs for every difference:

```diff
--- functions/public.func_sql_simple(integer, integer)
+++ functions/public.func_sql_simple(integer, integer)
@@ @@
 CREATE OR REPLACE FUNCTION public.func_sql_simple(a integer, b integer)
  RETURNS integer
  LANGUAGE sql
+ IMMUTABLE
 AS $function$
-   SELECT a + b;
+   SELECT a * b;
 $function$

--- /dev/null
+++ triggers/public.test_table.notify_on_update
@@ @@
+CREATE TRIGGER notify_on_update AFTER UPDATE ON test_table FOR EACH ROW EXECUTE FUNCTION trig_test()

--- types/public.test_enum_type
+++ types/public.test_enum_type
@@ @@
-{enum1,enum2}
+{enum1,enum2,enum3}
```

The diff shows:
- `func_sql_simple` gained `IMMUTABLE` and its body changed from `a + b` to `a * b`
- `test_table` got a new trigger `notify_on_update`
- `test_enum_type` got a new enum value `enum3`

## Dump directory structure

See [File format reference](docs/file-formats.md) for tracked object types, file naming, and example output.

## PHPUnit integration

SchemaKeeper can also run as a PHPUnit test that fails on schema drift. See [PHPUnit integration](docs/phpunit-integration.md) for setup instructions.

## CLI reference

See [CLI reference](docs/cli-reference.md) for the full list of options, filter flags, exit codes, and password handling.

## Recommended workflow

### Resolving merge conflicts

Different objects live in separate files, so changes to different objects auto-merge without conflicts.

When two branches modify the **same object**:

1. Merge the branch as usual
2. Accept either side of each conflict (`--ours` or `--theirs`)
3. Apply all migrations from both branches to your local database
4. Run `schemakeeper dump`
5. Commit the result

> The choice in step 2 doesn't matter &mdash; step 4 overwrites the files with the correct state.

### When verify fails

A failing `verify` means the database doesn't match the committed dump.

| Cause | Fix |
|-------|-----|
| Forgot to dump after migration | Run `dump` and commit the updated files |
| Untracked DDL ran directly on database | Create a migration (or revert the change), then re-dump |
| Stale dump after merge | Re-apply migrations and re-dump (see above) |
| Environment-specific object | Exclude it with `--skip-schema` or `--skip-section` |

## Limitations

**Not tracked:**
- RLS policies
- Roles/permissions (GRANT/REVOKE)
- Rules
- Foreign data wrappers
- Publications/subscriptions
- Event triggers
- Operators and operator classes
- Aggregate and window functions
- Multirange types
- Comments (`COMMENT ON`)

**Procedures** require PostgreSQL 11+. On older versions, the procedures section is empty.

**Cross-version formatting:** Dumps are deterministic within a PostgreSQL major version, but formatting may differ across major versions (e.g., trigger syntax, `pg_get_viewdef()` output).

## Contributing

Contributions are welcome. Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for guidelines.

## License

MIT &mdash; see [LICENSE](LICENSE) for details.
