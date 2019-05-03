#!/usr/bin/env bash
docker-compose exec -T postgres bash <<'EOF'
export PGPASSWORD=postgres \
&& echo "SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = 'schema_keeper'   AND pid <> pg_backend_pid();" | psql -hpostgres -Upostgres \
&& for f in /docker-entrypoint-initdb.d/*; do psql -hpostgres -Upostgres < "$f"; done
EOF