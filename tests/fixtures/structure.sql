DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public;
DROP SCHEMA IF EXISTS extensions CASCADE;
DROP SCHEMA IF EXISTS test_schema CASCADE;
DROP SCHEMA IF EXISTS "test~tilde" CASCADE;

CREATE SCHEMA extensions;
CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;
CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA extensions;

CREATE TABLE public.test_table (
   id bigserial PRIMARY KEY,
   values text,
   created_at timestamptz NOT NULL DEFAULT now(),
   updated_at timestamptz,
   metadata jsonb,
   tags text[],
   email text CHECK (email ~* '^.+@.+$'),
   status text DEFAULT 'pending',
   amount numeric(10,2) CHECK (amount >= 0),
   is_active boolean NOT NULL DEFAULT true,
   uuid_col uuid DEFAULT extensions.gen_random_uuid(),
   ip_address inet,
   valid_period daterange,
   duration interval,
   binary_data bytea,
   price money,
   doc xml,
   settings json,
   search_vector tsvector,
   location point,
   mac macaddr,
   flags bit varying(8),
   score real,
   precise_score double precision,
   code char(10),
   short_desc varchar(255),
   int_range int4range,
   CONSTRAINT test_table_valid_period_check CHECK (valid_period IS NULL OR NOT isempty(valid_period))
);

CREATE INDEX idx_test_table_created ON public.test_table (created_at DESC);
CREATE INDEX idx_test_table_metadata ON public.test_table USING gin (metadata);
CREATE INDEX idx_test_table_tags ON public.test_table USING gin (tags);
CREATE UNIQUE INDEX idx_test_table_email ON public.test_table (email) WHERE email IS NOT NULL;
CREATE INDEX idx_test_table_email_lower ON public.test_table (lower(email));
CREATE INDEX idx_test_table_status_created ON public.test_table (status, created_at DESC);
CREATE INDEX idx_test_table_uuid ON public.test_table (uuid_col);

COMMENT ON TABLE public.test_table IS 'Main test table for schema-keeper';
COMMENT ON COLUMN public.test_table.id IS 'Primary identifier';
COMMENT ON COLUMN public.test_table.metadata IS 'Arbitrary JSON metadata';
COMMENT ON COLUMN public.test_table.tags IS 'Array of tags';

CREATE TABLE public.test_child (
   id bigserial PRIMARY KEY,
   parent_id bigint NOT NULL REFERENCES public.test_table(id) ON DELETE CASCADE ON UPDATE CASCADE,
   name text NOT NULL,
   sort_order integer NOT NULL DEFAULT 0,
   CONSTRAINT test_child_name_not_empty CHECK (length(name) > 0),
   CONSTRAINT test_child_parent_name_unique UNIQUE (parent_id, name)
);

CREATE INDEX idx_test_child_parent ON public.test_child (parent_id);

CREATE TABLE public.test_identity (
   id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
   code text NOT NULL UNIQUE,
   description text,
   created_at timestamptz NOT NULL DEFAULT now()
);

COMMENT ON TABLE public.test_identity IS 'Table using identity column';

CREATE TABLE public.test_deferrable (
   id bigserial PRIMARY KEY,
   identity_id bigint NOT NULL,
   seq_num integer NOT NULL,
   CONSTRAINT test_deferrable_identity_fk FOREIGN KEY (identity_id)
      REFERENCES public.test_identity(id) DEFERRABLE INITIALLY DEFERRED,
   CONSTRAINT test_deferrable_unique UNIQUE (identity_id, seq_num) DEFERRABLE INITIALLY IMMEDIATE
);

CREATE TABLE public.test_exclude (
   id bigserial PRIMARY KEY,
   room_id integer NOT NULL,
   time_range tstzrange NOT NULL,
   CONSTRAINT test_exclude_no_overlap EXCLUDE USING gist (room_id WITH =, time_range WITH &&)
);

CREATE VIEW public.test_view AS SELECT * FROM public.test_table;

CREATE VIEW public.test_view_check AS
   SELECT id, values, status, is_active
   FROM public.test_table
   WHERE is_active = true
   WITH CHECK OPTION;

ALTER VIEW public.test_view_check ALTER COLUMN status SET DEFAULT 'active';

CREATE VIEW public.test_view_local_check AS
   SELECT id, values, status
   FROM public.test_view_check
   WHERE status = 'pending'
   WITH LOCAL CHECK OPTION;

CREATE VIEW public.test_view_secure
   WITH (security_barrier=true) AS
   SELECT id, email, created_at
   FROM public.test_table
   WHERE email IS NOT NULL;

CREATE VIEW public.test_view_joined AS
   SELECT
      t.id AS table_id,
      t.values AS table_values,
      c.id AS child_id,
      c.name AS child_name,
      c.sort_order,
      t.created_at,
      t.amount * 1.1 AS amount_with_tax,
      COALESCE(t.status, 'unknown') AS status_display
   FROM public.test_table t
   LEFT JOIN public.test_child c ON c.parent_id = t.id;

CREATE VIEW public.test_view_agg AS
   SELECT
      t.status,
      COUNT(*) AS total_count,
      SUM(t.amount) AS total_amount,
      AVG(t.amount) AS avg_amount,
      MIN(t.created_at) AS first_created,
      MAX(t.created_at) AS last_created
   FROM public.test_table t
   GROUP BY t.status;

COMMENT ON VIEW public.test_view_agg IS 'Aggregated statistics by status';
COMMENT ON COLUMN public.test_view_agg.total_amount IS 'Sum of all amounts';

CREATE MATERIALIZED VIEW public.test_mat_view AS SELECT * FROM public.test_table;

CREATE OR REPLACE FUNCTION public.trig_test()
   RETURNS trigger
   LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
   RETURN NEW;
END;
$function$;

CREATE TRIGGER test_trigger BEFORE UPDATE ON public.test_table FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

CREATE OR REPLACE FUNCTION public.view_insert_handler()
   RETURNS trigger
   LANGUAGE plpgsql
AS $function$
BEGIN
   INSERT INTO public.test_table (values, status, is_active)
   VALUES (NEW.values, NEW.status, NEW.is_active);
   RETURN NEW;
END;
$function$;

CREATE TRIGGER test_view_check_insert
   INSTEAD OF INSERT ON public.test_view_check
   FOR EACH ROW EXECUTE PROCEDURE public.view_insert_handler();

CREATE OR REPLACE FUNCTION public.trig_statement()
   RETURNS trigger
   LANGUAGE plpgsql
AS $function$
BEGIN
   RETURN NULL;
END;
$function$;

CREATE TRIGGER test_trigger_after_insert
   AFTER INSERT ON public.test_table
   FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

CREATE TRIGGER test_trigger_after_delete
   AFTER DELETE ON public.test_table
   FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

CREATE TRIGGER test_trigger_when
   BEFORE UPDATE ON public.test_table
   FOR EACH ROW
   WHEN (OLD.status IS DISTINCT FROM NEW.status)
   EXECUTE PROCEDURE public.trig_test();

CREATE TRIGGER test_trigger_statement
   AFTER INSERT ON public.test_table
   FOR EACH STATEMENT EXECUTE PROCEDURE public.trig_statement();

CREATE TRIGGER test_trigger_multi_event
   BEFORE INSERT OR UPDATE ON public.test_child
   FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

CREATE TRIGGER test_trigger_update_of
   BEFORE UPDATE OF email, status ON public.test_table
   FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

CREATE TRIGGER test_trigger_truncate
   AFTER TRUNCATE ON public.test_table
   FOR EACH STATEMENT EXECUTE PROCEDURE public.trig_statement();

CREATE CONSTRAINT TRIGGER test_constraint_trigger
   AFTER INSERT ON public.test_child
   DEFERRABLE INITIALLY DEFERRED
   FOR EACH ROW EXECUTE PROCEDURE public.trig_test();

ALTER TABLE public.test_table DISABLE TRIGGER test_trigger_truncate;

CREATE TYPE public.test_type AS (
   id bigint,
   values character varying
);

CREATE TYPE public.test_enum_type AS ENUM (
   'enum1',
   'enum2'
);

CREATE TYPE public.enum_single AS ENUM (
   'only_value'
);

CREATE TYPE public.enum_many AS ENUM (
   'val1',
   'val2',
   'val3',
   'val4',
   'val5',
   'val6',
   'val7',
   'val8',
   'val9',
   'val10'
);

CREATE TYPE public.enum_special AS ENUM (
   'Value With Spaces',
   'UPPERCASE',
   'lowercase',
   'MixedCase',
   'with-dash',
   'with_underscore'
);

CREATE TYPE public.composite_various AS (
   int_col integer,
   bigint_col bigint,
   smallint_col smallint,
   numeric_col numeric(10,2),
   real_col real,
   double_col double precision,
   text_col text,
   varchar_col character varying(100),
   char_col character(10),
   bool_col boolean,
   date_col date,
   time_col time without time zone,
   timetz_col time with time zone,
   timestamp_col timestamp without time zone,
   timestamptz_col timestamp with time zone,
   interval_col interval,
   uuid_col uuid,
   json_col json,
   jsonb_col jsonb,
   bytea_col bytea,
   inet_col inet,
   macaddr_col macaddr
);

CREATE TYPE public.composite_arrays AS (
   int_arr integer[],
   text_arr text[],
   bool_arr boolean[],
   numeric_arr numeric[]
);

CREATE TYPE public.composite_nested AS (
   id bigint,
   name text,
   nested_data test_type,
   created_at timestamp with time zone
);

CREATE TYPE public.composite_with_enum AS (
   id bigint,
   status test_enum_type,
   description text
);

CREATE TYPE public.test_float_range AS RANGE (
    subtype = float8,
    subtype_diff = float8mi
);

CREATE DOMAIN public.domain_basic AS integer;

CREATE DOMAIN public.domain_not_null AS text NOT NULL;

CREATE DOMAIN public.domain_with_default AS integer DEFAULT 0;

CREATE DOMAIN public.domain_with_check AS integer
    CONSTRAINT domain_with_check_positive CHECK (VALUE > 0);

CREATE DOMAIN public.domain_with_collation AS text
    COLLATE "C";

CREATE SEQUENCE public.seq_smallint
    AS smallint
    START WITH 1
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 32767
    NO CYCLE;

CREATE SEQUENCE public.seq_integer
    AS integer
    START WITH 100
    INCREMENT BY 10
    MINVALUE 1
    MAXVALUE 1000000
    NO CYCLE;

CREATE SEQUENCE public.seq_bigint_custom
    AS bigint
    START WITH 1000000
    INCREMENT BY 100
    MINVALUE 1000000
    MAXVALUE 9999999999
    NO CYCLE;

CREATE SEQUENCE public.seq_with_cycle
    AS integer
    START WITH 1
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 100
    CYCLE;

CREATE SEQUENCE public.seq_descending
    AS integer
    START WITH 1000
    INCREMENT BY -1
    MINVALUE 1
    MAXVALUE 1000
    NO CYCLE;

CREATE SEQUENCE public.seq_custom_range
    AS integer
    START WITH 500
    INCREMENT BY 5
    MINVALUE 100
    MAXVALUE 900
    NO CYCLE;

CREATE OR REPLACE FUNCTION public.test_agg_sfunc(state numeric, val numeric)
   RETURNS numeric
   LANGUAGE plpgsql
AS $function$
BEGIN
   RETURN COALESCE(state, 0) + COALESCE(val, 0);
END;
$function$;

CREATE AGGREGATE public.test_sum_agg(numeric) (
   SFUNC = public.test_agg_sfunc,
   STYPE = numeric,
   INITCOND = '0'
);

CREATE OR REPLACE FUNCTION public.func_sql_simple(a integer, b integer)
   RETURNS integer
   LANGUAGE sql
AS $function$
   SELECT a + b;
$function$;

CREATE OR REPLACE FUNCTION public.func_sql_immutable(val text)
   RETURNS text
   LANGUAGE sql
   IMMUTABLE
AS $function$
   SELECT upper(val);
$function$;

CREATE OR REPLACE FUNCTION public.func_stable()
   RETURNS timestamp with time zone
   LANGUAGE sql
   STABLE
AS $function$
   SELECT now();
$function$;

CREATE OR REPLACE FUNCTION public.func_void(msg text)
   RETURNS void
   LANGUAGE plpgsql
AS $function$
BEGIN
   RAISE NOTICE '%', msg;
END;
$function$;

CREATE OR REPLACE FUNCTION public.func_setof_int(n integer)
   RETURNS SETOF integer
   LANGUAGE sql
AS $function$
   SELECT generate_series(1, n);
$function$;

CREATE OR REPLACE FUNCTION public.func_setof_type()
   RETURNS SETOF public.test_type
   LANGUAGE sql
AS $function$
   SELECT id, values FROM public.test_table LIMIT 5;
$function$;

CREATE OR REPLACE FUNCTION public.func_returns_table(min_id bigint)
   RETURNS TABLE(id bigint, val text)
   LANGUAGE sql
AS $function$
   SELECT id, values FROM public.test_table WHERE id >= min_id;
$function$;

CREATE OR REPLACE FUNCTION public.func_out_params(a integer, b integer, OUT sum integer, OUT product integer)
   LANGUAGE plpgsql
AS $function$
BEGIN
   sum := a + b;
   product := a * b;
END;
$function$;

CREATE OR REPLACE FUNCTION public.func_inout(INOUT val integer)
   LANGUAGE plpgsql
AS $function$
BEGIN
   val := val * 2;
END;
$function$;

CREATE OR REPLACE FUNCTION public.func_variadic(VARIADIC nums integer[])
   RETURNS integer
   LANGUAGE sql
AS $function$
   SELECT sum(x)::integer FROM unnest(nums) AS x;
$function$;

CREATE OR REPLACE FUNCTION public.func_defaults(a integer, b integer DEFAULT 10, c text DEFAULT 'hello')
   RETURNS text
   LANGUAGE sql
AS $function$
   SELECT c || ': ' || (a + b)::text;
$function$;

CREATE OR REPLACE FUNCTION public.func_array(arr integer[])
   RETURNS integer[]
   LANGUAGE sql
AS $function$
   SELECT array_agg(x * 2) FROM unnest(arr) AS x;
$function$;

CREATE OR REPLACE FUNCTION public.func_custom_type(t public.test_type)
   RETURNS text
   LANGUAGE sql
AS $function$
   SELECT t.id::text || ': ' || t.values;
$function$;

CREATE OR REPLACE FUNCTION public.func_enum_param(e public.test_enum_type)
   RETURNS text
   LANGUAGE sql
AS $function$
   SELECT e::text;
$function$;

CREATE OR REPLACE FUNCTION public.func_security_definer()
   RETURNS text
   LANGUAGE sql
   SECURITY DEFINER
AS $function$
   SELECT current_user::text;
$function$;

CREATE OR REPLACE FUNCTION public.func_strict(a integer, b integer)
   RETURNS integer
   LANGUAGE sql
   STRICT
AS $function$
   SELECT a + b;
$function$;

CREATE OR REPLACE FUNCTION public.func_cost_rows(n integer)
   RETURNS SETOF integer
   LANGUAGE sql
   COST 1000
   ROWS 100
AS $function$
   SELECT generate_series(1, n);
$function$;

CREATE OR REPLACE FUNCTION public.func_parallel_safe(a integer, b integer)
   RETURNS integer
   LANGUAGE sql
   IMMUTABLE
   PARALLEL SAFE
AS $function$
   SELECT a + b;
$function$;

CREATE OR REPLACE FUNCTION public.func_set_config()
   RETURNS text
   LANGUAGE sql
   SET search_path = public
   SET statement_timeout = '5s'
AS $function$
   SELECT current_setting('search_path')::text;
$function$;

CREATE OR REPLACE FUNCTION public.func_overload(a integer)
   RETURNS integer
   LANGUAGE sql
AS $function$
   SELECT a * 2;
$function$;

CREATE OR REPLACE FUNCTION public.func_overload(a integer, b integer)
   RETURNS integer
   LANGUAGE sql
AS $function$
   SELECT a + b;
$function$;

CREATE OR REPLACE FUNCTION public.func_overload(t text)
   RETURNS text
   LANGUAGE sql
AS $function$
   SELECT upper(t);
$function$;

CREATE TABLE public.test_partitioned (
    id integer NOT NULL,
    created_date date NOT NULL,
    data text
) PARTITION BY RANGE (created_date);

CREATE TABLE public.test_part_2024 PARTITION OF public.test_partitioned
    FOR VALUES FROM ('2024-01-01') TO ('2025-01-01');

CREATE TABLE public.test_part_2025 PARTITION OF public.test_partitioned
    FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');

CREATE SCHEMA test_schema;

CREATE SCHEMA "test~tilde";

CREATE TYPE test_schema.schema_enum AS ENUM (
   'schema_val1',
   'schema_val2'
);

CREATE TYPE test_schema.schema_composite AS (
   id integer,
   data text,
   active boolean
);

CREATE DOMAIN test_schema.schema_domain AS text NOT NULL DEFAULT '';

CREATE SEQUENCE test_schema.schema_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    NO CYCLE;

CREATE SEQUENCE public."seq{curly}" AS integer START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100 NO CYCLE;

CREATE TABLE test_schema.cross_ref_child (
    id bigint PRIMARY KEY,
    parent_id bigint NOT NULL REFERENCES public.test_table(id)
);

CREATE TABLE test_schema.test_part_2026 PARTITION OF public.test_partitioned
    FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');

CREATE OR REPLACE FUNCTION test_schema.func_in_schema(x integer)
   RETURNS integer
   LANGUAGE sql
AS $function$
   SELECT x * 3;
$function$;
