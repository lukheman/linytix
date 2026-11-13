#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$APP_ROOT/.env}"
RTO_SECONDS="${RTO_SECONDS:-14400}"

if [[ $# -lt 1 ]]; then
    echo "Pemakaian: $0 <file-backup.sql.gz|file-backup.sqlite.gz> [nama-db-drill]" >&2
    exit 1
fi

BACKUP_FILE="$1"
DRILL_DB="${2:-}"

if [[ ! -f "$BACKUP_FILE" ]]; then
    echo "File backup tidak ditemukan: $BACKUP_FILE" >&2
    exit 1
fi

env_value() {
    local key="$1"
    if [[ -f "$ENV_FILE" ]]; then
        grep -E "^${key}=" "$ENV_FILE" | tail -n 1 | cut -d= -f2- \
            | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
    fi
}

DB_CONNECTION="${DB_CONNECTION:-$(env_value DB_CONNECTION)}"
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
DB_DATABASE="${DB_DATABASE:-$(env_value DB_DATABASE)}"
DB_HOST="${DB_HOST:-$(env_value DB_HOST)}"
DB_PORT="${DB_PORT:-$(env_value DB_PORT)}"
DB_USERNAME="${DB_USERNAME:-$(env_value DB_USERNAME)}"
DB_PASSWORD="${DB_PASSWORD:-$(env_value DB_PASSWORD)}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [[ "$DB_CONNECTION" == "sqlite" && -z "$DB_DATABASE" ]]; then
    DB_DATABASE="database/database.sqlite"
fi

START="$(date +%s)"

case "$DB_CONNECTION" in
    mysql|mariadb)
        if ! command -v mysql >/dev/null 2>&1; then
            echo "mysql client tidak ditemukan." >&2
            exit 1
        fi

        DRILL_DB="${DRILL_DB:-${DB_DATABASE}_restore_drill_$(date +%s)}"
        export MYSQL_PWD="$DB_PASSWORD"

        mysql \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USERNAME" \
            -e "DROP DATABASE IF EXISTS \`$DRILL_DB\`; CREATE DATABASE \`$DRILL_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

        gunzip -c "$BACKUP_FILE" | mysql \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USERNAME" \
            "$DRILL_DB"

        USERS="$(mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" -N -B -e "SELECT COUNT(*) FROM users;" "$DRILL_DB")"
        ORDERS="$(mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" -N -B -e "SELECT COUNT(*) FROM orders;" "$DRILL_DB")"
        TICKETS="$(mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" -N -B -e "SELECT COUNT(*) FROM tickets;" "$DRILL_DB")"

        if [[ "${KEEP_DRILL_DB:-0}" != "1" ]]; then
            mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" -e "DROP DATABASE \`$DRILL_DB\`;"
        fi

        unset MYSQL_PWD
        ;;
    sqlite)
        if ! command -v sqlite3 >/dev/null 2>&1; then
            echo "sqlite3 tidak ditemukan." >&2
            exit 1
        fi

        DRILL_DB="${DRILL_DB:-$(mktemp --suffix=.sqlite)}"
        gunzip -c "$BACKUP_FILE" > "$DRILL_DB"

        USERS="$(sqlite3 "$DRILL_DB" "SELECT COUNT(*) FROM users;")"
        ORDERS="$(sqlite3 "$DRILL_DB" "SELECT COUNT(*) FROM orders;")"
        TICKETS="$(sqlite3 "$DRILL_DB" "SELECT COUNT(*) FROM tickets;")"

        if [[ "${KEEP_DRILL_DB:-0}" != "1" ]]; then
            rm -f "$DRILL_DB"
        fi
        ;;
    *)
        echo "DB_CONNECTION tidak didukung: $DB_CONNECTION" >&2
        exit 1
        ;;
esac

END="$(date +%s)"
ELAPSED="$((END - START))"

echo "Restore drill selesai."
echo "Database drill: $DRILL_DB"
echo "users=$USERS orders=$ORDERS tickets=$TICKETS"
echo "Durasi: ${ELAPSED} detik (RTO ${RTO_SECONDS} detik)"

if (( ELAPSED > RTO_SECONDS )); then
    echo "GAGAL: durasi restore melebihi RTO." >&2
    exit 1
fi

if (( USERS <= 0 )); then
    echo "GAGAL: tabel users kosong setelah restore." >&2
    exit 1
fi

echo "PASS: restore berada dalam RTO dan data inti terbaca."
