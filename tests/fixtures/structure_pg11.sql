CREATE OR REPLACE PROCEDURE public.proc_basic(val integer)
   LANGUAGE plpgsql
AS $procedure$
BEGIN
   RAISE NOTICE 'Value: %', val;
END;
$procedure$;

CREATE OR REPLACE PROCEDURE public.proc_inout(INOUT result integer)
   LANGUAGE plpgsql
AS $procedure$
BEGIN
   result := result * 2;
END;
$procedure$;

CREATE OR REPLACE PROCEDURE public.proc_security_definer()
   LANGUAGE plpgsql
   SECURITY DEFINER
AS $procedure$
BEGIN
   RAISE NOTICE 'Current user: %', current_user;
END;
$procedure$;

CREATE OR REPLACE PROCEDURE test_schema.proc_in_schema(x integer)
   LANGUAGE plpgsql
AS $procedure$
BEGIN
   RAISE NOTICE 'x = %', x;
END;
$procedure$;
