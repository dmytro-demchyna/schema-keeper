<?php

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

$params = new PSQLParameters('postgres', 5432, 'schema_keeper', 'postgres', 'postgres');

$params->setSkippedSchemas([
    'information_schema',
    'pg_%'
]);

return $params;