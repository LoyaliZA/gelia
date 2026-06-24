#!/bin/bash
# backup-contabilidad.sh — Respaldo del módulo contabilidad (4 tablas MySQL vía Docker)
#
# Uso:
#   ./backup-contabilidad.sh
#   ./backup-contabilidad.sh --compose-file compose.yaml
#   ./backup-contabilidad.sh --output-dir storage/backups/contabilidad --keep 10
#   ./backup-contabilidad.sh --prefix pre_restore
#
# Genera:
#   {output-dir}/{prefix}_YYYYMMDD_HHMMSS.sql
#   {output-dir}/{prefix}_YYYYMMDD_HHMMSS.manifest.json

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

COMPOSE_FILE="compose.prod.yaml"
OUTPUT_DIR="storage/backups/contabilidad"
KEEP=0
PREFIX="contabilidad"

TABLES=(
    "platforms"
    "lotes_pagos"
    "contabilidad_pedidos"
    "contabilidad_pedido_detalles"
)

usage() {
    cat <<'EOF'
Uso: ./backup-contabilidad.sh [opciones]

Opciones:
  --compose-file FILE   Archivo compose (default: compose.prod.yaml)
  --output-dir DIR      Directorio de salida (default: storage/backups/contabilidad)
  --keep N              Conservar solo los últimos N respaldos (0 = sin rotación)
  --prefix NAME         Prefijo del archivo (default: contabilidad)
  -h, --help            Mostrar esta ayuda
EOF
}

log() { echo "[backup-contabilidad] $*"; }
die() { echo "[backup-contabilidad] ERROR: $*" >&2; exit 1; }

mysql_container_running() {
    local cid
    cid="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" ps -q mysql 2>/dev/null | head -1)"
    [[ -n "$cid" ]]
}

resolve_docker_compose() {
    if docker compose version &>/dev/null; then
        DOCKER_COMPOSE=(docker compose)
    elif command -v docker-compose &>/dev/null; then
        DOCKER_COMPOSE=(docker-compose)
    else
        die "No se encontró 'docker compose' ni 'docker-compose'"
    fi
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --compose-file)
            COMPOSE_FILE="${2:?Falta valor para --compose-file}"
            shift 2
            ;;
        --output-dir)
            OUTPUT_DIR="${2:?Falta valor para --output-dir}"
            shift 2
            ;;
        --keep)
            KEEP="${2:?Falta valor para --keep}"
            shift 2
            ;;
        --prefix)
            PREFIX="${2:?Falta valor para --prefix}"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            die "Opción desconocida: $1 (usa --help)"
            ;;
    esac
done

if [[ ! -f .env ]]; then
    die "No se encontró .env en $SCRIPT_DIR"
fi

# shellcheck disable=SC1091
set -a
# shellcheck source=/dev/null
source .env
set +a

: "${DB_DATABASE:?DB_DATABASE no definido en .env}"
: "${DB_USERNAME:?DB_USERNAME no definido en .env}"
: "${DB_PASSWORD:?DB_PASSWORD no definido en .env}"

resolve_docker_compose

if ! mysql_container_running; then
    die "El contenedor mysql no está en ejecución (compose: $COMPOSE_FILE)"
fi

mkdir -p "$OUTPUT_DIR"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
SQL_FILE="${OUTPUT_DIR}/${PREFIX}_${TIMESTAMP}.sql"
MANIFEST_FILE="${OUTPUT_DIR}/${PREFIX}_${TIMESTAMP}.manifest.json"

log "Iniciando respaldo → $SQL_FILE"

"${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T mysql mysqldump \
    -u"$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --add-drop-table \
    --routines=false \
    --triggers=false \
    --no-tablespaces \
    "$DB_DATABASE" \
    "${TABLES[@]}" > "$SQL_FILE"

if [[ ! -s "$SQL_FILE" ]]; then
    die "El archivo SQL generado está vacío"
fi

declare -A ROW_COUNTS
for table in "${TABLES[@]}"; do
    count="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T mysql mysql \
        -u"$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        -N -e "SELECT COUNT(*) FROM \`${table}\`" "$DB_DATABASE" 2>/dev/null | tr -d '\r')"
    ROW_COUNTS["$table"]="${count:-0}"
done

FILE_SIZE="$(wc -c < "$SQL_FILE" | tr -d ' ')"
SHA256="$(sha256sum "$SQL_FILE" | awk '{print $1}')"
GIT_COMMIT="$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")"
EXPORTED_AT="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

# Generar manifest JSON (sin dependencias externas)
{
    echo '{'
    echo "  \"exported_at\": \"${EXPORTED_AT}\","
    echo "  \"database\": \"${DB_DATABASE}\","
    echo "  \"compose_file\": \"${COMPOSE_FILE}\","
    echo "  \"prefix\": \"${PREFIX}\","
    echo "  \"git_commit\": \"${GIT_COMMIT}\","
    echo "  \"sql_file\": \"$(basename "$SQL_FILE")\","
    echo "  \"file_size_bytes\": ${FILE_SIZE},"
    echo "  \"sha256\": \"${SHA256}\","
    echo '  "tables": ['
    for i in "${!TABLES[@]}"; do
        table="${TABLES[$i]}"
        comma=","
        [[ $i -eq $((${#TABLES[@]} - 1)) ]] && comma=""
        echo "    \"${table}\"${comma}"
    done
    echo '  ],'
    echo '  "row_counts": {'
    for i in "${!TABLES[@]}"; do
        table="${TABLES[$i]}"
        comma=","
        [[ $i -eq $((${#TABLES[@]} - 1)) ]] && comma=""
        echo "    \"${table}\": ${ROW_COUNTS[$table]}${comma}"
    done
    echo '  }'
    echo '}'
} > "$MANIFEST_FILE"

log "Respaldo completado"
log "  SQL:       $SQL_FILE ($(numfmt --to=iec-i --suffix=B "$FILE_SIZE" 2>/dev/null || echo "${FILE_SIZE} bytes"))"
log "  Manifest:  $MANIFEST_FILE"
for table in "${TABLES[@]}"; do
    log "  $table: ${ROW_COUNTS[$table]} filas"
done

if [[ "$KEEP" -gt 0 ]]; then
    mapfile -t OLD_SQL < <(ls -1t "${OUTPUT_DIR}/${PREFIX}"_*.sql 2>/dev/null || true)
    if ((${#OLD_SQL[@]} > KEEP)); then
        for ((i = KEEP; i < ${#OLD_SQL[@]}; i++)); do
            old_sql="${OLD_SQL[$i]}"
            old_manifest="${old_sql%.sql}.manifest.json"
            log "Rotación: eliminando $(basename "$old_sql")"
            rm -f "$old_sql" "$old_manifest"
        done
    fi
fi

exit 0
