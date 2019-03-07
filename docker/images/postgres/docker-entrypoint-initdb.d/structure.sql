DROP DATABASE IF EXISTS schema_keeper;
CREATE DATABASE schema_keeper;

\connect schema_keeper

CREATE TABLE public.test_table (
   id bigserial primary key ,
   values text
);

CREATE view public.test_view AS SELECT * FROM public.test_table;

CREATE materialized view public.test_mat_view AS SELECT * FROM public.test_table;

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

CREATE TYPE public.test_type AS (
   id bigint,
   values character varying
);

CREATE TYPE public.test_enum_type AS ENUM (
   'enum1',
   'enum2'
);

CREATE SCHEMA test_schema;