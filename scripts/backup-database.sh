#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$APP_ROOT/.env}"

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
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"

if [[ "$DB_CONNECTION" == "sqlite" && -z "$DB_DATABASE" ]]; then
    DB_DATABASE="database/database.sqlite"
fi
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BASENAME="linytix-${STAMP}"

mkdir -p "$BACKUP_DIR"

case "$DB_CONNECTION" in
    mysql|mariadb)
        if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
            echo "DB_DATABASE dan DB_USERNAME wajib diisi untuk backup MySQL." >&2
            exit 1
        fi

        if ! command -v mysqldump >/dev/null 2>&1; then
            echo "mysqldump tidak ditemukan." >&2
            exit 1
        fi

        TARGET="$BACKUP_DIR/${BASENAME}.sql.gz"
        export MYSQL_PWD="$DB_PASSWORD"

        mysqldump \
            --single-transaction \
            --quick \
            --routines \
            --triggers \
            --no-tablespaces \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USERNAME" \
            "$DB_DATABASE" | gzip -9 > "$TARGET"

        unset MYSQL_PWD
        ;;
    sqlite)
        if [[ -z "$DB_DATABASE" ]]; then
            echo "DB_DATABASE wajib diisi untuk backup SQLite." >&2
            exit 1
        fi

        if [[ "$DB_DATABASE" != /* ]]; then
            DB_DATABASE="$APP_ROOT/$DB_DATABASE"
        fi

        if [[ ! -f "$DB_DATABASE" ]]; then
            echo "Database SQLite tidak ditemukan: $DB_DATABASE" >&2
            exit 1
        fi

        TARGET="$BACKUP_DIR/${BASENAME}.sqlite.gz"
        TMP_SQLITE="$(mktemp)"
        trap 'rm -f "$TMP_SQLITE"' EXIT

        if command -v sqlite3 >/dev/null 2>&1; then
            sqlite3 "$DB_DATABASE" ".backup '$TMP_SQLITE'"
        else
            cp "$DB_DATABASE" "$TMP_SQLITE"
        fi

        gzip -9 -c "$TMP_SQLITE" > "$TARGET"
        ;;
    *)
        echo "DB_CONNECTION tidak didukung: $DB_CONNECTION" >&2
        exit 1
        ;;
esac

gzip -t "$TARGET"
CHECKSUM="$(sha256sum "$TARGET" | awk '{print $1}')"
printf '%s  %s\n' "$CHECKSUM" "$(basename "$TARGET")" > "${TARGET}.sha256"

find "$BACKUP_DIR" -maxdepth 1 -type f -name 'linytix-*' -mtime +"$RETENTION_DAYS" -delete

echo "Backup selesai: $TARGET"
echo "Checksum: $CHECKSUM"
echo "Retensi: ${RETENTION_DAYS} hari"
