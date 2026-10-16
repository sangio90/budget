#!/usr/bin/env bash
#
# Backup del database di produzione.
#
# Flusso: ssh sul server -> mysqldump | gzip in un file temporaneo
#         -> scp del file in locale -> cancellazione del file dal server.
#
# Uso:
#   scripts/db-backup.sh              salva in backups/<db>-<timestamp>.sql.gz
#   scripts/db-backup.sh /cartella    salva nella cartella indicata
#
# Configurazione: scripts/db-sync.env (vedi db-sync.env.example).
# Per ripristinare il backup in locale: scripts/db-restore.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
CONFIG_FILE="$SCRIPT_DIR/db-sync.env"

if [[ ! -f "$CONFIG_FILE" ]]; then
    echo "Manca $CONFIG_FILE: copia db-sync.env.example e compila i valori." >&2
    exit 1
fi
# shellcheck source=db-sync.env.example
source "$CONFIG_FILE"

: "${PROD_SSH:?PROD_SSH non impostato in $CONFIG_FILE}"
: "${PROD_DB_DATABASE:?PROD_DB_DATABASE non impostato in $CONFIG_FILE}"
: "${PROD_DB_USERNAME:?PROD_DB_USERNAME non impostato in $CONFIG_FILE}"
: "${PROD_DB_PASSWORD:?PROD_DB_PASSWORD non impostato in $CONFIG_FILE}"
PROD_DB_HOST="${PROD_DB_HOST:-127.0.0.1}"
PROD_DB_PORT="${PROD_DB_PORT:-3306}"

BACKUP_DIR="${1:-${BACKUP_DIR:-$PROJECT_DIR/backups}}"
mkdir -p "$BACKUP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
FILENAME="${PROD_DB_DATABASE}-${STAMP}.sql.gz"
REMOTE_FILE="/tmp/${FILENAME}"
LOCAL_FILE="${BACKUP_DIR}/${FILENAME}"

SSH_OPTS=(-o ConnectTimeout=15)

# Cancella il file temporaneo dal server: viene chiamata sempre in uscita,
# anche se scp o mysqldump falliscono.
cleanup_remote() {
    ssh "${SSH_OPTS[@]}" "$PROD_SSH" "rm -f $(printf '%q' "$REMOTE_FILE")" >/dev/null 2>&1 || true
}
trap cleanup_remote EXIT

echo "→ Dump di '$PROD_DB_DATABASE' su $PROD_SSH ..."
# Lo script remoto viaggia su stdin, quindi la password non compare mai negli
# argomenti di nessun processo (né in locale né sul server).
ssh "${SSH_OPTS[@]}" "$PROD_SSH" bash -s <<EOF
set -euo pipefail
export MYSQL_PWD=$(printf '%q' "$PROD_DB_PASSWORD")
mysqldump \
    --host=$(printf '%q' "$PROD_DB_HOST") \
    --port=$(printf '%q' "$PROD_DB_PORT") \
    --user=$(printf '%q' "$PROD_DB_USERNAME") \
    --single-transaction --quick \
    --routines --triggers \
    --no-tablespaces --set-gtid-purged=OFF \
    --default-character-set=utf8mb4 \
    $(printf '%q' "$PROD_DB_DATABASE") \
  | gzip -c > $(printf '%q' "$REMOTE_FILE")
EOF

echo "→ Download in $LOCAL_FILE ..."
scp -q "${SSH_OPTS[@]}" "$PROD_SSH:$REMOTE_FILE" "$LOCAL_FILE"
gzip -t "$LOCAL_FILE"

echo "→ Rimozione del file temporaneo dal server ..."
cleanup_remote
trap - EXIT

echo "✓ Backup completato: $LOCAL_FILE ($(du -h "$LOCAL_FILE" | cut -f1))"
