#!/usr/bin/env bash
#
# Ripristina un backup (creato da db-backup.sh) nel MySQL locale di docker compose.
#
# Uso:
#   scripts/db-restore.sh                usa il backup più recente in backups/
#   scripts/db-restore.sh file.sql.gz    usa il file indicato (.sql o .sql.gz)
#
# Il database locale viene cancellato e ricreato prima dell'import.
# Le credenziali sono quelle del container, che docker compose legge dal .env
# del progetto: non serve nessuna configurazione aggiuntiva.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/backups}"
SERVICE="db"

cd "$PROJECT_DIR"

if [[ $# -ge 1 ]]; then
    DUMP_FILE="$1"
else
    DUMP_FILE="$(ls -1t "$BACKUP_DIR"/*.sql.gz "$BACKUP_DIR"/*.sql 2>/dev/null | head -n 1 || true)"
    if [[ -z "$DUMP_FILE" ]]; then
        echo "Nessun backup in $BACKUP_DIR: esegui prima scripts/db-backup.sh" >&2
        exit 1
    fi
fi
if [[ ! -f "$DUMP_FILE" ]]; then
    echo "File non trovato: $DUMP_FILE" >&2
    exit 1
fi

echo "→ Avvio del container MySQL ..."
docker compose up -d --wait "$SERVICE"

# Esegue il client mysql come root dentro al container, usando le variabili
# d'ambiente del container stesso per password e nome del database.
mysql_root() {
    docker compose exec -T "$SERVICE" sh -c \
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot --default-character-set=utf8mb4 "$@"' \
        sh "$@"
}
DB_NAME="$(docker compose exec -T "$SERVICE" sh -c 'printf %s "$MYSQL_DATABASE"')"

echo "→ Ricreo il database '$DB_NAME' ..."
mysql_root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;
               CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "→ Import di $DUMP_FILE ..."
if [[ "$DUMP_FILE" == *.gz ]]; then
    gzip -dc "$DUMP_FILE" | mysql_root "$DB_NAME"
else
    mysql_root "$DB_NAME" < "$DUMP_FILE"
fi

echo "→ Righe per tabella:"
COUNT_SQL="$(mysql_root -N -s "$DB_NAME" -e "
    SET SESSION group_concat_max_len = 65535;
    SELECT GROUP_CONCAT(
        CONCAT('SELECT ''', table_name, ''' AS tabella, COUNT(*) AS righe FROM \`', table_name, '\`')
        SEPARATOR ' UNION ALL ')
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';")"
mysql_root -t "$DB_NAME" -e "$COUNT_SQL ORDER BY tabella;"

echo "✓ Restore completato."
