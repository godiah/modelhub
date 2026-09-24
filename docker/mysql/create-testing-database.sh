#!/usr/bin/env bash
# Runs once on first container init (docker-entrypoint-initdb.d convention).
# The app connects as root (see .env DB_USERNAME), so no separate GRANT is
# needed — root already has full privileges on any database it creates.
#
# A dedicated "testing" database is required (not the app's dev database):
# phpunit.xml points tests at it via DB_DATABASE=testing, and this app's
# migrations include raw MySQL DDL (ALTER TABLE ... MODIFY ... ENUM) that
# isn't sqlite-portable, so tests must run against real MySQL, isolated
# from the dev database RefreshDatabase would otherwise wipe.
mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS testing;
EOSQL
