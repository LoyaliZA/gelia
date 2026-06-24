#!/bin/bash
# restore-contabilidad.sh — Restauración del módulo contabilidad desde un respaldo SQL
#
# Precondiciones:
#   1. Mismo código Laravel con migraciones de contabilidad aplicadas (php artisan migrate)
#   2. Contenedor MySQL activo (docker compose -f compose.prod.yaml ps mysql)
#   3. No ejecutar PlatformSeeder después del restore (sobrescribiría platforms)
#
# Uso:
#   ./restore-contabilidad.sh storage/backups/contabilidad/contabilidad_20260624_153000.sql
#   ./restore-contabilidad.sh --yes archivo.sql
#   ./restore-contabilidad.sh --dry-run archivo.sql

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

COMPOSE_FILE="compose.prod.yaml"
SKIP_PRE_BACKUP=0
ASSUME_YES=0
DRY_RUN=0
SQL_FILE=""

TABLES=(
    "platforms"
    "lotes_pagos"
    "contabilidad_pedidos"
    "contabilidad_pedido_detalles"
)

usage() {
    cat <<'EOF'
Uso: ./restore-contabilidad.sh [opciones] <archivo.sql>

Opciones:
  --compose-file FILE   Archivo compose (default: compose.prod.yaml)
  --yes                 Omitir confirmación interactiva
  --skip-pre-backup     No crear respaldo de seguridad del destino antes de restaurar
  --dry-run             Mostrar plan sin ejecutar cambios
  -h, --help            Mostrar esta ayuda

Ejemplo:
  ./restore-contabilidad.sh storage/backups/contabilidad/contabilidad_20260624_153000.sql
EOF
}

log() { echo "[restore-contabilidad] $*"; }
die() { echo "[restore-contabilidad] ERROR: $*" >&2; exit 1; }

resolve_docker_compose() {
    if docker compose version &>/dev/null; then
        DOCKER_COMPOSE=(docker compose)
    elif command -v docker-compose &>/dev/null; then
        DOCKER_COMPOSE=(docker-compose)
    else
        die "No se encontró 'docker compose' ni 'docker-compose'"
    fi
}

mysql_container_running() {
    local cid
    cid="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" ps -q mysql 2>/dev/null | head -1)"
    [[ -n "$cid" ]]
}

gelia_container_running() {
    local cid
    cid="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" ps -q gelia 2>/dev/null | head -1)"
    [[ -n "$cid" ]]
}

get_manifest_row_count() {
    local table="$1"
    local manifest="$2"
    awk -v t="\"${table}\"" '
        /"row_counts"/ { in_counts=1; next }
        in_counts && $0 ~ t {
            if (match($0, /: *[0-9]+/)) {
                val=substr($0, RSTART+2, RLENGTH-2)
                gsub(/[^0-9]/, "", val)
                print val
                exit
            }
        }
        in_counts && /^  \}/ { exit }
    ' "$manifest"
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --compose-file)
            COMPOSE_FILE="${2:?Falta valor para --compose-file}"
            shift 2
            ;;
        --yes)
            ASSUME_YES=1
            shift
            ;;
        --skip-pre-backup)
            SKIP_PRE_BACKUP=1
            shift
            ;;
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        -*)
            die "Opción desconocida: $1 (usa --help)"
            ;;
        *)
            if [[ -n "$SQL_FILE" ]]; then
                die "Solo se permite un archivo SQL"
            fi
            SQL_FILE="$1"
            shift
            ;;
    esac
done

if [[ -z "$SQL_FILE" ]]; then
    usage
    exit 1
fi

if [[ ! -f "$SQL_FILE" ]]; then
    die "Archivo no encontrado: $SQL_FILE"
fi

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

MANIFEST_FILE="${SQL_FILE%.sql}.manifest.json"
HAS_MANIFEST=0
if [[ -f "$MANIFEST_FILE" ]]; then
    HAS_MANIFEST=1
fi

log "Verificando estado de migraciones..."
if gelia_container_running; then
    migrate_out="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T gelia php artisan migrate:status 2>&1 || true)"
    if echo "$migrate_out" | grep -qiE 'Pending|pendiente'; then
        log "ADVERTENCIA: Hay migraciones pendientes en el destino. Ejecuta 'php artisan migrate' antes de restaurar."
    fi
else
    log "ADVERTENCIA: Contenedor gelia no activo; no se pudo verificar migrate:status"
fi

log "Plan de restauración:"
log "  Archivo:    $SQL_FILE"
log "  Manifest:   $([[ $HAS_MANIFEST -eq 1 ]] && echo "$MANIFEST_FILE" || echo "(no encontrado)")"
log "  Base datos: $DB_DATABASE"
log "  Compose:    $COMPOSE_FILE"
log "  Tablas:     ${TABLES[*]}"

if [[ "$DRY_RUN" -eq 1 ]]; then
    log "DRY-RUN: no se realizaron cambios"
    exit 0
fi

if [[ "$SKIP_PRE_BACKUP" -eq 0 ]]; then
    log "Creando respaldo de seguridad del destino (pre-restore)..."
    "$SCRIPT_DIR/backup-contabilidad.sh" \
        --compose-file "$COMPOSE_FILE" \
        --output-dir "storage/backups/contabilidad" \
        --prefix "pre_restore"
else
    log "Omitiendo pre-backup (--skip-pre-backup)"
fi

if [[ "$ASSUME_YES" -eq 0 ]]; then
    echo ""
    echo "ATENCIÓN: Esta operación REEMPLAZARÁ las tablas de contabilidad en '$DB_DATABASE'."
    echo "Tablas afectadas: ${TABLES[*]}"
    read -r -p "Escribe 'yes' para continuar: " confirm
    if [[ "$confirm" != "yes" ]]; then
        die "Restauración cancelada por el usuario"
    fi
fi

log "Importando respaldo (FOREIGN_KEY_CHECKS=0 durante la operación)..."

{
    echo "SET NAMES utf8mb4;"
    echo "SET FOREIGN_KEY_CHECKS=0;"
    cat "$SQL_FILE"
    echo "SET FOREIGN_KEY_CHECKS=1;"
} | "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T mysql mysql \
    -u"$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    "$DB_DATABASE"

log "Importación completada. Verificando conteos de filas..."

VERIFY_FAILED=0
for table in "${TABLES[@]}"; do
    actual="$("${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T mysql mysql \
        -u"$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        -N -e "SELECT COUNT(*) FROM \`${table}\`" "$DB_DATABASE" 2>/dev/null | tr -d '\r')"

    expected=""
    if [[ "$HAS_MANIFEST" -eq 1 ]]; then
        expected="$(get_manifest_row_count "$table" "$MANIFEST_FILE")"
    fi

    if [[ -n "$expected" && "$actual" != "$expected" ]]; then
        log "  $table: $actual filas (esperado según manifest: $expected) — DIFERENCIA"
        VERIFY_FAILED=1
    else
        if [[ -n "$expected" ]]; then
            log "  $table: $actual filas (OK, coincide con manifest)"
        else
            log "  $table: $actual filas"
        fi
    fi
done

if [[ "$HAS_MANIFEST" -eq 1 ]]; then
    expected_sha="$(grep '"sha256"' "$MANIFEST_FILE" | sed -E 's/.*"sha256": "([^"]+)".*/\1/')"
    actual_sha="$(sha256sum "$SQL_FILE" | awk '{print $1}')"
    if [[ -n "$expected_sha" && "$actual_sha" != "$expected_sha" ]]; then
        log "ADVERTENCIA: SHA256 del archivo no coincide con el manifest (archivo pudo modificarse en tránsito)"
        VERIFY_FAILED=1
    fi
fi

echo ""
if [[ "$VERIFY_FAILED" -eq 1 ]]; then
    log "Restauración finalizada con advertencias. Revisa los conteos arriba."
    log "Rollback manual: usa el pre_restore más reciente en storage/backups/contabilidad/"
else
    log "Restauración verificada correctamente."
fi

echo ""
log "Chequeos manuales sugeridos:"
log "  - /contabilidad"
log "  - /contabilidad/retiros"
log "  - /contabilidad/historial-pagos"

exit "$VERIFY_FAILED"
