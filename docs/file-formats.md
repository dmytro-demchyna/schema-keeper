# File format reference

Each tracked object is stored in a separate file under the dump directory. This page shows the directory layout, tracked object types, file naming conventions, and example output for each format.

## Dump directory structure

```
/path/to/dump
├── .schema-keeper                 # Safety marker (prevents overwriting wrong dirs)
├── extensions/                    # Global: extension → schema mapping
│   ├── pgcrypto.txt
│   └── btree_gist.txt
└── structure/
    ├── public/
    │   ├── .gitkeep
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
        ├── .gitkeep
        ├── tables/
        │   └── invoices.txt
        └── functions/
            └── calc_tax(numeric).sql
```

The `.schema-keeper` marker file is created automatically on the first dump. On subsequent runs, if the target directory is non-empty, `dump` checks for this marker before overwriting &mdash; preventing accidental overwrite of unrelated directories.

The dump directory is tool-owned: every `dump` run rewrites `structure/` and `extensions/` from scratch. Keep only SchemaKeeper output there.

### Tracked object types

SchemaKeeper tracks **8 object types** across all schemas:

| Object Type | File Ext | What's Inside | Example |
|---|---|---|---|
| Tables | `.txt` | Columns, indexes, constraints, foreign keys, references, partitions, triggers (like `\d` output) | [example](#tables) |
| Views | `.txt` | Column definitions + view definition query + triggers + view options | [example](#views) |
| Materialized Views | `.txt` | Column definitions + indexes + view definition query | [example](#materialized-views) |
| Functions | `.sql` | Full `CREATE OR REPLACE FUNCTION` from `pg_get_functiondef()` | [example](#functions) |
| Procedures | `.sql` | Full `CREATE OR REPLACE PROCEDURE` (PostgreSQL 11+) | [example](#procedures) |
| Triggers | `.sql` | Full `CREATE TRIGGER` from `pg_get_triggerdef()` + state label (`[disabled]`, `[replica]`, `[always]`) for non-default states | [example](#triggers) |
| Types | `.txt` | Enum values, composite structure, domain definitions, range types | [example](#types) |
| Sequences | `.txt` | Data type, bounds, increment, cycle option | [example](#sequences) |

Extensions (extension-to-schema mapping) are also tracked, stored at the top level of the dump directory ([format](#extensions)).

### File naming

- Tables, views, types, sequences: `object_name.txt`
- Functions, procedures: `function_name(arg_types).sql` &mdash; argument types use PostgreSQL internal names (e.g. `int8` instead of `bigint`). Because the signature is part of the file name, changing a function's arguments produces a file deletion + creation rather than a diff. This matches PostgreSQL semantics, where a different signature is a different function.
- Triggers: `table_name.trigger_name.sql`
- Names are filesystem-escaped when needed. Examples: `has/slash()` -> `has~Sslash().sql`, `.hidden(integer)` -> `~Dhidden(integer).sql`, `my~func(integer)` -> `my~~func(integer).sql`

## Tables

Structured text similar to PostgreSQL's `\d` output. Includes columns, indexes, constraints, foreign keys, reverse references, and triggers.

```
                               Table "public.orders"
 Column     | Type                     | Collation | Nullable | Default
------------+--------------------------+-----------+----------+-------------------------------------
 id         | bigint                   |           | not null | nextval('orders_id_seq'::regclass)
 user_id    | bigint                   |           | not null |
 status     | text                     |           | not null | 'pending'
 amount     | numeric(10,2)            |           | not null |
 created_at | timestamp with time zone |           | not null | now()
Indexes:
    "orders_pkey" PRIMARY KEY, btree (id)
    "idx_orders_user" btree (user_id)
    "idx_orders_status_created" btree (status, created_at DESC)
Check constraints:
    "orders_amount_positive" CHECK ((amount >= 0))
Foreign-key constraints:
    "orders_user_id_fkey" FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
Triggers:
    audit_trigger AFTER INSERT OR UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION audit_log()
```

## Views

Structured text with column definitions, the view definition query, trigger listings, and view options (such as `check_option`, `security_barrier`) when present.

```
                      View "public.active_users"
 Column     | Type                     | Collation | Nullable | Default | Storage
------------+--------------------------+-----------+----------+---------+----------
 id         | bigint                   |           |          |         | plain
 username   | text                     |           |          |         | extended
 email      | text                     |           |          |         | extended
 created_at | timestamp with time zone |           |          |         | plain
View definition:
 SELECT users.id,
    users.username,
    users.email,
    users.created_at
   FROM users
  WHERE users.is_active;
Options: security_barrier=true
```

## Materialized Views

Column definitions with a `Storage` and `Stats target` column, plus indexes and view definition query. Materialized views do not have trigger listings or view options.

```
                  Materialized view "public.monthly_stats"
 Column  | Type    | Collation | Nullable | Default | Storage  | Stats target
---------+---------+-----------+----------+---------+----------+-------------
 month   | date    |           |          |         | plain    |
 total   | numeric |           |          |         | main     |
 count   | bigint  |           |          |         | plain    |
Indexes:
    "monthly_stats_month_idx" btree (month)
View definition:
 SELECT date_trunc('month', created_at) AS month, ...
```

## Functions

Executable SQL &mdash; the full `CREATE OR REPLACE FUNCTION` statement from `pg_get_functiondef()`, ready to copy-paste.

```sql
CREATE OR REPLACE FUNCTION public.calculate_total(order_id bigint)
 RETURNS numeric
 LANGUAGE sql
 STABLE
AS $function$
   SELECT sum(amount) FROM order_items WHERE order_id = $1;
$function$
```

## Procedures

Same format as functions &mdash; full `CREATE OR REPLACE PROCEDURE` from `pg_get_functiondef()`. Requires PostgreSQL 11+; on older versions the procedures section is empty.

## Triggers

One-line `CREATE TRIGGER` definitions from `pg_get_triggerdef()`.

```sql
CREATE TRIGGER audit_trigger AFTER INSERT OR UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION audit_log()
```

PostgreSQL 10 renders this as `EXECUTE PROCEDURE ...`; newer versions typically render `EXECUTE FUNCTION ...`.

Non-default trigger states are appended as a label: `[disabled]`, `[replica]`, or `[always]`.

## Types

Format depends on the type kind.

**Enums** &mdash; value list:

```
{pending,confirmed,shipped,delivered,cancelled}
```

**Composite types** &mdash; structured text:

```
            Composite type "public.address"
 Column  | Type    | Collation | Nullable | Default
---------+---------+-----------+----------+--------
 street  | text    |           |          |
 city    | text    |           |          |
 country | text    |           |          |
 zip     | text    |           |          |
```

**Domain types** &mdash; structured text with base type and optional properties:

```
Domain "public.positive_integer"
Base type: integer
Not null: true
Default: 0
Check constraints:
    "positive_integer_check" CHECK ((VALUE > 0))
```

**Range types** &mdash; structured text with subtype and related properties:

```
Range type "public.float_range"
Subtype: double precision
Subtype opclass: float8_ops
Subtype diff: float8mi
```

## Sequences

Key properties in plain text.

```
Sequence "public.orders_id_seq"
Data type: bigint
Start value: 1
Min value: 1
Max value: 9223372036854775807
Increment: 1
Cycle: NO
Cache size: 1
```

## Extensions

Simple text files mapping each extension to its schema. File name is the extension name, content is the schema name.

```
extensions
```

This means the extension is installed in the `extensions` schema.
