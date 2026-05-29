#!/usr/bin/env bash
# =============================================================================
# install.sh — CI4 Admin Starter · Template Configurator
#
# Usage: bash install.sh
#
# Reconfigures this repository from the ci4-api-starter template defaults to
# your own API project. Safe to run on macOS (BSD sed) and Linux (GNU sed).
# =============================================================================

set -euo pipefail

# -----------------------------------------------------------------------------
# Colors
# -----------------------------------------------------------------------------
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

info()    { echo -e "${CYAN}${BOLD}[INFO]${RESET}  $*"; }
ok()      { echo -e "${GREEN}${BOLD}[OK]${RESET}    $*"; }
warn()    { echo -e "${YELLOW}${BOLD}[WARN]${RESET}  $*"; }
err()     { echo -e "${RED}${BOLD}[ERROR]${RESET} $*" >&2; }
die()     { err "$*"; exit 1; }
section() { echo -e "\n${BOLD}${CYAN}── $* ──${RESET}"; }

# -----------------------------------------------------------------------------
# Cross-platform sed in-place (BSD on macOS requires extension arg)
# -----------------------------------------------------------------------------
sed_inplace() {
    local file="$1"
    local pattern="$2"
    if [[ "$(uname -s)" == "Darwin" ]]; then
        sed -i '' "$pattern" "$file"
    else
        sed -i "$pattern" "$file"
    fi
}

# -----------------------------------------------------------------------------
# Escape a literal string for use as a sed PATTERN (search side)
# Escapes: . [ * ^ $ / \ (common metacharacters that appear in URLs/paths)
# -----------------------------------------------------------------------------
escape_pattern() {
    printf '%s' "$1" | sed 's/[.[\*^$\/\\]/\\&/g'
}

# -----------------------------------------------------------------------------
# Escape a literal string for use as a sed REPLACEMENT (right side)
# Escapes: & / \
# -----------------------------------------------------------------------------
escape_replacement() {
    printf '%s' "$1" | sed 's/[&\/\\]/\\&/g'
}

# -----------------------------------------------------------------------------
# Validation helpers
# -----------------------------------------------------------------------------
require_non_empty() {
    [[ -n "${1// }" ]] || die "$2 no puede estar vacío."
}

validate_url() {
    echo "$1" | grep -qE '^https?://[^[:space:]]+$' || die "$2 debe ser una URL válida (http:// o https://)."
}

validate_port() {
    echo "$1" | grep -qE '^[0-9]{1,5}$' && [[ "$1" -ge 1 && "$1" -le 65535 ]] || die "El puerto debe ser un número entre 1 y 65535."
}

# -----------------------------------------------------------------------------
# Apply a list of PATTERN|REPLACEMENT pairs to a file using sed.
# Replacements are applied in the order given; the full URL is expected
# to come before any substring of it to avoid partial corruption.
# Usage: apply_subs FILE "PAT1|REP1" "PAT2|REP2" ...
# -----------------------------------------------------------------------------
apply_subs() {
    local file="$1"
    shift
    if [[ ! -f "$file" ]]; then
        warn "Archivo no encontrado, se omite: $file"
        return
    fi
    local changed=0
    for pair in "$@"; do
        local pat="${pair%%|*}"
        local rep="${pair##*|}"
        # Check if the raw (un-escaped) original text is still present
        local raw_pat
        raw_pat="$(printf '%s' "$pat" | sed 's/\\//g')"
        if grep -qF "$raw_pat" "$file" 2>/dev/null; then
            sed_inplace "$file" "s/${pat}/${rep}/g"
            changed=1
        fi
    done
    if [[ $changed -eq 1 ]]; then
        ok "Actualizado: $file"
    else
        info "Sin cambios (¿ya configurado?): $file"
    fi
}

# -----------------------------------------------------------------------------
# Must run from repo root
# -----------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

[[ -f "composer.json" && -d "app" ]] || \
    die "Ejecuta este script desde la raíz del repositorio ci4-admin-starter."

# =============================================================================
# Banner
# =============================================================================
echo ""
echo -e "${BOLD}${CYAN}╔══════════════════════════════════════════════════════════════╗${RESET}"
echo -e "${BOLD}${CYAN}║        CI4 Admin Starter — Configuración de Template         ║${RESET}"
echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════════════════════╝${RESET}"
echo ""
echo "  Este script reconfigura el template para apuntar a tu propia"
echo "  API backend, reemplazando todas las referencias a 'ci4-api-starter'."
echo ""
echo "  Presiona Ctrl+C en cualquier momento para cancelar sin cambios."
echo ""

# Parse arguments
YES_MODE=false
for _arg in "$@"; do
    case "$_arg" in
        --yes|-y) YES_MODE=true ;;
    esac
done

# =============================================================================
# Prompts interactivos
# =============================================================================
section "Configuración"

if [ -n "${CI4_API_NAME:-}" ]; then
  API_NAME="$CI4_API_NAME"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  API_NAME="my-api"
else
  read -r -p "$(echo -e "  ${BOLD}Nombre del repo API${RESET} (reemplaza 'ci4-api-starter') [my-api]: ")" INPUT_API_NAME
  API_NAME="${INPUT_API_NAME:-my-api}"
fi
require_non_empty "$API_NAME" "Nombre del repo API"

DEFAULT_GITHUB="https://github.com/yourusername/${API_NAME}"
if [ -n "${CI4_API_GITHUB_URL:-}" ]; then
  API_GITHUB_URL="$CI4_API_GITHUB_URL"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  API_GITHUB_URL="${DEFAULT_GITHUB}"
else
  read -r -p "$(echo -e "  ${BOLD}URL GitHub del API${RESET}\n  [${DEFAULT_GITHUB}]: ")" INPUT_API_GITHUB_URL
  API_GITHUB_URL="${INPUT_API_GITHUB_URL:-${DEFAULT_GITHUB}}"
fi
validate_url "$API_GITHUB_URL" "URL GitHub del API"

if [ -n "${CI4_API_BASE_URL:-}" ]; then
  API_BASE_URL="$CI4_API_BASE_URL"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  API_BASE_URL="http://localhost:8080"
else
  read -r -p "$(echo -e "  ${BOLD}URL base del API${RESET} (reemplaza 'http://localhost:8080') [http://localhost:8080]: ")" INPUT_API_BASE_URL
  API_BASE_URL="${INPUT_API_BASE_URL:-http://localhost:8080}"
fi
validate_url "$API_BASE_URL" "URL base del API"

if [ -n "${CI4_APP_NAME:-}" ]; then
  APP_NAME="$CI4_APP_NAME"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  APP_NAME="My Admin Panel"
else
  read -r -p "$(echo -e "  ${BOLD}Nombre del panel admin${RESET} (reemplaza 'API Client') [My Admin Panel]: ")" INPUT_APP_NAME
  APP_NAME="${INPUT_APP_NAME:-My Admin Panel}"
fi
require_non_empty "$APP_NAME" "Nombre del panel admin"

if [ -n "${CI4_ADMIN_PORT:-}" ]; then
  ADMIN_PORT="$CI4_ADMIN_PORT"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  ADMIN_PORT="8082"
else
  read -r -p "$(echo -e "  ${BOLD}Puerto del panel admin${RESET} (reemplaza '8082') [8082]: ")" INPUT_ADMIN_PORT
  ADMIN_PORT="${INPUT_ADMIN_PORT:-8082}"
fi
validate_port "$ADMIN_PORT"

echo ""
if [ -n "${CI4_RUN_COMPOSER:-}" ]; then
  RUN_COMPOSER="$CI4_RUN_COMPOSER"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  RUN_COMPOSER="Y"
else
  read -r -p "$(echo -e "  ${BOLD}¿Ejecutar 'composer install' al finalizar?${RESET} [Y/n]: ")" INPUT_COMPOSER
  RUN_COMPOSER="${INPUT_COMPOSER:-Y}"
fi

if [ -n "${CI4_REMOVE_SELF:-}" ]; then
  REMOVE_SELF="$CI4_REMOVE_SELF"
elif [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  REMOVE_SELF="N"
else
  read -r -p "$(echo -e "  ${BOLD}¿Eliminar este script al finalizar?${RESET} [y/N]: ")" INPUT_REMOVE_SELF
  REMOVE_SELF="${INPUT_REMOVE_SELF:-N}"
fi

# =============================================================================
# Confirmación
# =============================================================================
section "Resumen de cambios"

echo ""
echo -e "  ${BOLD}Nombre repo API:${RESET}   ci4-api-starter  →  ${GREEN}${API_NAME}${RESET}"
echo -e "  ${BOLD}URL GitHub API:${RESET}    https://github.com/dcardenasl/ci4-api-starter"
echo -e "                     →  ${GREEN}${API_GITHUB_URL}${RESET}"
echo -e "  ${BOLD}URL base API:${RESET}      http://localhost:8080  →  ${GREEN}${API_BASE_URL}${RESET}"
echo -e "  ${BOLD}Nombre de app:${RESET}     API Client  →  ${GREEN}${APP_NAME}${RESET}"
echo -e "  ${BOLD}Puerto admin:${RESET}      8082  →  ${GREEN}${ADMIN_PORT}${RESET}"
_rc_lower="$(printf '%s' "$RUN_COMPOSER" | tr '[:upper:]' '[:lower:]')"
echo -e "  ${BOLD}composer install:${RESET}  $([ "$_rc_lower" != "n" ] && echo "Sí" || echo "No")"
_rs_lower="$(printf '%s' "$REMOVE_SELF" | tr '[:upper:]' '[:lower:]')"
echo -e "  ${BOLD}Eliminar script:${RESET}   $([ "$_rs_lower" = "y" ] && echo "Sí" || echo "No")"
echo ""

if [ "$YES_MODE" = "true" ] || [ "${CI4_CONFIRM:-}" = "y" ]; then
  CONFIRM="y"
else
  read -r -p "$(echo -e "  ${BOLD}¿Continuar? [y/N]:${RESET} ")" CONFIRM
fi

_confirm_lower="$(printf '%s' "$CONFIRM" | tr '[:upper:]' '[:lower:]')"
[ "$_confirm_lower" = "y" ] || { warn "Cancelado. No se realizaron cambios."; exit 0; }

# =============================================================================
# Pre-escape de todos los valores
# IMPORTANTE: reemplazar URLs completas ANTES que subcadenas del nombre corto
# para evitar corrupción parcial (la URL contiene el nombre del repo).
# =============================================================================
P_GITHUB_OLD="$(escape_pattern 'https://github.com/dcardenasl/ci4-api-starter')"
R_GITHUB_NEW="$(escape_replacement "${API_GITHUB_URL}")"

P_API_NAME_OLD="$(escape_pattern 'ci4-api-starter')"
R_API_NAME_NEW="$(escape_replacement "${API_NAME}")"

P_BASE_URL_OLD="$(escape_pattern 'http://localhost:8080')"
R_BASE_URL_NEW="$(escape_replacement "${API_BASE_URL}")"

P_APP_NAME_OLD="$(escape_pattern 'API Client')"
R_APP_NAME_NEW="$(escape_replacement "${APP_NAME}")"

P_PORT_OLD="8082"
R_PORT_NEW="${ADMIN_PORT}"

# composer.json: path ../ci4-api-starter/ -> ../{API_NAME}/
P_COMPOSER_PATH_OLD="$(escape_pattern "../ci4-api-starter/")"
R_COMPOSER_PATH_NEW="$(escape_replacement "../${API_NAME}/")"

# app/Config/ApiClient.php: PHP class defaults
P_PHP_BASE_URL="$(escape_pattern "public string \$baseUrl = 'http://localhost:8080';")"
R_PHP_BASE_URL="$(escape_replacement "public string \$baseUrl = '${API_BASE_URL}';")"

P_PHP_APP_NAME="$(escape_pattern "public string \$appName = 'API Client';")"
R_PHP_APP_NAME="$(escape_replacement "public string \$appName = '${APP_NAME}';")"

# app/Views/layouts/partials/head.php: PHP fallback default
P_HEAD_APP_NAME="$(escape_pattern "\$appName ??= 'API Client';")"
R_HEAD_APP_NAME="$(escape_replacement "\$appName ??= '${APP_NAME}';")"

# =============================================================================
# Sustituciones en archivos de documentación y configuración
# =============================================================================
section "Actualizando archivos"

# CLAUDE.md
apply_subs "CLAUDE.md" \
    "${P_GITHUB_OLD}|${R_GITHUB_NEW}" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_APP_NAME_OLD}|${R_APP_NAME_NEW}" \
    "${P_PORT_OLD}|${R_PORT_NEW}"

# README.md
apply_subs "README.md" \
    "${P_GITHUB_OLD}|${R_GITHUB_NEW}" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_PORT_OLD}|${R_PORT_NEW}"

# GEMINI.md
apply_subs "GEMINI.md" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_PORT_OLD}|${R_PORT_NEW}"

# AGENTS.md
apply_subs "AGENTS.md" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_PORT_OLD}|${R_PORT_NEW}"

# composer.json
apply_subs "composer.json" \
    "${P_COMPOSER_PATH_OLD}|${R_COMPOSER_PATH_NEW}"

# env (template)
apply_subs "env" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_APP_NAME_OLD}|${R_APP_NAME_NEW}"

# app/Config/ApiClient.php
apply_subs "app/Config/ApiClient.php" \
    "${P_PHP_BASE_URL}|${R_PHP_BASE_URL}" \
    "${P_PHP_APP_NAME}|${R_PHP_APP_NAME}"

# tests/unit/Libraries/ApiClientTest.php
apply_subs "tests/unit/Libraries/ApiClientTest.php" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_APP_NAME_OLD}|${R_APP_NAME_NEW}"

# app/Views/layouts/partials/head.php
apply_subs "app/Views/layouts/partials/head.php" \
    "${P_HEAD_APP_NAME}|${R_HEAD_APP_NAME}"

# docs/
apply_subs "docs/COMPATIBILIDAD-API.md" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}"

apply_subs "docs/GOOGLE-LOGIN-SETUP.md" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_BASE_URL_OLD}|${R_BASE_URL_NEW}" \
    "${P_PORT_OLD}|${R_PORT_NEW}"

apply_subs "docs/plan/PLAN-CI4-CLIENT.md" \
    "${P_API_NAME_OLD}|${R_API_NAME_NEW}" \
    "${P_APP_NAME_OLD}|${R_APP_NAME_NEW}"

# =============================================================================
# Crear y configurar .env
# =============================================================================
section "Configurando .env"

if [[ -f ".env" ]]; then
    warn ".env ya existe. Se omite la copia desde el template."
    warn "Borra .env y vuelve a ejecutar este script si quieres un .env limpio."
else
    cp env .env
    ok "Creado .env a partir del template env."
fi

# CI_ENVIRONMENT
if grep -q "^# CI_ENVIRONMENT" .env; then
    sed_inplace ".env" "s|^# CI_ENVIRONMENT = .*|CI_ENVIRONMENT = development|"
    ok "Activado CI_ENVIRONMENT = development en .env"
elif ! grep -q "^CI_ENVIRONMENT" .env; then
    echo "CI_ENVIRONMENT = development" >> .env
    ok "Añadido CI_ENVIRONMENT = development al .env"
fi

# app.baseURL  (escape the dot so we don't also match the alternative `app_baseURL` line)
APP_BASE_URL_VAL="http://localhost:${ADMIN_PORT}/"
if grep -q "^# app\.baseURL" .env; then
    sed_inplace ".env" "s|^# app\.baseURL = .*|app.baseURL = '${APP_BASE_URL_VAL}'|"
    ok "Activado app.baseURL en .env"
elif grep -q "^app\.baseURL" .env; then
    sed_inplace ".env" "s|^app\.baseURL = .*|app.baseURL = '${APP_BASE_URL_VAL}'|"
    ok "Actualizado app.baseURL en .env"
else
    echo "app.baseURL = '${APP_BASE_URL_VAL}'" >> .env
    ok "Añadido app.baseURL al .env"
fi

# apiClient.baseUrl
ESCAPED_API_BASE_URL="$(escape_replacement "${API_BASE_URL}")"
if grep -q "^# apiClient.baseUrl" .env; then
    sed_inplace ".env" "s|^# apiClient.baseUrl = .*|apiClient.baseUrl = '${ESCAPED_API_BASE_URL}'|"
    ok "Activado apiClient.baseUrl en .env"
elif grep -q "^apiClient.baseUrl" .env; then
    sed_inplace ".env" "s|^apiClient.baseUrl = .*|apiClient.baseUrl = '${ESCAPED_API_BASE_URL}'|"
    ok "Actualizado apiClient.baseUrl en .env"
else
    echo "apiClient.baseUrl = '${API_BASE_URL}'" >> .env
    ok "Añadido apiClient.baseUrl al .env"
fi

# apiClient.appName
ESCAPED_APP_NAME="$(escape_replacement "${APP_NAME}")"
if grep -q "^# apiClient.appName" .env; then
    sed_inplace ".env" "s|^# apiClient.appName = .*|apiClient.appName = '${ESCAPED_APP_NAME}'|"
    ok "Activado apiClient.appName en .env"
elif grep -q "^apiClient.appName" .env; then
    sed_inplace ".env" "s|^apiClient.appName = .*|apiClient.appName = '${ESCAPED_APP_NAME}'|"
    ok "Actualizado apiClient.appName en .env"
else
    echo "apiClient.appName = '${APP_NAME}'" >> .env
    ok "Añadido apiClient.appName al .env"
fi

# =============================================================================
# composer install (opcional)
# =============================================================================
_rc_lower="$(printf '%s' "$RUN_COMPOSER" | tr '[:upper:]' '[:lower:]')"
if [ "$_rc_lower" != "n" ]; then
    section "Instalando dependencias Composer"
    if command -v composer &>/dev/null; then
        composer install --no-interaction
        ok "Dependencias de Composer instaladas."
    else
        warn "Composer no encontrado en PATH. Ejecuta 'composer install' manualmente."
    fi
else
    info "Se omite composer install."
fi

# =============================================================================
# Resumen final
# =============================================================================
section "Instalación completada"

echo ""
echo -e "  ${BOLD}Archivos actualizados:${RESET}"
echo -e "  ${GREEN}•${RESET} CLAUDE.md, README.md, GEMINI.md, AGENTS.md"
echo -e "  ${GREEN}•${RESET} composer.json (path sync-swagger)"
echo -e "  ${GREEN}•${RESET} env (template), .env (configuración activa)"
echo -e "  ${GREEN}•${RESET} app/Config/ApiClient.php"
echo -e "  ${GREEN}•${RESET} app/Views/layouts/partials/head.php"
echo -e "  ${GREEN}•${RESET} docs/COMPATIBILIDAD-API.md, docs/GOOGLE-LOGIN-SETUP.md"
echo -e "  ${GREEN}•${RESET} docs/plan/PLAN-CI4-CLIENT.md"
echo ""
echo -e "  ${BOLD}Tu proyecto está configurado con:${RESET}"
echo -e "    API backend:   ${CYAN}${API_NAME}${RESET}  →  ${CYAN}${API_BASE_URL}${RESET}"
echo -e "    Panel admin:   ${CYAN}${APP_NAME}${RESET}  →  ${CYAN}http://localhost:${ADMIN_PORT}${RESET}"
echo ""
echo -e "  ${BOLD}Próximos pasos:${RESET}"
echo -e "    1. Asegúrate de que ${CYAN}${API_NAME}${RESET} está corriendo en ${CYAN}${API_BASE_URL}${RESET}"
echo -e "    2. Inicia el servidor de desarrollo:"
echo -e "       ${YELLOW}php spark serve --port ${ADMIN_PORT}${RESET}"
echo ""
echo -e "  ${YELLOW}Nota:${RESET} Si tu repo de API no está en el directorio hermano de este"
echo -e "  proyecto, actualiza el path del script ${BOLD}sync-swagger${RESET} en composer.json."
echo ""

# =============================================================================
# Autoeliminación opcional
# =============================================================================
_rs_lower="$(printf '%s' "$REMOVE_SELF" | tr '[:upper:]' '[:lower:]')"
if [ "$_rs_lower" = "y" ]; then
    SCRIPT_PATH="${BASH_SOURCE[0]}"
    rm -- "$SCRIPT_PATH"
    ok "install.sh eliminado."
fi
