#!/bin/bash
#
# ci4-admin-starter Module Scaffolding
# Creates a new resource within an admin module and registers PSR-4 autoloading.
#
# Usage:
#   bash bin/make-module.sh <Resource> <Module> <ApiPath> [RouteSegment] [Fields] [--dry-run] [--force]
#
# Scope:
#   Generates a CRUD shell only: controller, service, requests, routes, views,
#   language files, test stubs, and optional POST item-actions via --action.
#   When [Fields] is provided, views use typed components (form/text, form/select,
#   form/boolean…) and the index table has one column per field.
#   Aggregate-grade UI behavior still requires manual extension after scaffolding.
#
# Examples:
#   bash bin/make-module.sh Product Catalog /catalog/products
#   bash bin/make-module.sh Product Catalog /catalog/products 'name:string:required,price:decimal:required,active:boolean'
#   bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories
#   bash bin/make-module.sh Order Orders /orders
#   bash bin/make-module.sh Product Catalog /catalog/products --dry-run
#   bash bin/make-module.sh Product Catalog /catalog/products --force
#

set -e
set -o pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

usage() {
    cat <<'USAGE'
Usage:
  bash bin/make-module.sh <Resource> <Module> <ApiPath> [RouteSegment] [flags]

Scope:
  Generates a CRUD shell only. You still need manual follow-up for aggregate
  behavior such as custom actions, nested resources, relation-array forms,
  option loaders, media/file-picker flows, and CSV import/export.
  The built-in `--action=<verb>` hook covers only common POST item actions
  such as approve, publish, archive, restore.

Arguments:
  <Resource>      StudlyCase resource name  (e.g. Product, SchoolCategory)
  <Module>        StudlyCase module name    (e.g. Catalog, Education)
  <ApiPath>       API path for the resource (e.g. /catalog/products)
  [RouteSegment]  URL segment override      (default: resource_plural with dashes)
  [Fields]        Comma-separated field definitions: name:type[:required][:<extra>]
                  Types: string, text, longtext, int, bigint, decimal, float,
                         boolean, date, datetime, enum, relation, file, image
                  Extra for enum:    pipe-separated options  (e.g. status:enum:active|inactive)
                  Extra for relation: variable name for options array (e.g. category_id:relation:categories)
                  If arg 4 contains ':', it is treated as Fields (RouteSegment is skipped).
                  If arg 4 has no ':', it is RouteSegment and arg 5 is Fields.
                  When omitted, a single 'name:string:required' field is generated.

Flags:
  --dry-run         Print what would be generated without writing any file
  --force           Overwrite existing files (skipped by default)
  --service=hub|domain
                    Which backend the new service should target. Default is 'hub'
                    (apiClient, port 8080). Pass 'domain' to wire the service
                    against domainApiClient (port 8090 — a ci4-domain-starter app).
  --action=<verb>    Add a custom POST action for a single item. Repeat the flag
                    for multiple actions (e.g. --action=approve --action=publish).
                    Verbs must be lower-kebab-case.
  --csv             Scaffold CSV export/import hooks alongside the CRUD shell.
  --check-api[=URL] Probe the API endpoint with a 2s HEAD request before scaffolding
                    and warn if it doesn't respond. Default URL is read from
                    apiClient.baseUrl in .env (or domainApiClient.baseUrl when
                    --service=domain).

Examples:
  bash bin/make-module.sh Product Catalog /catalog/products
  bash bin/make-module.sh Product Catalog /catalog/products 'name:string:required,price:decimal:required,active:boolean'
  bash bin/make-module.sh Product Catalog /catalog/products 'name:string:required,status:enum:draft|published|archived'
  bash bin/make-module.sh Product Catalog /catalog/products 'name:string:required,category_id:relation:required:categories'
  bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories
  bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories 'name:string:required,active:boolean'
  bash bin/make-module.sh Product Catalog /catalog/products --dry-run
  bash bin/make-module.sh Product Catalog /catalog/products --check-api
  bash bin/make-module.sh Product Catalog /catalog/products --check-api=http://localhost:8080
  bash bin/make-module.sh User Identity /users --action=approve --action=archive
  bash bin/make-module.sh Report Analytics /analytics/reports 'name:string:required,score:int:required' --csv
USAGE
}

# ─── Argument parsing (positionals + flags interleaved) ────────────────────────

POSITIONAL=()
DRY_RUN=false
FORCE=false
CHECK_API_URL=""
CHECK_API_REQUESTED=false
SERVICE_TARGET="hub"
CSV_ENABLED=false
CUSTOM_ACTIONS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=true; shift ;;
        --force)   FORCE=true;   shift ;;
        --service=*) SERVICE_TARGET="${1#--service=}"; shift ;;
        --action=*) CUSTOM_ACTIONS+=("${1#--action=}"); shift ;;
        --csv) CSV_ENABLED=true; shift ;;
        --check-api) CHECK_API_REQUESTED=true; shift ;;
        --check-api=*) CHECK_API_REQUESTED=true; CHECK_API_URL="${1#--check-api=}"; shift ;;
        --help|-h) usage; exit 0 ;;
        --*)
            echo -e "${RED}❌ Unknown flag: $1${NC}"
            echo ""
            usage
            exit 1
            ;;
        *) POSITIONAL+=("$1"); shift ;;
    esac
done
set -- "${POSITIONAL[@]}"

if [[ "$SERVICE_TARGET" != "hub" && "$SERVICE_TARGET" != "domain" ]]; then
    echo -e "${RED}❌ --service must be 'hub' or 'domain'. Got: '${SERVICE_TARGET}'${NC}"
    exit 1
fi

if [[ "$SERVICE_TARGET" == "domain" ]]; then
    CLIENT_FACTORY="domainApiClient"
    CLIENT_ENV_KEY="domainApiClient.baseUrl"
else
    CLIENT_FACTORY="apiClient"
    CLIENT_ENV_KEY="apiClient.baseUrl"
fi

RESOURCE=${1:-}
MODULE=${2:-}
API_PATH=${3:-}
ROUTE_SEGMENT_ARG=${4:-}
ROUTE_SEGMENT=""
FIELDS=""

if [[ -n "$ROUTE_SEGMENT_ARG" ]]; then
    if [[ "$ROUTE_SEGMENT_ARG" == *":"* ]]; then
        FIELDS="$ROUTE_SEGMENT_ARG"
    else
        ROUTE_SEGMENT="$ROUTE_SEGMENT_ARG"
        FIELDS=${5:-}
    fi
fi

if [[ -z "$RESOURCE" || -z "$MODULE" || -z "$API_PATH" ]]; then
    echo -e "${RED}❌ Missing arguments${NC}"
    echo ""
    usage
    exit 1
fi

# ─── Input validation ──────────────────────────────────────────────────────────

if [[ ! "$RESOURCE" =~ ^[A-Z][a-zA-Z0-9]+$ ]]; then
    echo -e "${RED}❌ RESOURCE must be StudlyCase (e.g. Product, SchoolCategory). Got: '${RESOURCE}'${NC}"
    exit 1
fi

if [[ ! "$MODULE" =~ ^[A-Z][a-zA-Z0-9]+$ ]]; then
    echo -e "${RED}❌ MODULE must be StudlyCase (e.g. Catalog, Education). Got: '${MODULE}'${NC}"
    exit 1
fi

if [[ "$API_PATH" != /* ]]; then
    echo -e "${RED}❌ API_PATH must start with '/' (e.g. /catalog/products). Got: '${API_PATH}'${NC}"
    exit 1
fi

if [[ ${#CUSTOM_ACTIONS[@]} -gt 0 ]]; then
    for action in "${CUSTOM_ACTIONS[@]}"; do
        if [[ ! "$action" =~ ^[a-z][a-z0-9-]*$ ]]; then
            echo -e "${RED}❌ --action values must be lower-kebab-case (e.g. approve, publish, archive-item). Got: '${action}'${NC}"
            exit 1
        fi
    done

    unique_action_count=$(printf '%s\n' "${CUSTOM_ACTIONS[@]}" | sort -u | wc -l | tr -d ' ')
    if [[ "${#CUSTOM_ACTIONS[@]}" -ne "$unique_action_count" ]]; then
        echo -e "${RED}❌ Duplicate --action flags detected. Each custom action must be unique.${NC}"
        exit 1
    fi
fi

# Change to project root (ci4-admin-starter/)
cd "$(dirname "$0")/.."

LOCK_DIR="${TMPDIR:-/tmp}/ci4-admin-make-module.lock"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
    echo -e "${YELLOW}⚠ Another make-module.sh process is running. Waiting for the scaffold lock...${NC}"
    while ! mkdir "$LOCK_DIR" 2>/dev/null; do
        sleep 1
    done
fi
trap 'rm -rf "$LOCK_DIR"' EXIT

# ─── Optional API endpoint probe (--check-api) ─────────────────────────────────
if [[ "$CHECK_API_REQUESTED" == true ]]; then
    if [[ -z "$CHECK_API_URL" && -f .env ]]; then
        CHECK_API_URL=$(grep -E "^[[:space:]]*${CLIENT_ENV_KEY//./\\.}" .env | head -1 | sed -E "s/.*=\s*['\"]?([^'\"]+)['\"]?.*/\1/" | tr -d ' ')
    fi
    if [[ -z "$CHECK_API_URL" ]]; then
        echo -e "${YELLOW}⚠ --check-api requested but no URL provided and apiClient.baseUrl not found in .env. Skipping.${NC}"
    else
        FULL_URL="${CHECK_API_URL%/}${API_PATH}"
        echo -e "${BLUE}Probing API endpoint:${NC} ${FULL_URL}"
        # -I = HEAD; -m 2 = 2s timeout; -o /dev/null silences body; -w prints status
        STATUS=$(curl -sI -m 2 -o /dev/null -w '%{http_code}' "$FULL_URL" 2>/dev/null || echo '000')
        case "$STATUS" in
            2*|3*|401|403)
                echo -e "  ${GREEN}✓ Endpoint reachable (HTTP ${STATUS})${NC}"
                ;;
            404)
                echo -e "  ${YELLOW}⚠ Endpoint returned 404 — check that ${API_PATH} exists in the API.${NC}"
                ;;
            000)
                echo -e "  ${YELLOW}⚠ Endpoint unreachable (connection refused or timeout). The scaffold will continue, but the module will fail at runtime.${NC}"
                ;;
            *)
                echo -e "  ${YELLOW}⚠ Endpoint returned HTTP ${STATUS} — verify it's behaving as expected.${NC}"
                ;;
        esac
        echo ""
    fi
fi

# ─── Acronym warning ───────────────────────────────────────────────────────────
# Detect uppercase runs (≥2 consecutive caps followed by lowercase, or all-caps suffix)
# that previously generated broken outputs like 'a_p_i_keys' / 'A p i key'.
if [[ "$RESOURCE" =~ [A-Z]{2,}[a-z] ]] || [[ "$RESOURCE" =~ [A-Z]{2,}$ ]]; then
    CANONICAL=$(python3 -c '
import sys, re
v = sys.argv[1]
v = re.sub(r"([A-Z]+)([A-Z][a-z])", lambda m: m.group(1).capitalize() + m.group(2), v)
v = re.sub(r"([A-Z]+)$", lambda m: m.group(1).capitalize(), v)
print(v[:1].upper() + v[1:])
' "$RESOURCE")
    echo -e "${YELLOW}⚠ Resource '${RESOURCE}' contains a run of consecutive uppercase letters.${NC}"
    echo -e "${YELLOW}  Derived names will keep the acronym intact (e.g. snake='api_key' instead of 'a_p_i_key').${NC}"
    echo -e "${YELLOW}  Class/file names preserve the resource as-typed (e.g. ${RESOURCE}Controller.php).${NC}"
    echo -e "${YELLOW}  If you prefer canonical StudlyCase, re-run with: ${CANONICAL}${NC}"
    echo ""
fi

# ─── Name derivations ──────────────────────────────────────────────────────────

# StudlyCase → snake_case, treating runs of uppercase as one word.
#   SchoolCategory → school_category
#   APIKey         → api_key      (not a_p_i_key — see audit P0)
#   HTTPRequest    → http_request
#   OAuth2Token    → o_auth2_token
to_snake() {
    python3 -c '
import sys, re
v = sys.argv[1]
v = re.sub(r"([a-z0-9])([A-Z])", r"\1_\2", v)
v = re.sub(r"([A-Z]+)([A-Z][a-z])", r"\1_\2", v)
print(v.lower())
' "$1"
}

# StudlyCase → lower camelCase, normalizing acronym runs to a single capitalized word.
#   Audience       → audience
#   SchoolCategory → schoolCategory
#   APIKey         → apiKey         (not aPIKey)
to_camel() {
    python3 -c '
import sys, re
v = sys.argv[1]
# Normalize acronym runs: APIKey → ApiKey, HTTPRequest → HttpRequest
v = re.sub(r"([A-Z]+)([A-Z][a-z])", lambda m: m.group(1).capitalize() + m.group(2), v)
v = re.sub(r"([A-Z]+)$", lambda m: m.group(1).capitalize(), v)
# Lowercase first character
print(v[:1].lower() + v[1:])
' "$1"
}

# StudlyCase → canonical StudlyCase, normalizing acronym runs.
#   APIKey       → ApiKey
#   HTTPRequest  → HttpRequest
#   OAuth2Token  → Oauth2Token
#   SchoolCategory → SchoolCategory  (no-op)
to_canonical_studly() {
    python3 -c '
import sys, re
v = sys.argv[1]
v = re.sub(r"([A-Z]+)([A-Z][a-z])", lambda m: m.group(1).capitalize() + m.group(2), v)
v = re.sub(r"([A-Z]+)$", lambda m: m.group(1).capitalize(), v)
print(v[:1].upper() + v[1:])
' "$1"
}

to_action_method() {
    python3 -c '
import sys
parts = sys.argv[1].split("-")
first = parts[0]
rest = "".join(p[:1].upper() + p[1:] for p in parts[1:])
print(first + rest)
' "$1"
}

humanize_kebab() {
    python3 -c '
import sys
print(" ".join(p[:1].upper() + p[1:] for p in sys.argv[1].split("-")))
' "$1"
}

# Naive plural: handles -y→-ies, keeps the rest predictable
pluralize() {
    local w="$1"
    if [[ "$w" =~ [^aeiou]y$ ]]; then
        echo "${w%y}ies"
    elif [[ "$w" =~ (s|x|z|ch|sh)$ ]]; then
        echo "${w}es"
    else
        echo "${w}s"
    fi
}

RESOURCE_SNAKE=$(to_snake "$RESOURCE")                        # school_category
RESOURCE_CAMEL=$(to_camel "$RESOURCE")                        # audience / schoolCategory
RESOURCE_CANONICAL=$(to_canonical_studly "$RESOURCE")         # ApiKey (from APIKey)
RESOURCE_PLURAL=$(pluralize "$RESOURCE_SNAKE")                # school_categories
MODULE_LOWER=$(echo "$MODULE" | tr '[:upper:]' '[:lower:]')  # shows

if [[ -z "$ROUTE_SEGMENT" ]]; then
    ROUTE_SEGMENT="${RESOURCE_PLURAL//_/-}"                   # school-categories
fi

ROUTE_SEGMENT_UNDERSCORE="${ROUTE_SEGMENT//-/_}"              # school_categories (for view paths / route names)

SERVICE_NAME="${RESOURCE_CAMEL}ApiService"                    # audienceApiService
SERVICE_CLASS="${RESOURCE}ApiService"                         # AudienceApiService
SERVICE_IFACE="${RESOURCE}ApiServiceInterface"                # AudienceApiServiceInterface
CONTROLLER_CLASS="${RESOURCE}Controller"                      # AudienceController
STORE_REQUEST="${RESOURCE}StoreRequest"                       # AudienceStoreRequest
UPDATE_REQUEST="${RESOURCE}UpdateRequest"                     # AudienceUpdateRequest
CONTROLLER_FQCN="\\\\App\\\\Modules\\\\${MODULE}\\\\Controllers\\\\${CONTROLLER_CLASS}"
ROUTE_NAME="admin.${MODULE_LOWER}.${ROUTE_SEGMENT_UNDERSCORE}"  # admin.shows.audiences
REORDER_ROUTE_NAME="${ROUTE_NAME}.reorder"
SAVE_ORDER_ROUTE_NAME="${ROUTE_NAME}.save_order"
LANG_PREFIX="${RESOURCE_PLURAL}"                              # school_categories  (used as lang key prefix)
VIEW_PATH="${MODULE_LOWER}/${ROUTE_SEGMENT_UNDERSCORE}"       # shows/audiences
MODULE_DIR="app/Modules/${MODULE}"

# Human-readable labels for language stubs
RESOURCE_LABEL=$(echo "${RESOURCE_SNAKE//_/ }" | awk '{$1=toupper(substr($1,1,1))substr($1,2)}1')
RESOURCE_LOWER=$(echo "$RESOURCE_LABEL" | tr '[:upper:]' '[:lower:]')

SERVICE_IFACE_ACTIONS=""
SERVICE_CLASS_ACTIONS=""
CONTROLLER_ACTIONS=""
ROUTE_APPEND_BLOCK=""
SHOW_ACTION_BUTTONS=""
LANG_EN_ACTIONS=""
LANG_ES_ACTIONS=""
CUSTOM_ACTIONS_SUMMARY=""
CSV_ROUTE_BLOCK=""
CSV_SERVICE_IFACE=""
CSV_SERVICE_IMPL=""
CSV_CONTROLLER_ACTIONS=""
CSV_TOOLBAR_ACTIONS=""
CSV_INDEX_COMMENT=""
CSV_LANG_EN=""
CSV_LANG_ES=""

for ACTION in "${CUSTOM_ACTIONS[@]}"; do
    ACTION_METHOD="$(to_action_method "$ACTION")"
    ACTION_LABEL="$(humanize_kebab "$ACTION")"
    ACTION_ROUTE_NAME="${ROUTE_NAME}.${ACTION//-/_}"
    ACTION_PREFIX="${LANG_PREFIX}_${ACTION//-/_}"

    SERVICE_IFACE_ACTIONS+=$'\n\n'"    /** @return ApiResponse */"$'\n'"    public function ${ACTION_METHOD}(int|string \$id): array;"

    SERVICE_CLASS_ACTIONS+=$'\n\n'"    public function ${ACTION_METHOD}(int|string \$id): array"$'\n'"    {"$'\n'"        return \$this->apiClient->post(\$this->resourcePath() . '/' . \$id . '/${ACTION}');"$'\n'"    }"

    CONTROLLER_ACTIONS+=$'\n\n'"    public function ${ACTION_METHOD}(string \$id): RedirectResponse"$'\n'"    {"$'\n'"        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->${ACTION_METHOD}(\$id));"$'\n\n'"        if (! \$response['ok']) {"$'\n'"            return \$this->failApi(\$response, lang('${MODULE}.${ACTION_PREFIX}_failed'), route_to('${ROUTE_NAME}.show', \$id), false);"$'\n'"        }"$'\n\n'"        return redirect()->to(route_to('${ROUTE_NAME}.show', \$id))->with('success', lang('${MODULE}.${ACTION_PREFIX}_success'));"$'\n'"    }"

    ROUTE_APPEND_BLOCK+=$'\n'"    \$routes->post('${ROUTE_SEGMENT}/(:segment)/${ACTION}', '${CONTROLLER_FQCN}::${ACTION_METHOD}/\$1', ['as' => '${ACTION_ROUTE_NAME}']);"

    SHOW_ACTION_BUTTONS+=$(cat <<EOF

                <form method="post" action="<?= route_to('${ACTION_ROUTE_NAME}', \$itemId) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="<?= esc(action_button_class()) ?>">
                        <?= esc(lang('${MODULE}.${ACTION_PREFIX}')) ?>
                    </button>
                </form>
EOF
)

    LANG_EN_ACTIONS+=$'\n'"    '${ACTION_PREFIX}'                   => '${ACTION_LABEL} ${RESOURCE_LABEL}',"$'\n'"    '${ACTION_PREFIX}_success'           => '${ACTION_LABEL} completed successfully for ${RESOURCE_LABEL}.',"$'\n'"    '${ACTION_PREFIX}_failed'            => 'Could not run ${ACTION} for the ${RESOURCE_LOWER}.',"
    LANG_ES_ACTIONS+=$'\n'"    '${ACTION_PREFIX}'                   => '${ACTION_LABEL} ${RESOURCE_LABEL}',"$'\n'"    '${ACTION_PREFIX}_success'           => 'La acción ${ACTION_LABEL} se ejecutó correctamente para el ${RESOURCE_LOWER}.',"$'\n'"    '${ACTION_PREFIX}_failed'            => 'No se pudo ejecutar la acción ${ACTION_LABEL} para el ${RESOURCE_LOWER}.',"

    if [[ -n "$CUSTOM_ACTIONS_SUMMARY" ]]; then
        CUSTOM_ACTIONS_SUMMARY+=", "
    fi
    CUSTOM_ACTIONS_SUMMARY+="${ACTION}"
done

: <<'CSV_SNIPPETS'
if [[ "$CSV_ENABLED" == true ]]; then
    CSV_ROUTE_BLOCK=$'\n'"    \$routes->get('${ROUTE_SEGMENT}/export', '${CONTROLLER_FQCN}::exportCsv', ['as' => '${ROUTE_NAME}.export_csv']);"$'\n'"    \$routes->post('${ROUTE_SEGMENT}/import', '${CONTROLLER_FQCN}::importCsv', ['as' => '${ROUTE_NAME}.import_csv']);"

    CSV_SERVICE_IFACE=$'\n\n'"    /**\n     * @param array<string, mixed> \$filters\n     * @return ApiResponse\n     */\n    public function exportCsv(array \$filters = []): array;"$'\n\n'"    /**\n     * @param array<int, array<string, mixed>> \$rows\n     * @return ApiResponse\n     */\n    public function importCsv(array \$rows): array;"

    CSV_SERVICE_IMPL=$'\n\n'"    public function exportCsv(array \$filters = []): array\n    {\n        return \$this->list(\$filters);\n    }"$'\n\n'"    public function importCsv(array \$rows): array\n    {\n        \$created = [];\n\n        foreach (\$rows as \$row) {\n            if (! is_array(\$row)) {\n                continue;\n            }\n\n            \$created[] = \$this->create(\$row);\n        }\n\n        return [\n            'ok' => true,\n            'status' => 200,\n            'data' => \$created,\n        ];\n    }"

    CSV_CONTROLLER_ACTIONS=$'\n\n'"    public function exportCsv(): ResponseInterface"$'\n'"    {"$'\n'"        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->exportCsv(\$this->request->getGet()));"$'\n'"        if (! \$response['ok']) {"$'\n'"            return \$this->response->setStatusCode(500)->setBody(lang('${MODULE}.${LANG_PREFIX}_csv_export_failed'));"$'\n'"        }"$'\n\n'"        \$rows = \$this->extractItems(\$response);"$'\n'"        \$columns = [${CSV_COLUMNS}];"$'\n'"        \$stream = fopen('php://temp', 'w+');"$'\n'"        fputcsv(\$stream, \$columns);"$'\n\n'"        foreach (\$rows as \$row) {"$'\n'"            if (! is_array(\$row)) {"$'\n'"                continue;"$'\n'"            }"$'\n\n'"            \$line = [];"$'\n'"            foreach (\$columns as \$column) {"$'\n'"                \$line[] = (string) (\$row[\$column] ?? '');"$'\n'"            }"$'\n\n'"            fputcsv(\$stream, \$line);"$'\n'"        }"$'\n\n'"        rewind(\$stream);"$'\n'"        \$csv = stream_get_contents(\$stream) ?: '';"$'\n'"        fclose(\$stream);"$'\n\n'"        return \$this->response"$'\n'"            ->setStatusCode(200)"$'\n'"            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')"$'\n'"            ->setHeader('Content-Disposition', 'attachment; filename=\"${ROUTE_SEGMENT}.csv\"')"$'\n'"            ->setBody(\$csv);"$'\n'"    }"$'\n\n'"    public function importCsv(): RedirectResponse"$'\n'"    {"$'\n'"        \$file = \$this->request instanceof \\CodeIgniter\\HTTP\\IncomingRequest ? \$this->request->getFile('csv_file') : null;"$'\n'"        if (\$file === null || ! \$file->isValid()) {"$'\n'"            return \$this->withError(lang('${MODULE}.${LANG_PREFIX}_csv_invalid_file'), route_to('${ROUTE_NAME}'));"$'\n'"        }"$'\n\n'"        \$handle = fopen(\$file->getTempName(), 'r');"$'\n'"        if (\$handle === false) {"$'\n'"            return \$this->withError(lang('${MODULE}.${LANG_PREFIX}_csv_invalid_file'), route_to('${ROUTE_NAME}'));"$'\n'"        }"$'\n\n'"        \$headers = fgetcsv(\$handle) ?: [];"$'\n'"        \$columns = [${CSV_COLUMNS}];"$'\n'"        \$rows = [];"$'\n\n'"        while ((\$row = fgetcsv(\$handle)) !== false) {"$'\n'"            if (! is_array(\$row) || \$row === []) {"$'\n'"                continue;"$'\n'"            }"$'\n\n'"            \$assoc = [];"$'\n'"            foreach (\$headers as \$index => \$header) {"$'\n'"                if (! is_string(\$header) || ! isset(\$columns[\$index])) {"$'\n'"                    continue;"$'\n'"                }"$'\n\n'"                \$assoc[\$columns[\$index]] = \$row[\$index] ?? '';"$'\n'"            }"$'\n\n'"            if (\$assoc !== []) {"$'\n'"                \$rows[] = \$assoc;"$'\n'"            }"$'\n        }"$'\n\n'"        fclose(\$handle);"$'\n\n'"        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->importCsv(\$rows));"$'\n'"        if (! \$response['ok']) {"$'\n'"            return \$this->failApi(\$response, lang('${MODULE}.${LANG_PREFIX}_csv_import_failed'), route_to('${ROUTE_NAME}'), false);"$'\n'"        }"$'\n\n'"        return redirect()->to(route_to('${ROUTE_NAME}'))->with('success', lang('${MODULE}.${LANG_PREFIX}_csv_import_success'));"$'\n'"    }"

    CSV_TOOLBAR_ACTIONS=$(cat <<EOF
    <?= view('components/table/export_button', [
        'exportUrl' => route_to('${ROUTE_NAME}.export_csv'),
        'label' => '${MODULE}.${LANG_PREFIX}_export_csv',
    ]) ?>
    <?= view('components/form/export_import', [
        'importUrl' => route_to('${ROUTE_NAME}.import_csv'),
        'importLabel' => '${MODULE}.${LANG_PREFIX}_import_csv',
        'previewView' => 'components/form/import_preview',
    ]) ?>
EOF
)

    CSV_INDEX_COMMENT="<?php /* CSV scaffold hooks: components/table/export_button, components/form/export_import, components/form/import_preview */ ?>"

    CSV_LANG_EN=$(cat <<EOF
    '${LANG_PREFIX}_export_csv'           => 'Export ${RESOURCE_LABEL} CSV',
    '${LANG_PREFIX}_import_csv'           => 'Import ${RESOURCE_LABEL} CSV',
    '${LANG_PREFIX}_csv_import_success'   => '${RESOURCE_LABEL} CSV imported successfully.',
    '${LANG_PREFIX}_csv_import_failed'     => 'Could not import the ${RESOURCE_LOWER} CSV file.',
    '${LANG_PREFIX}_csv_export_failed'     => 'Could not export the ${RESOURCE_LOWER} CSV file.',
    '${LANG_PREFIX}_csv_invalid_file'      => 'Please upload a valid CSV file.',
EOF
)
    CSV_LANG_ES=$(cat <<EOF
    '${LANG_PREFIX}_export_csv'           => 'Exportar CSV de ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_import_csv'           => 'Importar CSV de ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_csv_import_success'   => 'El CSV de ${RESOURCE_LABEL} se importó correctamente.',
    '${LANG_PREFIX}_csv_import_failed'     => 'No se pudo importar el CSV del ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_csv_export_failed'     => 'No se pudo exportar el CSV del ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_csv_invalid_file'      => 'Sube un archivo CSV válido.',
EOF
)
fi
CSV_SNIPPETS

# ─── Dynamic Field Generator ──────────────────────────────────────────────────
GENERATED_SNIPPETS=$(python3 - "$FIELDS" "$MODULE" "$LANG_PREFIX" "$RESOURCE_CAMEL" "$RESOURCE" "${CI4_TEMPLATE_JSON:-}" "$API_PATH" <<'PYEOF'
import os
import sys
import json
import re

fields_str = sys.argv[1]
module = sys.argv[2]
lang_prefix = sys.argv[3]
resource_camel = sys.argv[4]
resource = sys.argv[5]
template_json_path = sys.argv[6] if len(sys.argv) > 6 else ""
api_path = sys.argv[7] if len(sys.argv) > 7 else ""
ORDER_FIELD_NAMES = {'order', 'sort_order'}


def php_escape(value: str) -> str:
    return value.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n")


def humanize_field_name(field_name: str) -> str:
    cleaned = field_name.replace('_id', '').replace('_', ' ').strip()
    if not cleaned:
        cleaned = field_name.replace('_', ' ')
    return " ".join(part[:1].upper() + part[1:] for part in cleaned.split())


def default_placeholder(field_type: str, label: str, locale: str) -> str:
    if field_type in ['string', 'text', 'longtext', 'int', 'bigint', 'decimal', 'float']:
        return ('Ingresa ' if locale == 'es' else 'Enter ') + label
    if field_type in ['enum', 'relation', 'date', 'datetime']:
        return ('Selecciona ' if locale == 'es' else 'Select ') + label
    if field_type in ['file', 'image']:
        return ('Elige ' if locale == 'es' else 'Choose ') + label
    return ''


def default_help(field_type: str, label: str, locale: str) -> str:
    if field_type in ['string', 'text', 'longtext']:
        return ('Ingresa ' if locale == 'es' else 'Enter ') + label + '.'
    if field_type in ['int', 'bigint', 'decimal', 'float']:
        return ('Ingresa ' if locale == 'es' else 'Enter ') + label + '.'
    if field_type in ['enum', 'relation']:
        return ('Selecciona ' if locale == 'es' else 'Select ') + label + '.'
    if field_type in ['date', 'datetime']:
        return ('Selecciona ' if locale == 'es' else 'Select ') + label + '.'
    if field_type in ['file', 'image']:
        return ('Elige ' if locale == 'es' else 'Choose ') + label + '.'
    if field_type == 'boolean':
        return ('Activa o desactiva ' if locale == 'es' else 'Toggle ') + label + '.'
    return ''


def default_boolean_state(field_name: str, label: str, locale: str, is_on: bool) -> str:
    seed = f"{field_name} {label}".lower()
    if 'active' in seed:
        if locale == 'es':
            return 'Activo' if is_on else 'Inactivo'
        return 'Active' if is_on else 'Inactive'
    if 'publish' in seed:
        if locale == 'es':
            return 'Publicado' if is_on else 'No publicado'
        return 'Published' if is_on else 'Unpublished'
    if 'visible' in seed:
        return 'Visible' if is_on else ('Oculto' if locale == 'es' else 'Hidden')
    if 'enable' in seed:
        if locale == 'es':
            return 'Habilitado' if is_on else 'Deshabilitado'
        return 'Enabled' if is_on else 'Disabled'
    if locale == 'es':
        return 'Sí' if is_on else 'No'
    return 'Yes' if is_on else 'No'


def load_template_fields(path: str, resource_name: str):
    if not path or not os.path.isfile(path):
        return {}
    try:
        with open(path, encoding='utf-8') as fh:
            data = json.load(fh)
    except Exception:
        return {}
    for entity in data.get('entities', []):
        if not isinstance(entity, dict):
            continue
        if entity.get('name') != resource_name:
            continue
        fields_meta = {}
        for field in entity.get('fields', []):
            if isinstance(field, dict) and field.get('name'):
                fields_meta[str(field['name'])] = field
        return fields_meta
    return {}


template_fields = load_template_fields(template_json_path, resource)

def is_locale_key(locale: object) -> bool:
    return isinstance(locale, str) and re.match(r'^[a-z]{2}(?:-[a-zA-Z0-9]+)*$', locale) is not None

template_locales = {'en', 'es'}
if isinstance(template_fields, dict):
    for meta in template_fields.values():
        if not isinstance(meta, dict):
            continue
        i18n = meta.get('i18n')
        if not isinstance(i18n, dict):
            continue
        for locale in i18n.keys():
            if is_locale_key(locale):
                template_locales.add(str(locale))

locales = ['en', 'es'] + sorted(
    [locale for locale in template_locales if locale not in {'en', 'es'}]
)
lang_fields = {locale: [] for locale in locales}

# Parse fields
fields = []
if fields_str:
    for item in fields_str.split(','):
        if not item.strip():
            continue
        parts = item.split(':')
        name = parts[0]
        field_type = parts[1] if len(parts) > 1 else 'string'
        required = False
        enum_options = []
        relation_table = ""
        relation_path = ""

        for part in parts[2:]:
            if part == 'required':
                required = True
            elif field_type == 'enum':
                enum_options = part.split('|')
            elif field_type == 'relation':
                # Accept `categories` or `categories=/catalog/categories`
                # (explicit API path for the related resource).
                if '=' in part:
                    relation_table, relation_path = part.split('=', 1)
                else:
                    relation_table = part

        fields.append({
            'name': name,
            'type': field_type,
            'required': required,
            'enum_options': enum_options,
            'relation_table': relation_table,
            'relation_path': relation_path
        })
else:
    fields = [{
        'name': 'name',
        'type': 'string',
        'required': True,
        'enum_options': [],
        'relation_table': '',
        'relation_path': ''
    }]

req_fields = []
req_rules = []
req_payload = []
index_headers = []
index_rows = []
create_fields = []
edit_fields = []
show_rows = []
reorder_field = ''
reorder_display_key = ''
relations = []
csv_columns = []


def to_camel_py(value: str) -> str:
    parts = [p for p in value.split('_') if p]
    if not parts:
        return value
    return parts[0] + ''.join(p[:1].upper() + p[1:] for p in parts[1:])


def derive_relation_path(var: str) -> str:
    base = api_path.rstrip('/')
    parent = base.rsplit('/', 1)[0] if base.count('/') > 1 else ''
    return parent + '/' + var.replace('_', '-')

for f in fields:
    name = f['name']
    ftype = f['type']
    req = f['required']
    enum_opts = f['enum_options']
    rel_table = f['relation_table']
    is_order_field = name in ORDER_FIELD_NAMES and ftype in ['int', 'bigint']

    if ftype == 'relation' and rel_table:
        rel_var = re.sub(r'[^a-zA-Z0-9_]', '', rel_table)
        rel_method = to_camel_py(rel_var)
        if rel_method in {'list', 'get', 'create', 'update', 'delete'}:
            sys.stderr.write(
                "relation variable '{}' collides with a base CRUD service method; pick another name\n".format(rel_var)
            )
            sys.exit(1)
        relations.append({
            'field': name,
            'var': rel_var,
            'method': rel_method,
            'path': f['relation_path'] or derive_relation_path(rel_var),
        })
    meta = template_fields.get(name, {}) if isinstance(template_fields, dict) else {}
    if not isinstance(meta, dict):
        meta = {}
    i18n = meta.get('i18n') if isinstance(meta.get('i18n'), dict) else {}
    base_label = str(meta.get('label') or humanize_field_name(name))
    base_placeholder = str(meta.get('placeholder') or default_placeholder(ftype, base_label, 'en'))
    base_help = str(meta.get('help') or default_help(ftype, base_label, 'en'))

    if not is_order_field and not reorder_display_key:
        reorder_display_key = name
    if is_order_field and not reorder_field:
        reorder_field = name
    
    req_fields.append("'{}'".format(name))
    
    rule = ""
    if ftype == 'string':
        rule = 'required|min_length[2]|max_length[255]' if req else 'permit_empty|string|max_length[255]'
    elif ftype in ['text', 'longtext']:
        rule = 'required|string' if req else 'permit_empty|string'
    elif ftype in ['int', 'bigint']:
        rule = 'permit_empty|integer' if is_order_field else ('required|integer' if req else 'permit_empty|integer')
    elif ftype in ['decimal', 'float']:
        rule = 'required|decimal' if req else 'permit_empty|decimal'
    elif ftype == 'boolean':
        rule = 'permit_empty'
    elif ftype in ['date', 'datetime']:
        rule = 'required|valid_date' if req else 'permit_empty|valid_date'
    elif ftype == 'enum':
        in_list = ",".join(enum_opts)
        rule = 'required|in_list[{}]'.format(in_list) if req else 'permit_empty|in_list[{}]'.format(in_list)
    elif ftype == 'relation':
        rule = 'required' if req else 'permit_empty'
    else:
        rule = 'permit_empty'
    req_rules.append("            '{}' => '{}',".format(name, rule))
    
    if ftype == 'boolean':
        payload_line = "            '{}' => $this->postBool('{}'),".format(name, name)
    elif is_order_field:
        payload_line = "            '{}' => $this->postInt('{}', 0),".format(name, name)
    elif ftype in ['int', 'bigint', 'relation']:
        payload_line = "            '{}' => $this->postInt('{}'),".format(name, name)
    elif ftype in ['decimal', 'float']:
        payload_line = "            '{}' => (float) $this->postString('{}'),".format(name, name)
    else:
        payload_line = "            '{}' => $this->postString('{}'),".format(name, name)
    req_payload.append(payload_line)
    
    for locale in locales:
        locale_meta = i18n.get(locale, {}) if isinstance(i18n, dict) else {}
        if not isinstance(locale_meta, dict):
            locale_meta = {}

        label = str(locale_meta.get('label') or base_label)
        placeholder = str(locale_meta.get('placeholder') or base_placeholder or default_placeholder(ftype, label, locale))
        help_text = str(locale_meta.get('help') or base_help or default_help(ftype, label, locale))

        lang_fields[locale].append("    'field_{}' => '{}',".format(name, php_escape(label)))
        if placeholder:
            lang_fields[locale].append("    'field_{}_placeholder' => '{}',".format(name, php_escape(placeholder)))
        if help_text:
            lang_fields[locale].append("    'field_{}_help' => '{}',".format(name, php_escape(help_text)))
        if ftype == 'boolean':
            lang_fields[locale].append("    'field_{}_on' => '{}',".format(name, php_escape(default_boolean_state(name, label, locale, True))))
            lang_fields[locale].append("    'field_{}_off' => '{}',".format(name, php_escape(default_boolean_state(name, label, locale, False))))
    
    header = """                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('{name}')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('{name}')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('{module}.field_{name}')])) ?>">
                                <span><?= lang('{module}.field_{name}') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('{name}')"></span>
                            </button>
                        </th>"""
    header = header.replace('{name}', name).replace('{module}', module)
    if not is_order_field:
        index_headers.append(header)
    
    if ftype == 'boolean':
        row_td = """                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex items-center">
                                    <template x-if="row.{name}">
                                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <template x-if="!row.{name}">
                                        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </template>
                                </span>
                            </td>"""
    elif ftype in ['date', 'datetime']:
        row_td = """                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.{name})"></td>"""
    elif ftype == 'image':
        row_td = """                            <td class="<?= esc(table_td_class()) ?>">
                                <template x-if="row.{name}">
                                    <img class="h-10 w-10 rounded-full object-cover" :src="row.{name}" alt="">
                                </template>
                                <template x-if="!row.{name}">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>"""
    elif ftype == 'enum':
        row_td = """                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10" x-text="String(row.{name} ?? '-')"></span>
                            </td>"""
    else:
        td_class = 'primary' if name == 'name' else 'muted'
        row_td = """                            <td class="<?= esc(table_td_class('{td_class}')) ?>" x-text="String(row.{name} ?? '-')"></td>""".replace('{td_class}', td_class)
    
    row_td = row_td.replace('{name}', name)
    if not is_order_field:
        index_rows.append(row_td)

    req_php = 'true' if req else 'false'
    if ftype == 'string':
        comp = """        <?= view('components/form/text', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype in ['text', 'longtext']:
        comp = """        <?= view('components/form/textarea', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype in ['int', 'bigint']:
        comp = """        <?= view('components/form/number', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype in ['decimal', 'float']:
        comp = """        <?= view('components/form/decimal', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype == 'boolean':
        comp = """        <?= view('components/form/boolean', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'value' => $item['{name}'] ?? false,
            'on_label' => '{module}.field_{name}_on',
            'off_label' => '{module}.field_{name}_off',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype == 'date':
        comp = """        <?= view('components/form/date', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype == 'datetime':
        comp = """        <?= view('components/form/datetime', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype == 'enum':
        opts_php = ",\n".join([f"                '{o}' => '{o.title()}'" for o in enum_opts])
        comp = """        <?= view('components/form/select', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'options' => [
{opts_php}
            ],
            'value' => $item['{name}'] ?? '',
            'errors' => $errors ?? []
        ]) ?>""".replace('{opts_php}', opts_php)
    elif ftype == 'relation':
        comp = """        <?= view('components/form/relation', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'options' => ${rel_table} ?? [],
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'value' => $item['{name}'] ?? '',
            'errors' => $errors ?? []
        ]) ?>""".replace('{rel_table}', rel_table)
    elif ftype == 'file':
        comp = """        <?= view('components/form/file', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'value' => $item['{name}'] ?? '',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    elif ftype == 'image':
        comp = """        <?= view('components/form/image', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'value' => $item['{name}'] ?? '',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
    else:
        comp = """        <?= view('components/form/text', [
            'name' => '{name}',
            'label' => '{module}.field_{name}',
            'required' => {req_php},
            'value' => $item['{name}'] ?? '',
            'placeholder' => '{module}.field_{name}_placeholder',
            'help' => '{module}.field_{name}_help',
            'errors' => $errors ?? []
        ]) ?>"""
        
    comp = comp.replace('{name}', name).replace('{module}', module).replace('{req_php}', req_php)
    if not is_order_field:
        create_fields.append(comp)
        edit_fields.append(comp)
    
    if ftype == 'boolean':
        val_expr = "view('components/table/boolean_cell', ['value' => ${RESOURCE_CAMEL}['{name}'] ?? false])".replace('{RESOURCE_CAMEL}', resource_camel).replace('{name}', name)
        show_row = """            <?= view('components/display/field_row', [
                'label' => '{module}.field_{name}',
                'value' => {val_expr},
                'isHtml' => true
            ]) ?>"""
    elif ftype == 'image':
        val_expr = "! empty(${RESOURCE_CAMEL}['{name}']) ? '<img class=\"h-20 w-20 object-cover rounded-lg\" src=\"' . esc(${RESOURCE_CAMEL}['{name}']) . '\" alt=\"\">' : '—'".replace('{RESOURCE_CAMEL}', resource_camel).replace('{name}', name)
        show_row = """            <?= view('components/display/field_row', [
                'label' => '{module}.field_{name}',
                'value' => {val_expr},
                'isHtml' => true
            ]) ?>"""
    elif ftype == 'enum':
        val_expr = "! empty(${RESOURCE_CAMEL}['{name}']) ? '<span class=\"inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10\">' . esc(${RESOURCE_CAMEL}['{name}']) . '</span>' : '—'".replace('{RESOURCE_CAMEL}', resource_camel).replace('{name}', name)
        show_row = """            <?= view('components/display/field_row', [
                'label' => '{module}.field_{name}',
                'value' => {val_expr},
                'isHtml' => true
            ]) ?>"""
    elif ftype == 'relation':
        rel_lookup = f"(${rel_table}[(string) (${resource_camel}['{name}'] ?? '')] ?? (${resource_camel}['{name}'] ?? '—'))"
        show_row = """            <?= view('components/display/field_row', [
                'label' => '{module}.field_{name}',
                'value' => {val_expr}
            ]) ?>""".replace('{module}', module).replace('{name}', name).replace('{val_expr}', rel_lookup)
    else:
        val_expr = "${RESOURCE_CAMEL}['{name}'] ?? '—'".replace('{RESOURCE_CAMEL}', resource_camel).replace('{name}', name)
        show_row = """            <?= view('components/display/field_row', [
                'label' => '{module}.field_{name}',
                'value' => {val_expr}
            ]) ?>"""
            
    show_row = show_row.replace('{module}', module).replace('{name}', name).replace('{val_expr}', val_expr)
    if not is_order_field:
        show_rows.append(show_row)
        csv_columns.append("'{name}'".format(name=name))

# ── Relation (FK) wiring: service fetchers, controller option loaders,
# view data lines, index filter selects, and data() allowed filters ──
relation_service_iface = []
relation_service_impl = []
relation_controller_helpers = []
relation_view_data = []
relation_filter_selects = []
relation_filter_fields = []

for rel in relations:
    field = rel['field']
    var = rel['var']
    method = rel['method']
    path = rel['path']
    options_method = method + 'Options'

    relation_filter_fields.append("'{}'".format(field))

    relation_service_iface.append(
        "\n\n    /**\n"
        "     * @param array<string, mixed> $filters\n"
        "     * @return ApiResponse\n"
        "     */\n"
        "    public function {method}(array $filters = []): array;".format(method=method)
    )

    relation_service_impl.append(
        "\n\n    /**\n"
        "     * @param array<string, mixed> $filters\n"
        "     * @return ApiResponse\n"
        "     */\n"
        "    public function {method}(array $filters = []): array\n"
        "    {{\n"
        "        return $this->apiClient->get('{path}', $filters);\n"
        "    }}".format(method=method, path=path)
    )

    relation_controller_helpers.append(
        "\n\n    /** @return array<string, string> */\n"
        "    private function {options_method}(): array\n"
        "    {{\n"
        "        $response = $this->safeApiCall(fn () => $this->{service_prop}->{method}(['limit' => 100]));\n"
        "        $options = [];\n\n"
        "        foreach ($this->extractItems($response) as $item) {{\n"
        "            if (! is_array($item) || ! isset($item['id'])) {{\n"
        "                continue;\n"
        "            }}\n"
        "            $label = $item['name'] ?? $item['title'] ?? $item['label'] ?? $item['email'] ?? $item['id'];\n"
        "            $options[(string) $item['id']] = (string) $label;\n"
        "        }}\n\n"
        "        return $options;\n"
        "    }}".format(options_method=options_method, service_prop=resource_camel + 'Service', method=method)
    )

    relation_view_data.append(
        "            '{var}' => $this->{options_method}(),".format(var=var, options_method=options_method)
    )

    relation_filter_selects.append(
        """    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('{module}.field_{field}') ?></label>
        <select name="{field}" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= esc(lang('App.all')) ?></option>
            <?php $selected_{field} = (string) request()->getGet('{field}'); ?>
            <?php foreach (($${var} ?? []) as $optValue => $optLabel): ?>
                <option value="<?= esc((string) $optValue, 'attr') ?>" <?= $selected_{field} === (string) $optValue ? 'selected' : '' ?>><?= esc((string) $optLabel) ?></option>
            <?php endforeach; ?>
        </select>
    </div>""".format(module=module, field=field, var=var)
    )

relation_fields_data = "\n".join(
    "            '{var}' => ${var} ?? [],".format(var=rel['var']) for rel in relations
)

output = {
    'req_fields': ", ".join(req_fields),
    'req_rules': "\n".join(req_rules),
    'req_payload': "\n".join(req_payload),
    'index_headers': "\n".join(index_headers),
    'index_rows': "\n".join(index_rows),
    'create_fields': "\n\n".join(create_fields),
    'edit_fields': "\n\n".join(edit_fields),
    'show_rows': "\n".join(show_rows),
    'lang_fields': {locale: "\n".join(lines) for locale, lines in lang_fields.items()},
    'lang_locales': locales,
    'reorder_field': reorder_field,
    'reorder_display_key': reorder_display_key,
    'relation_service_iface': "".join(relation_service_iface),
    'relation_service_impl': "".join(relation_service_impl),
    'relation_controller_helpers': "".join(relation_controller_helpers),
    'relation_view_data': "\n".join(relation_view_data),
    'relation_filter_selects': "\n".join(relation_filter_selects),
    'relation_filter_fields': ", ".join(relation_filter_fields),
    'relation_fields_data': relation_fields_data,
    'csv_columns': ", ".join(csv_columns),
}
print(json.dumps(output))
PYEOF
)

extract_snippet() {
    local key="$1"
    echo "$GENERATED_SNIPPETS" | php -r '
        $json = json_decode(file_get_contents("php://stdin"), true);
        echo $json[$argv[1]] ?? "";
    ' -- "$key"
}

extract_json_snippet() {
    local key="$1"
    echo "$GENERATED_SNIPPETS" | php -r '
        $json = json_decode(file_get_contents("php://stdin"), true);
        echo json_encode($json[$argv[1]] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ' -- "$key"
}

REQ_FIELDS=$(extract_snippet 'req_fields')
REQ_RULES=$(extract_snippet 'req_rules')
REQ_PAYLOAD=$(extract_snippet 'req_payload')
VIEW_INDEX_HEADERS=$(extract_snippet 'index_headers')
VIEW_INDEX_ROWS=$(extract_snippet 'index_rows')
VIEW_CREATE_FIELDS=$(extract_snippet 'create_fields')
VIEW_EDIT_FIELDS=$(extract_snippet 'edit_fields')
VIEW_SHOW_ROWS=$(extract_snippet 'show_rows')
RELATION_SERVICE_IFACE=$(extract_snippet 'relation_service_iface')
RELATION_SERVICE_IMPL=$(extract_snippet 'relation_service_impl')
RELATION_CONTROLLER_HELPERS=$(extract_snippet 'relation_controller_helpers')
RELATION_VIEW_DATA=$(extract_snippet 'relation_view_data')
RELATION_FILTER_SELECTS=$(extract_snippet 'relation_filter_selects')
RELATION_FILTER_FIELDS=$(extract_snippet 'relation_filter_fields')
RELATION_FIELDS_DATA=$(extract_snippet 'relation_fields_data')
CSV_COLUMNS=$(extract_snippet 'csv_columns')
CSV_SNIPPETS_JSON=$(python3 - "$CSV_ENABLED" "$RESOURCE" "$MODULE" "$RESOURCE_CAMEL" "$LANG_PREFIX" "$RESOURCE_LABEL" "$RESOURCE_LOWER" "$ROUTE_SEGMENT" "$ROUTE_NAME" "$API_PATH" "$CSV_COLUMNS" <<'PYEOF'
import json
import sys

enabled = sys.argv[1] == 'true'
resource = sys.argv[2]
module = sys.argv[3]
resource_camel = sys.argv[4]
lang_prefix = sys.argv[5]
resource_label = sys.argv[6]
resource_lower = sys.argv[7]
route_segment = sys.argv[8]
route_name = sys.argv[9]
api_path = sys.argv[10]
csv_columns = [item.strip().strip("'") for item in sys.argv[11].split(',') if item.strip()]

if enabled:
    controller_fqcn = f"\\\\App\\\\Modules\\\\{module}\\\\Controllers\\\\{resource}Controller"
    csv_route_block = (
        f"\n    $routes->get('{route_segment}/export', '{controller_fqcn}::exportCsv', ['as' => '{route_name}.export_csv']);\n"
        f"    $routes->post('{route_segment}/import', '{controller_fqcn}::importCsv', ['as' => '{route_name}.import_csv']);"
    )
    csv_service_iface = (
        "\n\n    /**\n"
        "     * @param array<string, mixed> $filters\n"
        "     * @return ApiResponse\n"
        "     */\n"
        "    public function exportCsv(array $filters = []): array;\n\n"
        "    /**\n"
        "     * @param array<int, array<string, mixed>> $rows\n"
        "     * @return ApiResponse\n"
        "     */\n"
        "    public function importCsv(array $rows): array;"
    )
    csv_service_impl = (
        "\n\n    public function exportCsv(array $filters = []): array\n"
        "    {\n"
        "        return $this->list($filters);\n"
        "    }\n\n"
        "    public function importCsv(array $rows): array\n"
        "    {\n"
        "        $created = [];\n\n"
        "        foreach ($rows as $row) {\n"
        "            if (! is_array($row)) {\n"
        "                continue;\n"
        "            }\n\n"
        "            $created[] = $this->create($row);\n"
        "        }\n\n"
        "        return [\n"
        "            'ok' => true,\n"
        "            'status' => 200,\n"
        "            'data' => $created,\n"
        "        ];\n"
        "    }"
    )
    csv_controller_actions = (
        "\n\n    public function exportCsv(): ResponseInterface\n"
        "    {\n"
        f"        $response = $this->safeApiCall(fn () => $this->{resource_camel}Service->exportCsv($this->request->getGet()));\n"
        f"        if (! $response['ok']) {{\n"
        f"            return $this->response->setStatusCode(500)->setBody(lang('{module}.{lang_prefix}_csv_export_failed'));\n"
        "        }\n\n"
        "        $rows = $this->extractItems($response);\n"
        f"        $columns = [{', '.join(csv_columns)}];\n"
        "        $stream = fopen('php://temp', 'w+');\n"
        "        fputcsv($stream, $columns);\n\n"
        "        foreach ($rows as $row) {\n"
        "            if (! is_array($row)) {\n"
        "                continue;\n"
        "            }\n\n"
        "            $line = [];\n"
        "            foreach ($columns as $column) {\n"
        "                $line[] = (string) ($row[$column] ?? '');\n"
        "            }\n\n"
        "            fputcsv($stream, $line);\n"
        "        }\n\n"
        "        rewind($stream);\n"
        "        $csv = stream_get_contents($stream) ?: '';\n"
        "        fclose($stream);\n\n"
        "        return $this->response\n"
        "            ->setStatusCode(200)\n"
        "            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')\n"
        f"            ->setHeader('Content-Disposition', 'attachment; filename=\"{route_segment}.csv\"')\n"
        "            ->setBody($csv);\n"
        "    }\n\n"
        "    public function importCsv(): RedirectResponse\n"
        "    {\n"
        "        $file = $this->request instanceof \\CodeIgniter\\HTTP\\IncomingRequest ? $this->request->getFile('csv_file') : null;\n"
        "        if ($file === null || ! $file->isValid()) {\n"
        f"            return $this->withError(lang('{module}.{lang_prefix}_csv_invalid_file'), route_to('{route_name}'));\n"
        "        }\n\n"
        "        $handle = fopen($file->getTempName(), 'r');\n"
        "        if ($handle === false) {\n"
        f"            return $this->withError(lang('{module}.{lang_prefix}_csv_invalid_file'), route_to('{route_name}'));\n"
        "        }\n\n"
        "        $headers = fgetcsv($handle) ?: [];\n"
        f"        $columns = [{', '.join(csv_columns)}];\n"
        "        $rows = [];\n\n"
        "        while (($row = fgetcsv($handle)) !== false) {\n"
        "            if (! is_array($row) || $row === []) {\n"
        "                continue;\n"
        "            }\n\n"
        "            $assoc = [];\n"
        "            foreach ($headers as $index => $header) {\n"
        "                if (! is_string($header) || ! isset($columns[$index])) {\n"
        "                    continue;\n"
        "                }\n\n"
        "                $assoc[$columns[$index]] = $row[$index] ?? '';\n"
        "            }\n\n"
        "            if ($assoc !== []) {\n"
        "                $rows[] = $assoc;\n"
        "            }\n"
        "        }\n\n"
        "        fclose($handle);\n\n"
        f"        $response = $this->safeApiCall(fn () => $this->{resource_camel}Service->importCsv($rows));\n"
        f"        if (! $response['ok']) {{\n"
        f"            return $this->failApi($response, lang('{module}.{lang_prefix}_csv_import_failed'), route_to('{route_name}'), false);\n"
        "        }\n\n"
        f"        return redirect()->to(route_to('{route_name}'))->with('success', lang('{module}.{lang_prefix}_csv_import_success'));\n"
        "    }"
    )
    csv_toolbar_actions = (
        f"    <?= view('components/table/export_button', [\n"
        f"        'exportUrl' => route_to('{route_name}.export_csv'),\n"
        f"        'label' => '{module}.{lang_prefix}_export_csv',\n"
        "    ]) ?>\n"
        f"    <?= view('components/form/export_import', [\n"
        f"        'importUrl' => route_to('{route_name}.import_csv'),\n"
        f"        'importLabel' => '{module}.{lang_prefix}_import_csv',\n"
        "        'previewView' => 'components/form/import_preview',\n"
        "    ]) ?>"
    )
    csv_index_comment = "<?php /* CSV scaffold hooks: components/table/export_button, components/form/export_import, components/form/import_preview */ ?>"
    csv_lang_en = (
        f"    '{lang_prefix}_export_csv'           => 'Export {resource_label} CSV',\n"
        f"    '{lang_prefix}_import_csv'           => 'Import {resource_label} CSV',\n"
        f"    '{lang_prefix}_csv_import_success'   => '{resource_label} CSV imported successfully.',\n"
        f"    '{lang_prefix}_csv_import_failed'     => 'Could not import the {resource_lower} CSV file.',\n"
        f"    '{lang_prefix}_csv_export_failed'     => 'Could not export the {resource_lower} CSV file.',\n"
        f"    '{lang_prefix}_csv_invalid_file'      => 'Please upload a valid CSV file.',"
    )
    csv_lang_es = (
        f"    '{lang_prefix}_export_csv'           => 'Exportar CSV de {resource_label}',\n"
        f"    '{lang_prefix}_import_csv'           => 'Importar CSV de {resource_label}',\n"
        f"    '{lang_prefix}_csv_import_success'   => 'El CSV de {resource_label} se importó correctamente.',\n"
        f"    '{lang_prefix}_csv_import_failed'     => 'No se pudo importar el CSV del {resource_lower}.',\n"
        f"    '{lang_prefix}_csv_export_failed'     => 'No se pudo exportar el CSV del {resource_lower}.',\n"
        f"    '{lang_prefix}_csv_invalid_file'      => 'Sube un archivo CSV válido.',"
    )
else:
    csv_route_block = csv_service_iface = csv_service_impl = csv_controller_actions = csv_toolbar_actions = csv_index_comment = csv_lang_en = csv_lang_es = ""

print(json.dumps({
    'csv_route_block': csv_route_block,
    'csv_service_iface': csv_service_iface,
    'csv_service_impl': csv_service_impl,
    'csv_controller_actions': csv_controller_actions,
    'csv_toolbar_actions': csv_toolbar_actions,
    'csv_index_comment': csv_index_comment,
    'csv_lang_en': csv_lang_en,
    'csv_lang_es': csv_lang_es,
}))
PYEOF
)
extract_csv_snippet() {
    local key="$1"
    echo "$CSV_SNIPPETS_JSON" | php -r '
        $json = json_decode(file_get_contents("php://stdin"), true);
        echo $json[$argv[1]] ?? "";
    ' -- "$key"
}
CSV_ROUTE_BLOCK=$(extract_csv_snippet 'csv_route_block')
CSV_SERVICE_IFACE=$(extract_csv_snippet 'csv_service_iface')
CSV_SERVICE_IMPL=$(extract_csv_snippet 'csv_service_impl')
CSV_CONTROLLER_ACTIONS=$(extract_csv_snippet 'csv_controller_actions')
CSV_TOOLBAR_ACTIONS=$(extract_csv_snippet 'csv_toolbar_actions')
CSV_INDEX_COMMENT=$(extract_csv_snippet 'csv_index_comment')
CSV_LANG_EN=$(extract_csv_snippet 'csv_lang_en')
CSV_LANG_ES=$(extract_csv_snippet 'csv_lang_es')
LANG_LOCALES_JSON=$(extract_json_snippet 'lang_locales')
LANG_FIELDS_JSON=$(extract_json_snippet 'lang_fields')
REORDER_FIELD=$(extract_snippet 'reorder_field')
REORDER_DISPLAY_KEY=$(extract_snippet 'reorder_display_key')
HAS_REORDER=false
if [[ -n "$REORDER_FIELD" ]]; then
    HAS_REORDER=true
fi

LANG_LOCALE_LIST=()
while IFS= read -r locale; do
    [[ -n "$locale" ]] || continue
    LANG_LOCALE_LIST+=("$locale")
done < <(printf '%s' "$LANG_LOCALES_JSON" | jq -r '.[]')

REORDER_ROUTE_BLOCK=""
REORDER_CONTROLLER_ACTIONS=""
REORDER_SHOW_BUTTON=""
REORDER_VIEW_BLOCK=""
REORDER_ROUTE_APPEND=""
TOOLBAR_REORDER_BUTTON=""
REORDER_UPDATE_PAYLOAD=""
if [[ "$HAS_REORDER" == true ]]; then
    REORDER_ROUTE_BLOCK=$'\n'"    \$routes->get('${ROUTE_SEGMENT}/reorder', '${CONTROLLER_FQCN}::reorder', ['as' => '${REORDER_ROUTE_NAME}', 'filter' => 'permission:${MODULE_LOWER}.write']);"$'\n'"    \$routes->post('${ROUTE_SEGMENT}/reorder', '${CONTROLLER_FQCN}::saveOrder', ['as' => '${SAVE_ORDER_ROUTE_NAME}', 'filter' => 'permission:${MODULE_LOWER}.write']);"

    REORDER_CONTROLLER_ACTIONS=$'\n\n'"    public function reorder(): string|RedirectResponse"$'\n'"    {"$'\n'"        \$deny = \$this->requireWrite();"$'\n'"        if (\$deny !== null) {"$'\n'"            return \$deny;"$'\n'"        }"$'\n\n'"        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->list(['limit' => 250, 'sort' => '${REORDER_FIELD}']));"$'\n'"        \$items = \$this->extractItems(\$response);"$'\n\n'"        return \$this->render('${VIEW_PATH}/reorder', ["$'\n'"            'title' => lang('${MODULE}.${LANG_PREFIX}_title') . ' - ' . lang('${MODULE}.field_${REORDER_FIELD}'),"$'\n'"            'items' => \$items,"$'\n'"        ]);"$'\n'"    }"$'\n\n'"    public function saveOrder(): ResponseInterface"$'\n'"    {"$'\n'"        \$deny = \$this->requireWrite();"$'\n'"        if (\$deny !== null) {"$'\n'"            return \$this->response->setJSON(["$'\n'"                'ok' => false,"$'\n'"                'message' => lang('App.access_denied'),"$'\n'"            ])->setStatusCode(403);"$'\n'"        }"$'\n\n'"        \$request = \$this->request;"$'\n'"        if (! \$request instanceof \\CodeIgniter\\HTTP\\IncomingRequest) {"$'\n'"            return \$this->response->setJSON(["$'\n'"                'ok' => false,"$'\n'"                'message' => 'Invalid request type',"$'\n'"            ])->setStatusCode(400);"$'\n'"        }"$'\n\n'"        \$json = \$request->getJSON(true);"$'\n'"        \$jsonArray = is_array(\$json) ? \$json : [];"$'\n'"        \$items = \$jsonArray['items'] ?? [];"$'\n\n'"        if (! is_array(\$items)) {"$'\n'"            return \$this->response->setJSON(["$'\n'"                'ok' => false,"$'\n'"                'message' => 'Invalid payload structure',"$'\n'"            ])->setStatusCode(400);"$'\n'"        }"$'\n\n'"        foreach (\$items as \$item) {"$'\n'"            \$id = (string) (\$item['id'] ?? '');"$'\n'"            \$value = isset(\$item['sort_order']) ? (int) \$item['sort_order'] : 0;"$'\n\n'"            if (\$id !== '') {"$'\n'"                \$this->${RESOURCE_CAMEL}Service->update(\$id, ['${REORDER_FIELD}' => \$value]);"$'\n'"            }"$'\n'"        }"$'\n\n'"        return \$this->response->setJSON(["$'\n'"            'ok' => true,"$'\n'"            'message' => lang('Files.gallery_save_success') ?? 'Order saved.',"$'\n'"        ]);"$'\n'"    }"

    REORDER_SHOW_BUTTON=$'\n'"                <a href=\"<?= route_to('${REORDER_ROUTE_NAME}') ?>\" class=\"<?= esc(action_button_class('neutral')) ?>\">"$'\n'"                    <?= ui_icon('layers', 'h-3.5 w-3.5') ?>"$'\n'"                    <?= esc(lang('${MODULE}.field_${REORDER_FIELD}') ?? lang('App.reorder')) ?>"$'\n'"                </a>"
    TOOLBAR_REORDER_BUTTON=$(cat <<EOF
    <a href="<?= route_to('${REORDER_ROUTE_NAME}') ?>" class="<?= esc(action_button_class('neutral')) ?>">
        <?= ui_icon('layers', 'h-3.5 w-3.5') ?>
        <?= esc(lang('App.reorder')) ?>
    </a>
EOF
)
    REORDER_UPDATE_PAYLOAD=$(cat <<EOF
    public function payload(): array
    {
        \$payload = parent::payload();
        unset(\$payload['${REORDER_FIELD}']);

        return \$payload;
    }
EOF
)
fi

if [[ -n "$REORDER_SHOW_BUTTON" ]]; then
    SHOW_ACTION_BUTTONS+="$REORDER_SHOW_BUTTON"
fi

# ─── Cross-module route collision detection ────────────────────────────────────
# Catches the realistic mistake of two modules registering the same route name
# (which would produce the same URL via `admin/{module_lower}/{segment}`).
# A re-scaffold within the same module is allowed (the inner per-file check
# below treats it as a no-op SKIP). Conflicts in OTHER modules are fatal.
THIS_MODULE_ROUTES="app/Modules/${MODULE}/Config/Routes.php"
CONFLICTING_FILES=()
if compgen -G "app/Modules/*/Config/Routes.php" >/dev/null; then
    while IFS= read -r -d '' route_file; do
        if [[ "$route_file" == "$THIS_MODULE_ROUTES" ]]; then
            continue
        fi
        if grep -qF "'${ROUTE_NAME}'" "$route_file" 2>/dev/null \
            || grep -qF "\"${ROUTE_NAME}\"" "$route_file" 2>/dev/null; then
            CONFLICTING_FILES+=("$route_file")
        fi
    done < <(find app/Modules -type f -name 'Routes.php' -print0 2>/dev/null)
fi
if [[ -f app/Config/Routes.php ]] && grep -qF "'${ROUTE_NAME}'" app/Config/Routes.php 2>/dev/null; then
    CONFLICTING_FILES+=('app/Config/Routes.php')
fi

if [[ ${#CONFLICTING_FILES[@]} -gt 0 ]]; then
    echo -e "${RED}❌ Route name '${ROUTE_NAME}' is already registered in another module:${NC}" >&2
    for f in "${CONFLICTING_FILES[@]}"; do
        echo -e "${RED}   - ${f}${NC}" >&2
    done
    echo -e "${YELLOW}Pick a different resource/module pair, or remove the conflicting module first" >&2
    echo -e "with: bash bin/remove-module.sh <Resource> <Module>${NC}" >&2
    exit 6
fi

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Admin Module Scaffolding — ${RESOURCE} in ${MODULE}${NC}"
if [[ "$DRY_RUN" == true ]]; then
    echo -e "${YELLOW}DRY-RUN MODE — no files will be written${NC}"
fi
if [[ "$FORCE" == true ]]; then
    echo -e "${YELLOW}FORCE MODE — existing files will be overwritten${NC}"
fi
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""
printf "  %-18s %s\n" "Resource:"     "$RESOURCE"
printf "  %-18s %s\n" "Module:"       "$MODULE"
printf "  %-18s %s\n" "API path:"     "$API_PATH"
printf "  %-18s %s\n" "Route:"        "admin/${MODULE_LOWER}/${ROUTE_SEGMENT}"
printf "  %-18s %s\n" "Route name:"   "${ROUTE_NAME}"
printf "  %-18s %s\n" "Service:"      "service('${SERVICE_NAME}')"
printf "  %-18s %s\n" "Views:"        "app/Views/${VIEW_PATH}/"
if [[ -n "$CUSTOM_ACTIONS_SUMMARY" ]]; then
    printf "  %-18s %s\n" "Custom actions:" "${CUSTOM_ACTIONS_SUMMARY}"
fi
echo ""

# ─── Helper: cross-platform placeholder substitution ───────────────────────────
# Usage: substitute_placeholders <file> <key1> <val1> [<key2> <val2> ...]
substitute_placeholders() {
    local file="$1"; shift

    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would substitute placeholders in: ${file}${NC}"
        return
    fi

    php -r '
        $file = $argv[1];
        $argc = count($argv);
        $map  = [];
        for ($i = 2; $i + 1 < $argc; $i += 2) {
            $map[$argv[$i]] = $argv[$i + 1];
        }
        $content = file_get_contents($file);
        if ($content === false) { fwrite(STDERR, "Failed to read {$file}\n"); exit(1); }
        $content = str_replace(array_keys($map), array_values($map), $content);
        file_put_contents($file, $content);
    ' -- "$file" "$@"
}

# ─── Helpers: file write with dry-run / skip / force semantics ─────────────────

_write() {
    local path="$1"
    local content="$2"

    if [[ "$DRY_RUN" == true ]]; then
        if [[ -f "$path" ]]; then
            echo -e "  ${YELLOW}[dry-run] Would skip (exists): ${path}${NC}"
        else
            echo -e "  ${GREEN}[dry-run] Would create:        ${path}${NC}"
        fi
        return
    fi

    if [[ -f "$path" && "$FORCE" != true ]]; then
        echo -e "  ${YELLOW}⚠ Skipped (exists):  ${path}${NC}"
        return
    fi

    if [[ -f "$path" && "$FORCE" == true ]]; then
        printf '%s\n' "$content" > "$path"
        echo -e "  ${GREEN}✓ Overwritten:       ${path}${NC}"
    else
        printf '%s\n' "$content" > "$path"
        echo -e "  ${GREEN}✓ Created:           ${path}${NC}"
    fi
}

write_file() {
    _write "$1" "$2"
}

write_heredoc() {
    local path="$1"
    local content
    content=$(cat)
    _write "$path" "$content"
}

# ─── Pre-flight: case-insensitive collision detection ────────────────────────
# Mirror of API's ScaffoldingOrchestrator::validateFilesDoNotExist().
# On case-insensitive filesystems (macOS HFS+/APFS, Windows NTFS) `[[ -f X ]]`
# returns true for siblings whose lowercased basename matches X. Without this
# guard, planned writes like 'tests/feature/APIKeyFlowTest.php' silently
# resolve to the starter's 'ApiKeyFlowTest.php' and the script reports
# "Skipped (exists)" while the new module is left half-wired against another
# module's namespace. Always check, even with --force, because the destructive
# overwrite is on a different module's file.

PLANNED_FILES=(
    "${MODULE_DIR}/Services/${SERVICE_IFACE}.php"
    "${MODULE_DIR}/Services/${SERVICE_CLASS}.php"
    "${MODULE_DIR}/Requests/${STORE_REQUEST}.php"
    "${MODULE_DIR}/Requests/${UPDATE_REQUEST}.php"
    "${MODULE_DIR}/Controllers/${CONTROLLER_CLASS}.php"
    "${MODULE_DIR}/Config/Routes.php"
    "app/Views/${VIEW_PATH}/index.php"
    "app/Views/${VIEW_PATH}/show.php"
    "app/Views/${VIEW_PATH}/create.php"
    "app/Views/${VIEW_PATH}/edit.php"
    "app/Views/${VIEW_PATH}/partials/filters.php"
    "app/Views/${VIEW_PATH}/partials/toolbar_actions.php"
    "tests/feature/${RESOURCE}FlowTest.php"
    "tests/unit/Services/${SERVICE_CLASS}Test.php"
)
if [[ "$HAS_REORDER" == true ]]; then
    PLANNED_FILES+=("app/Views/${VIEW_PATH}/reorder.php")
fi
if [[ "$CSV_ENABLED" == true ]]; then
    PLANNED_FILES+=(
        "app/Views/components/table/export_button.php"
        "app/Views/components/form/export_import.php"
        "app/Views/components/form/import_preview.php"
    )
fi
for locale in "${LANG_LOCALE_LIST[@]}"; do
    PLANNED_FILES+=("${MODULE_DIR}/Language/${locale}/${MODULE}.php")
done

COLLISION_REPORT=$(python3 - "${PLANNED_FILES[@]}" <<'PYEOF'
import os, sys
collisions = []
for path in sys.argv[1:]:
    parent = os.path.dirname(path) or '.'
    if not os.path.isdir(parent):
        continue
    target = os.path.basename(path)
    target_lower = target.lower()
    try:
        entries = os.listdir(parent)
    except OSError:
        continue
    exact_match = False
    case_match = None
    for entry in entries:
        if entry == target:
            exact_match = True
            break
        if entry.lower() == target_lower:
            case_match = entry
    if not exact_match and case_match is not None:
        collisions.append((path, os.path.join(parent, case_match)))
if collisions:
    for planned, actual in collisions:
        print(f"  planned: {planned}")
        print(f"  actual:  {actual}")
    sys.exit(1)
PYEOF
) || COLLISION_EXIT=$?
COLLISION_EXIT=${COLLISION_EXIT:-0}

if [[ $COLLISION_EXIT -ne 0 ]]; then
    echo -e "${RED}❌ Case-insensitive filesystem collision detected.${NC}"
    echo ""
    echo "$COLLISION_REPORT"
    echo ""
    echo -e "${YELLOW}Resource '${RESOURCE}' would shadow existing files belonging to another module.${NC}"
    echo -e "${YELLOW}On macOS/Windows the planned writes silently target those files instead, leaving${NC}"
    echo -e "${YELLOW}the new module half-wired.${NC}"
    echo ""
    echo -e "${YELLOW}Use a different StudlyCase name (e.g. ${RESOURCE_CANONICAL}) or remove the conflicting${NC}"
    echo -e "${YELLOW}module first via:${NC}"
    echo -e "${YELLOW}    bash bin/remove-module.sh <Resource> <Module>${NC}"
    exit 1
fi

# ─── PSR-4 registration ────────────────────────────────────────────────────────

AUTOLOAD_FILE="app/Config/Autoload.php"
IS_NEW_MODULE=false

if grep -qF "'App\\Modules\\${MODULE}'" "$AUTOLOAD_FILE"; then
    echo -e "${GREEN}✓ NO-OP: PSR-4 already registered for module ${MODULE}${NC}"
else
    IS_NEW_MODULE=true

    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would register PSR-4 namespace 'App\\Modules\\${MODULE}'${NC}"
    else
        echo -e "${YELLOW}Registering PSR-4 namespace for new module ${MODULE}...${NC}"

        if python3 - "$AUTOLOAD_FILE" "$MODULE" <<'PYEOF'
import sys

autoload_file = sys.argv[1]
module = sys.argv[2]

with open(autoload_file, encoding='utf-8') as f:
    lines = f.readlines()

entry = f"        'App\\Modules\\{module}'  => APPPATH . 'Modules/{module}',\n"
psr4_open = False
insert_at = None

for index, line in enumerate(lines):
    if "public $psr4 = [" in line:
        psr4_open = True
        continue

    if not psr4_open:
        continue

    if line.strip() == '];':
        insert_at = index
        break

if insert_at is None:
    sys.exit(1)

if any(entry.strip() == line.strip() for line in lines):
    sys.exit(0)

lines.insert(insert_at, entry)

with open(autoload_file, 'w', encoding='utf-8') as f:
    f.writelines(lines)
PYEOF
        then
            echo -e "${GREEN}✓ UPDATED: app/Config/Autoload.php (PSR-4 entry for ${MODULE})${NC}"
        else
            echo -e "${YELLOW}⚠ PSR-4 auto-inject failed. Add manually to ${AUTOLOAD_FILE}:${NC}"
            echo "    'App\\Modules\\${MODULE}'  => APPPATH . 'Modules/${MODULE}',"
            # Continue scaffolding so partial output is still useful
        fi
    fi
fi

# ─── Sidebar registration ──────────────────────────────────────────────────────

register_sidebar_from_template() {
    local template_json="${CI4_TEMPLATE_JSON:-}"

    if [[ -z "$template_json" || ! -f "$template_json" ]]; then
        return 0
    fi

    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would register sidebar entries from $(basename "$template_json")${NC}"
        return 0
    fi

    if [[ ! -x "bin/register-sidebar.sh" ]]; then
        echo -e "${YELLOW}⚠ Sidebar registrar not found — add sidebar entries manually if the template defines admin_sidebar.${NC}"
        return 0
    fi

    echo -e "${YELLOW}Registering sidebar entries from $(basename "$template_json")...${NC}"
    if bash bin/register-sidebar.sh "$template_json"; then
        echo -e "${GREEN}✓ UPDATED: app/Views/layouts/partials/sidebar.php (sidebar entries from $(basename "$template_json"))${NC}"
    else
        echo -e "${YELLOW}⚠ Sidebar auto-registration failed. Add sidebar entries manually if the template defines admin_sidebar.${NC}"
    fi
}

# ─── Directory structure ───────────────────────────────────────────────────────

echo ""
echo -e "${YELLOW}Creating directories...${NC}"
if [[ "$DRY_RUN" == true ]]; then
    echo -e "  ${YELLOW}[dry-run] Would create module / view / test directories${NC}"
else
    mkdir -p "${MODULE_DIR}/Controllers"
    mkdir -p "${MODULE_DIR}/Services"
    mkdir -p "${MODULE_DIR}/Requests"
    mkdir -p "${MODULE_DIR}/Config"
    mkdir -p "app/Views/${VIEW_PATH}/partials"
    mkdir -p "tests/feature"
    mkdir -p "tests/unit/Services"
    for locale in "${LANG_LOCALE_LIST[@]}"; do
        mkdir -p "${MODULE_DIR}/Language/${locale}"
    done
    echo -e "${GREEN}✓ Done${NC}"
fi

# ─── File generation ───────────────────────────────────────────────────────────

echo ""
echo -e "${YELLOW}Generating files...${NC}"

# ── ServiceInterface ───────────────────────────────────────────────────────────
write_file "${MODULE_DIR}/Services/${SERVICE_IFACE}.php" \
"<?php

declare(strict_types=1);

namespace App\\Modules\\${MODULE}\\Services;

/**
 * @phpstan-import-type ApiResponse from \\App\\Libraries\\ApiClientInterface
 */
interface ${SERVICE_IFACE}
{
    /**
     * @param array<string, mixed> \$filters
     * @return ApiResponse
     */
    public function list(array \$filters = []): array;

    /** @return ApiResponse */
    public function get(int|string \$id): array;

    /**
     * @param array<string, mixed> \$payload
     * @return ApiResponse
     */
    public function create(array \$payload): array;

    /**
     * @param array<string, mixed> \$payload
     * @return ApiResponse
     */
    public function update(int|string \$id, array \$payload): array;

    /** @return ApiResponse */
    public function delete(int|string \$id): array;
${SERVICE_IFACE_ACTIONS}
${CSV_SERVICE_IFACE}
${RELATION_SERVICE_IFACE}
}"

# ── Service ────────────────────────────────────────────────────────────────────
write_file "${MODULE_DIR}/Services/${SERVICE_CLASS}.php" \
"<?php

declare(strict_types=1);

namespace App\\Modules\\${MODULE}\\Services;

use App\\Services\\ResourceApiService;

class ${SERVICE_CLASS} extends ResourceApiService implements ${SERVICE_IFACE}
{
    protected function resourcePath(): string
    {
        return '${API_PATH}';
    }
${SERVICE_CLASS_ACTIONS}
${CSV_SERVICE_IMPL}
${RELATION_SERVICE_IMPL}
}"

# ── StoreRequest ───────────────────────────────────────────────────────────────
write_file "${MODULE_DIR}/Requests/${STORE_REQUEST}.php" \
"<?php

declare(strict_types=1);

namespace App\\Modules\\${MODULE}\\Requests;

use App\\Support\\Requests\\BaseFormRequest;

class ${STORE_REQUEST} extends BaseFormRequest
{
    protected function fields(): array
    {
        return [${REQ_FIELDS}];
    }

    public function rules(): array
    {
        return [
${REQ_RULES}
        ];
    }

    public function payload(): array
    {
        return [
${REQ_PAYLOAD}
        ];
    }
}"

# ── UpdateRequest ──────────────────────────────────────────────────────────────
write_file "${MODULE_DIR}/Requests/${UPDATE_REQUEST}.php" \
"<?php

declare(strict_types=1);

namespace App\\Modules\\${MODULE}\\Requests;

class ${UPDATE_REQUEST} extends ${STORE_REQUEST}
{
${REORDER_UPDATE_PAYLOAD}
}"

# ── Controller ─────────────────────────────────────────────────────────────────
write_file "${MODULE_DIR}/Controllers/${CONTROLLER_CLASS}.php" \
"<?php

declare(strict_types=1);

namespace App\\Modules\\${MODULE}\\Controllers;

use App\\Controllers\\BaseWebController;
use App\\Modules\\${MODULE}\\Requests\\${STORE_REQUEST};
use App\\Modules\\${MODULE}\\Requests\\${UPDATE_REQUEST};
use App\\Modules\\${MODULE}\\Services\\${SERVICE_IFACE};
use CodeIgniter\\HTTP\\RedirectResponse;
use CodeIgniter\\HTTP\\RequestInterface;
use CodeIgniter\\HTTP\\ResponseInterface;
use Psr\\Log\\LoggerInterface;

class ${CONTROLLER_CLASS} extends BaseWebController
{
    protected ${SERVICE_IFACE} \$${RESOURCE_CAMEL}Service;

    public function initController(RequestInterface \$request, ResponseInterface \$response, LoggerInterface \$logger): void
    {
        parent::initController(\$request, \$response, \$logger);
        \$this->${RESOURCE_CAMEL}Service = service('${SERVICE_NAME}');
    }

    public function index(): string
    {
        return \$this->render('${VIEW_PATH}/index', [
            'title'        => lang('${MODULE}.${LANG_PREFIX}_title'),
            'limitOptions' => [10, 25, 50, 100],
${RELATION_VIEW_DATA}
        ]);
    }

    public function data(): ResponseInterface
    {
        return \$this->tableDataResponse(
            [${RELATION_FILTER_FIELDS}],
            ['name', 'created_at'],
            fn (array \$params) => \$this->${RESOURCE_CAMEL}Service->list(\$params),
        );
    }

    public function show(string \$id): string
    {
        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->get(\$id));

        if (! \$response['ok']) {
            return \$this->render('${VIEW_PATH}/show', [
                'title' => lang('${MODULE}.${LANG_PREFIX}_details'),
                '${RESOURCE_CAMEL}' => [],
                'error' => \$this->firstMessage(\$response, lang('${MODULE}.${LANG_PREFIX}_not_found')),
${RELATION_VIEW_DATA}
            ]);
        }

        return \$this->render('${VIEW_PATH}/show', [
            'title' => lang('${MODULE}.${LANG_PREFIX}_details'),
            '${RESOURCE_CAMEL}' => \$this->extractData(\$response),
${RELATION_VIEW_DATA}
        ]);
    }

    public function create(): string
    {
        return \$this->render('${VIEW_PATH}/create', [
            'title' => lang('${MODULE}.${LANG_PREFIX}_create'),
${RELATION_VIEW_DATA}
        ]);
    }

    public function store(): RedirectResponse
    {
        /** @var ${STORE_REQUEST} \$request */
        \$request = service('formRequest', ${STORE_REQUEST}::class, false);
        \$invalid = \$this->validateRequest(\$request);
        if (\$invalid !== null) {
            return \$invalid;
        }

        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->create(\$request->payload()));

        if (! \$response['ok']) {
            return \$this->failApi(\$response, lang('${MODULE}.${LANG_PREFIX}_create_failed'));
        }

        return redirect()->to(route_to('${ROUTE_NAME}'))->with('success', lang('${MODULE}.${LANG_PREFIX}_create_success'));
    }

    public function edit(string \$id): string|RedirectResponse
    {
        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->get(\$id));
        if (! \$response['ok']) {
            return \$this->withError(lang('${MODULE}.${LANG_PREFIX}_not_found'), route_to('${ROUTE_NAME}'));
        }

        return \$this->render('${VIEW_PATH}/edit', [
            'title' => lang('${MODULE}.${LANG_PREFIX}_edit'),
            'item'  => \$this->extractData(\$response),
${RELATION_VIEW_DATA}
        ]);
    }

    public function update(string \$id): RedirectResponse
    {
        /** @var ${UPDATE_REQUEST} \$request */
        \$request = service('formRequest', ${UPDATE_REQUEST}::class, false);
        \$invalid = \$this->validateRequest(\$request);
        if (\$invalid !== null) {
            return \$invalid;
        }

        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->update(\$id, \$request->payload()));

        if (! \$response['ok']) {
            return \$this->failApi(\$response, lang('${MODULE}.${LANG_PREFIX}_update_failed'));
        }

        return redirect()->to(route_to('${ROUTE_NAME}'))->with('success', lang('${MODULE}.${LANG_PREFIX}_update_success'));
    }

    public function delete(string \$id): RedirectResponse
    {
        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->delete(\$id));

        if (! \$response['ok']) {
            return \$this->failApi(\$response, lang('${MODULE}.${LANG_PREFIX}_delete_failed'), route_to('${ROUTE_NAME}'), false);
        }

        return redirect()->to(route_to('${ROUTE_NAME}'))->with('success', lang('${MODULE}.${LANG_PREFIX}_delete_success'));
    }
${CSV_CONTROLLER_ACTIONS}
${REORDER_CONTROLLER_ACTIONS}
${CONTROLLER_ACTIONS}
${RELATION_CONTROLLER_HELPERS}
}"

# ── Routes ─────────────────────────────────────────────────────────────────────
ROUTES_FILE="${MODULE_DIR}/Config/Routes.php"
NS="\\\\App\\\\Modules\\\\${MODULE}\\\\Controllers\\\\${CONTROLLER_CLASS}"

if [[ ! -f "$ROUTES_FILE" ]]; then
    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${GREEN}[dry-run] Would create:        ${ROUTES_FILE}${NC}"
    else
        printf '%s\n' "<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection \$routes
 */

\$routes->group('admin/${MODULE_LOWER}', ['filter' => ['auth', 'admin']], static function (RouteCollection \$routes): void {
    // ${RESOURCE}
    \$routes->get('${ROUTE_SEGMENT}', '${NS}::index', ['as' => '${ROUTE_NAME}']);
    \$routes->get('${ROUTE_SEGMENT}/data', '${NS}::data', ['as' => '${ROUTE_NAME}.data']);
    \$routes->get('${ROUTE_SEGMENT}/create', '${NS}::create', ['as' => '${ROUTE_NAME}.create']);
    \$routes->post('${ROUTE_SEGMENT}', '${NS}::store', ['as' => '${ROUTE_NAME}.store']);
    \$routes->get('${ROUTE_SEGMENT}/(:segment)', '${NS}::show/\$1', ['as' => '${ROUTE_NAME}.show']);
    \$routes->get('${ROUTE_SEGMENT}/(:segment)/edit', '${NS}::edit/\$1', ['as' => '${ROUTE_NAME}.edit']);
    \$routes->post('${ROUTE_SEGMENT}/(:segment)', '${NS}::update/\$1', ['as' => '${ROUTE_NAME}.update']);
    \$routes->post('${ROUTE_SEGMENT}/(:segment)/delete', '${NS}::delete/\$1', ['as' => '${ROUTE_NAME}.delete']);
${REORDER_ROUTE_BLOCK}
${CSV_ROUTE_BLOCK}
${ROUTE_APPEND_BLOCK}
});" > "$ROUTES_FILE"
        echo -e "  ${GREEN}✓ Created:           ${ROUTES_FILE}${NC}"
    fi
else
    # Append the route block inside the existing group, before the closing });
    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would append route block to: ${ROUTES_FILE}${NC}"
    else
        if python3 - "$ROUTES_FILE" "$ROUTE_SEGMENT" "$NS" "$ROUTE_NAME" "$RESOURCE" "$ROUTE_APPEND_BLOCK" "$REORDER_ROUTE_BLOCK" "$CSV_ROUTE_BLOCK" <<'PYEOF'
import sys, re

routes_file, seg, ns, name, resource, extra_routes, reorder_routes, csv_routes = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5], sys.argv[6], sys.argv[7], sys.argv[8]

with open(routes_file) as f:
    content = f.read()

# Idempotency: skip if this segment is already present
if f"'{seg}'" in content or f'"{seg}"' in content:
    print(f"SKIP: route segment '{seg}' already present in {routes_file}", file=sys.stderr)
    sys.exit(2)

block = (
    f"\n    // {resource}\n"
    f"    $routes->get('{seg}', '{ns}::index', ['as' => '{name}']);\n"
    f"    $routes->get('{seg}/data', '{ns}::data', ['as' => '{name}.data']);\n"
    f"    $routes->get('{seg}/create', '{ns}::create', ['as' => '{name}.create']);\n"
    f"    $routes->post('{seg}', '{ns}::store', ['as' => '{name}.store']);\n"
    f"    $routes->get('{seg}/(:segment)', '{ns}::show/$1', ['as' => '{name}.show']);\n"
    f"    $routes->get('{seg}/(:segment)/edit', '{ns}::edit/$1', ['as' => '{name}.edit']);\n"
    f"    $routes->post('{seg}/(:segment)', '{ns}::update/$1', ['as' => '{name}.update']);\n"
    f"    $routes->post('{seg}/(:segment)/delete', '{ns}::delete/$1', ['as' => '{name}.delete']);"
)

if extra_routes:
    block += extra_routes

if reorder_routes:
    block += reorder_routes

if csv_routes:
    block += csv_routes

new_content = re.sub(r'(\n\}\);)', '\n' + block + r'\1', content, count=1)

with open(routes_file, 'w') as f:
    f.write(new_content)
PYEOF
        then
            echo -e "  ${GREEN}✓ Route block appended: ${ROUTES_FILE}${NC}"
        else
            PY_EXIT=$?
            if [[ $PY_EXIT -eq 2 ]]; then
                echo -e "  ${YELLOW}⚠ Skipped routes (segment '${ROUTE_SEGMENT}' already present)${NC}"
            else
                echo -e "  ${RED}✗ Failed to append routes (exit ${PY_EXIT})${NC}"
            fi
        fi
    fi
fi

# ── Language stubs ─────────────────────────────────────────────────────────────

write_lang() {
    local lang_file="$1"
    local locale="${2:-en}"
    local field_lines
    field_lines=$(printf '%s' "$LANG_FIELDS_JSON" | jq -r --arg locale "$locale" '.[$locale] // ""')

    local actions_block="$LANG_EN_ACTIONS"
    local csv_block=""
    local todo_comment=""
    local existed_before=false
    if [[ "$locale" == "es" ]]; then
        actions_block="$LANG_ES_ACTIONS"
        csv_block="$CSV_LANG_ES"
        todo_comment="// TODO: Revisa todas las traducciones (singular/plural y género gramatical pueden variar)."
    else
        csv_block="$CSV_LANG_EN"
    fi

    if [[ "$DRY_RUN" == true ]]; then
        if [[ -f "$lang_file" ]]; then
            echo -e "  ${YELLOW}[dry-run] Would skip (exists): ${lang_file}${NC}"
        else
            echo -e "  ${GREEN}[dry-run] Would create:        ${lang_file}${NC}"
        fi
        return
    fi

    if [[ -f "$lang_file" && "$FORCE" != true ]]; then
        # Check if keys are already present in the existing file
        if grep -q "'${LANG_PREFIX}_title'" "$lang_file" || grep -q "\"${LANG_PREFIX}_title\"" "$lang_file"; then
            echo -e "  ${YELLOW}⚠ Skipped (exists & keys present):  ${lang_file}${NC}"
            return
        fi
    fi
    if [[ -f "$lang_file" ]]; then
        existed_before=true
    fi

    local content
    if [[ "$locale" == "es" ]]; then
        content="<?php

declare(strict_types=1);

${todo_comment}
return [
    // ${RESOURCE} — list & actions
    '${LANG_PREFIX}_title'              => '${RESOURCE_LABEL}s',
    '${LANG_PREFIX}_new'                => 'Nuevo ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_create'             => 'Nuevo ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_edit'               => 'Editar ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_details'            => 'Detalle de ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_not_found'          => '${RESOURCE_LABEL} no encontrado.',
    '${LANG_PREFIX}_create_success'     => '${RESOURCE_LABEL} creado correctamente.',
    '${LANG_PREFIX}_create_failed'      => 'No se pudo crear el ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_update_success'     => '${RESOURCE_LABEL} actualizado correctamente.',
    '${LANG_PREFIX}_update_failed'      => 'No se pudo actualizar el ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_delete_success'     => '${RESOURCE_LABEL} eliminado correctamente.',
    '${LANG_PREFIX}_delete_failed'      => 'No se pudo eliminar el ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_empty'              => 'Aún no hay ${RESOURCE_LOWER}s registrados.',
    '${LANG_PREFIX}_search_placeholder' => 'Buscar por nombre...',
    '${LANG_PREFIX}_loading'            => 'Cargando ${RESOURCE_LOWER}s...',
    '${LANG_PREFIX}_no_results'         => 'No se encontraron ${RESOURCE_LOWER}s.',
${actions_block}
${csv_block}

    // ${RESOURCE} — form fields (add more as needed)
${field_lines}
];"
    else
        content="<?php

declare(strict_types=1);

return [
    // ${RESOURCE} — list & actions
    '${LANG_PREFIX}_title'              => '${RESOURCE_LABEL}s',
    '${LANG_PREFIX}_new'                => 'New ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_create'             => 'New ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_edit'               => 'Edit ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_details'            => '${RESOURCE_LABEL} details',
    '${LANG_PREFIX}_not_found'          => '${RESOURCE_LABEL} not found.',
    '${LANG_PREFIX}_create_success'     => '${RESOURCE_LABEL} created successfully.',
    '${LANG_PREFIX}_create_failed'      => 'Could not create the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_update_success'     => '${RESOURCE_LABEL} updated successfully.',
    '${LANG_PREFIX}_update_failed'      => 'Could not update the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_delete_success'     => '${RESOURCE_LABEL} deleted successfully.',
    '${LANG_PREFIX}_delete_failed'      => 'Could not delete the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_empty'              => 'No ${RESOURCE_LOWER}s registered yet.',
    '${LANG_PREFIX}_search_placeholder' => 'Search by name...',
    '${LANG_PREFIX}_loading'            => 'Loading ${RESOURCE_LOWER}s...',
    '${LANG_PREFIX}_no_results'         => 'No ${RESOURCE_LOWER}s found.',
${actions_block}
${csv_block}

    // ${RESOURCE} — form fields (add more as needed)
${field_lines}
];"
    fi

    mkdir -p "$(dirname "$lang_file")"
    printf '%s\n' "$content" > "$lang_file"
    if [[ "$existed_before" == true && "$FORCE" == true ]]; then
        echo -e "  ${GREEN}✓ Overwritten:       ${lang_file}${NC}"
    else
        echo -e "  ${GREEN}✓ Created:           ${lang_file}${NC}"
    fi
}

while IFS= read -r locale; do
    [[ -n "$locale" ]] || continue
    write_lang "${MODULE_DIR}/Language/${locale}/${MODULE}.php" "$locale"
done < <(printf '%s' "$LANG_LOCALES_JSON" | jq -r '.[]')

# ── View stubs ─────────────────────────────────────────────────────────────────

write_heredoc "app/Views/${VIEW_PATH}/index.php" << 'VIEW_EOF_MARKER'
<?php /** @var array $limitOptions */ ?>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= route_to('VIEW_ROUTE_NAME.data') ?>',
        pageUrl: '<?= route_to('VIEW_ROUTE_NAME') ?>',
        routes: {
            showBase: '<?= route_to('VIEW_ROUTE_NAME') ?>',
            editBase: '<?= route_to('VIEW_ROUTE_NAME') ?>'
        },
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    })" x-init="init()">

    <?= view('layouts/partials/table_toolbar', [
        'title'       => lang('VIEW_MODULE.VIEW_LANG_PREFIX_title'),
        'actionsView' => 'VIEW_VIEW_PATH/partials/toolbar_actions',
    ]) ?>
    VIEW_CSV_INDEX_COMMENT

    <?= view('layouts/partials/filter_panel', [
        'actionUrl'          => route_to('VIEW_ROUTE_NAME'),
        'clearUrl'           => route_to('VIEW_ROUTE_NAME'),
        'hasFilters'         => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults'     => ['limit' => '25'],
        'fieldsView'         => 'VIEW_VIEW_PATH/partials/filters',
        'fieldsData'         => [
            'limitOptions' => $limitOptions ?? [10, 25, 50, 100],
VIEW_RELATION_FIELDS_DATA
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <?= view('components/display/empty_state', [
            'title' => 'App.no_results',
            'description' => 'App.no_results_desc',
            'actionUrl' => route_to('VIEW_ROUTE_NAME.create'),
            'actionLabel' => 'App.create',
        ]) ?>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
VIEW_INDEX_HEADERS
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.created_at')])) ?>">
                                <span><?= lang('TableColumns.created_at') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
VIEW_INDEX_ROWS
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <a :href="showUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.view') ?></a>
                                    <a :href="editUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
    </template>

    <?= view('layouts/partials/remote_pagination') ?>
</section>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/index.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_" \
    "VIEW_CSV_INDEX_COMMENT" "${CSV_INDEX_COMMENT}" \
    "VIEW_VIEW_PATH"     "${VIEW_PATH}" \
    "VIEW_INDEX_HEADERS" "${VIEW_INDEX_HEADERS}" \
    "VIEW_INDEX_ROWS"    "${VIEW_INDEX_ROWS}" \
    "VIEW_RELATION_FIELDS_DATA" "${RELATION_FIELDS_DATA}"

write_heredoc "app/Views/${VIEW_PATH}/show.php" << 'VIEW_EOF_MARKER'
<?php $VIEW_RESOURCE_CAMEL = $VIEW_RESOURCE_CAMEL ?? []; ?>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($VIEW_RESOURCE_CAMEL)): ?>
    <?php $itemId = (string) ($VIEW_RESOURCE_CAMEL['id'] ?? ''); ?>

    <?= view('components/display/admin_page_header', [
        'backUrl' => route_to('VIEW_ROUTE_NAME'),
        'backLabel' => 'VIEW_MODULE.VIEW_LANG_PREFIX_title',
        'eyebrow' => 'VIEW_MODULE.VIEW_LANG_PREFIX_details',
        'title' => (string) ($VIEW_RESOURCE_CAMEL['name'] ?? $VIEW_RESOURCE_CAMEL['title'] ?? $VIEW_RESOURCE_CAMEL['id'] ?? lang('VIEW_MODULE.VIEW_LANG_PREFIX_details')),
    ]) ?>

    <?php ob_start(); ?>
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700"><?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_details')) ?></h3>
        </div>
        <dl class="divide-y divide-gray-100">
VIEW_SHOW_ROWS
            <?= view('components/display/field_row', [
                'label' => 'TableColumns.created_at',
                'value' => $VIEW_RESOURCE_CAMEL['created_at'] ?? '—',
            ]) ?>
        </dl>
    </section>
    <?php $mainContent = ob_get_clean(); ?>

    <?php ob_start(); ?>
    <a href="<?= route_to('VIEW_ROUTE_NAME.edit', $itemId) ?>" class="<?= esc(action_button_class('primary')) ?>"><?= lang('App.edit') ?></a>
VIEW_SHOW_ACTION_BUTTONS
    <?php $actionsContent = ob_get_clean(); ?>

    <?php ob_start(); ?>
    <form method="post" action="<?= route_to('VIEW_ROUTE_NAME.delete', $itemId) ?>" x-data @submit.prevent="$store.confirm.show('<?= esc(confirm_delete_message($VIEW_RESOURCE_CAMEL['name'] ?? $VIEW_RESOURCE_CAMEL['title'] ?? $VIEW_RESOURCE_CAMEL['id'] ?? null), 'js') ?>', () => $el.submit())">
        <?= csrf_field() ?>
        <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
            <?= esc(lang('App.delete')) ?>
        </button>
    </form>
    <?php $dangerContent = ob_get_clean(); ?>

    <?= view('components/display/admin_resource_layout', [
        'main' => $mainContent,
        'aside' => view('components/display/admin_actions_panel', [
            'content' => $actionsContent,
            'dangerContent' => $dangerContent,
        ]),
    ]) ?>
<?php endif; ?>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/show.php" \
    "VIEW_ROUTE_NAME"      "${ROUTE_NAME}" \
    "VIEW_MODULE"          "${MODULE}" \
    "VIEW_LANG_PREFIX_"    "${LANG_PREFIX}_" \
    "VIEW_RESOURCE_CAMEL"  "${RESOURCE_CAMEL}" \
    "VIEW_SHOW_ACTION_BUTTONS" "${SHOW_ACTION_BUTTONS}" \
    "VIEW_SHOW_ROWS"       "${VIEW_SHOW_ROWS}"

write_heredoc "app/Views/${VIEW_PATH}/create.php" << 'VIEW_EOF_MARKER'
<?= view('components/display/admin_page_header', [
    'backUrl' => route_to('VIEW_ROUTE_NAME'),
    'backLabel' => 'App.back',
    'eyebrow' => 'VIEW_MODULE.VIEW_LANG_PREFIX_title',
    'title' => 'VIEW_MODULE.VIEW_LANG_PREFIX_create',
]) ?>

<form method="post" action="<?= route_to('VIEW_ROUTE_NAME.store') ?>" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <?= csrf_field() ?>

    <div class="lg:col-span-2">
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_create')) ?></h3>
            <div class="mt-4 space-y-4">

VIEW_CREATE_FIELDS
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <?= view('components/display/admin_actions_panel', [
            'content' => '<button type="submit" class="' . esc(action_button_class('primary'), 'attr') . '">' . esc(lang('App.create')) . '</button>'
                . '<a href="' . esc(route_to('VIEW_ROUTE_NAME'), 'attr') . '" class="' . esc(action_button_class(), 'attr') . '">' . esc(lang('App.cancel')) . '</a>',
        ]) ?>
    </aside>
</form>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/create.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_" \
    "VIEW_CREATE_FIELDS" "${VIEW_CREATE_FIELDS}"

write_heredoc "app/Views/${VIEW_PATH}/edit.php" << 'VIEW_EOF_MARKER'
<?php $item = $item ?? []; ?>
<?= view('components/display/admin_page_header', [
    'backUrl' => route_to('VIEW_ROUTE_NAME'),
    'backLabel' => 'App.back',
    'eyebrow' => 'VIEW_MODULE.VIEW_LANG_PREFIX_title',
    'title' => 'VIEW_MODULE.VIEW_LANG_PREFIX_edit',
]) ?>

<form id="delete-item-form" method="post" action="<?= route_to('VIEW_ROUTE_NAME.delete', (string) ($item['id'] ?? '')) ?>" x-data @submit.prevent="$store.confirm.show('<?= esc(confirm_delete_message($item['name'] ?? $item['title'] ?? $item['id'] ?? null), 'js') ?>', () => $el.submit())">
    <?= csrf_field() ?>
</form>

<form method="post" action="<?= route_to('VIEW_ROUTE_NAME.update', (string) ($item['id'] ?? '')) ?>" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <?= csrf_field() ?>

    <div class="lg:col-span-2">
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_edit')) ?></h3>
            <div class="mt-4 space-y-4">

VIEW_EDIT_FIELDS
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <?= view('components/display/admin_actions_panel', [
            'content' => '<button type="submit" class="' . esc(action_button_class('primary'), 'attr') . '">' . esc(lang('App.update')) . '</button>'
                . '<a href="' . esc(route_to('VIEW_ROUTE_NAME'), 'attr') . '" class="' . esc(action_button_class(), 'attr') . '">' . esc(lang('App.cancel')) . '</a>',
            'dangerContent' => '<button type="submit" form="delete-item-form" class="' . esc(action_button_class('danger'), 'attr') . '">'
                . ui_icon('trash', 'h-3.5 w-3.5') . esc(lang('App.delete')) . '</button>',
        ]) ?>
    </aside>
</form>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/edit.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_" \
    "VIEW_EDIT_FIELDS"   "${VIEW_EDIT_FIELDS}"

if [[ "$HAS_REORDER" == true ]]; then
    write_heredoc "app/Views/${VIEW_PATH}/reorder.php" << 'VIEW_EOF_MARKER'
<div class="mb-4">
    <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
</div>

<?= view('components/display/reorder', [
    'items' => $items ?? [],
    'saveUrl' => route_to('VIEW_ROUTE_NAME.save_order'),
    'displayKey' => 'VIEW_REORDER_DISPLAY_KEY',
    'backUrl' => route_to('VIEW_ROUTE_NAME'),
    'title' => $title ?? lang('App.reorder'),
]) ?>
VIEW_EOF_MARKER

    substitute_placeholders "app/Views/${VIEW_PATH}/reorder.php" \
        "VIEW_ROUTE_NAME"          "${ROUTE_NAME}" \
        "VIEW_REORDER_DISPLAY_KEY" "${REORDER_DISPLAY_KEY}"
fi

write_heredoc "app/Views/${VIEW_PATH}/partials/filters.php" << 'VIEW_EOF_MARKER'
<?php /** @var array $limitOptions */ ?>

<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>"
            placeholder="<?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_search_placeholder')) ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
VIEW_RELATION_FILTER_SELECTS
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/partials/filters.php" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_" \
    "VIEW_RELATION_FILTER_SELECTS" "${RELATION_FILTER_SELECTS}"

write_heredoc "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" << 'VIEW_EOF_MARKER'
VIEW_TOOLBAR_REORDER_BUTTON
VIEW_TOOLBAR_CSV_ACTIONS
<a href="<?= route_to('VIEW_ROUTE_NAME.create') ?>" class="<?= esc(action_button_class('primary')) ?>">
    <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
    <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_new') ?>
</a>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_" \
    "VIEW_TOOLBAR_REORDER_BUTTON" "${TOOLBAR_REORDER_BUTTON}" \
    "VIEW_TOOLBAR_CSV_ACTIONS" "${CSV_TOOLBAR_ACTIONS}"

if [[ "$CSV_ENABLED" == true ]]; then
    write_heredoc "app/Views/components/table/export_button.php" << 'VIEW_EOF_MARKER'
<?php
/**
 * CSV export button.
 * @var string $exportUrl
 * @var string|null $label
 * @var string|null $title
 */

$exportUrl = $exportUrl ?? '';
$label = $label ?? '';
$title = $title ?? '';

if (! function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $value = lang($key);

        return $value === $key ? $fallback : (string) $value;
    }
}

$buttonLabel = $label !== '' ? lang($label) : safe_lang('App.export', 'Export');
$buttonTitle = $title !== '' ? lang($title) : safe_lang('App.export', 'Export');
?>
<a href="<?= esc($exportUrl, 'attr') ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc($buttonTitle, 'attr') ?>">
    <?= ui_icon('download', 'h-3.5 w-3.5') ?>
    <span><?= esc($buttonLabel) ?></span>
</a>
VIEW_EOF_MARKER

    write_heredoc "app/Views/components/form/export_import.php" << 'VIEW_EOF_MARKER'
<?php
/**
 * @var string $importUrl
 * @var string|null $importLabel
 * @var string|null $previewView
 * @var array<int, array<string, mixed>>|null $previewRows
 */

helper('form');

$importUrl = $importUrl ?? '';
$importLabel = $importLabel ?? '';
$previewView = $previewView ?? 'components/form/import_preview';
$previewRows = is_array($previewRows ?? null) ? $previewRows : [];

if (! function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $value = lang($key);

        return $value === $key ? $fallback : (string) $value;
    }
}

$importText = $importLabel !== '' ? lang($importLabel) : safe_lang('App.import', 'Import');
?>
<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h4 class="text-sm font-semibold text-gray-900"><?= esc($importText) ?></h4>
            <p class="mt-1 text-xs text-gray-500"><?= esc(safe_lang('App.csv_help', 'Use a CSV file to export or import records.')) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="post" action="<?= esc($importUrl, 'attr') ?>" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                <?= csrf_field() ?>
                <label class="sr-only" for="csv_file"><?= esc($importText) ?></label>
                <input id="csv_file" type="file" name="csv_file" accept=".csv,text/csv" class="<?= esc(input_class('csv_file')) ?>">
                <button type="submit" class="<?= esc(action_button_class('primary')) ?>">
                    <?= ui_icon('upload', 'h-3.5 w-3.5') ?>
                    <span><?= esc($importText) ?></span>
                </button>
            </form>
        </div>
    </div>

    <?php if ($previewRows !== []): ?>
        <div class="mt-4">
            <?= view($previewView, ['rows' => $previewRows]) ?>
        </div>
    <?php endif; ?>
</div>
VIEW_EOF_MARKER

    write_heredoc "app/Views/components/form/import_preview.php" << 'VIEW_EOF_MARKER'
<?php
/**
 * @var array<int, array<string, mixed>> $rows
 */

$rows = is_array($rows ?? null) ? $rows : [];

if (! function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $value = lang($key);

        return $value === $key ? $fallback : (string) $value;
    }
}
?>
<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
    <div class="flex items-center justify-between">
        <h5 class="text-sm font-semibold text-gray-900"><?= esc(safe_lang('App.preview', 'Preview')) ?></h5>
        <span class="text-xs text-gray-500"><?= esc(count($rows)) ?> <?= esc(safe_lang('App.rows', 'rows')) ?></span>
    </div>
    <div class="mt-3 overflow-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <?php $columns = $rows !== [] ? array_keys((array) $rows[0]) : []; ?>
                    <?php foreach ($columns as $column): ?>
                        <th class="px-3 py-2 text-left font-medium text-gray-700"><?= esc((string) $column) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <td class="px-3 py-2 text-gray-700"><?= esc((string) ($row[$column] ?? '')) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
VIEW_EOF_MARKER
fi

# ── Test stubs ─────────────────────────────────────────────────────────────────

write_file "tests/feature/${RESOURCE}FlowTest.php" \
"<?php

declare(strict_types=1);

namespace Tests\\Feature;

use App\\Modules\\${MODULE}\\Services\\${SERVICE_CLASS};
use CodeIgniter\\Test\\CIUnitTestCase;
use CodeIgniter\\Test\\FeatureTestTrait;
use Config\\Services;

/**
 * @internal
 */
final class ${RESOURCE}FlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAdminRoutesRequireAuth(): void
    {
        \$result = \$this->get('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}');
        \$result->assertRedirectTo(site_url('login'));
    }

    public function testNonAdminCannotAccess(): void
    {
        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => []],
        ])->get('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}');

        \$result->assertRedirectTo(site_url('dashboard'));
    }

    public function testIndexRendersForAdmin(): void
    {
        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read']],
        ])->get('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}');

        \$result->assertStatus(200);
    }

    public function testStoreValidationFailureRedirectsBack(): void
    {
        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read']],
        ])->post('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}', [
            csrf_token() => csrf_hash(),
        ]);

        \$result->assertRedirect();
    }

    public function testDeleteSuccessRedirectsToList(): void
    {
        \$mock = \$this->createMock(${SERVICE_CLASS}::class);
        \$mock->expects(\$this->once())
            ->method('delete')
            ->with('test-uuid')
            ->willReturn([
                'ok' => true, 'status' => 200, 'data' => [],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('${SERVICE_NAME}', \$mock);

        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read']],
        ])->post('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}/test-uuid/delete', [
            csrf_token() => csrf_hash(),
        ]);

        \$result->assertRedirectTo(site_url('admin/${MODULE_LOWER}/${ROUTE_SEGMENT}'));
    }
}"

write_file "tests/unit/Services/${SERVICE_CLASS}Test.php" \
"<?php

declare(strict_types=1);

namespace Tests\\Unit\\Services;

use App\\Libraries\\ApiClientInterface;
use App\\Modules\\${MODULE}\\Services\\${SERVICE_CLASS};
use CodeIgniter\\Test\\CIUnitTestCase;

/**
 * @internal
 */
final class ${SERVICE_CLASS}Test extends CIUnitTestCase
{
    public function testListCallsCorrectEndpoint(): void
    {
        \$mock     = \$this->createMock(ApiClientInterface::class);
        \$expected = ['ok' => true, 'status' => 200, 'data' => []];

        \$mock->expects(\$this->once())
            ->method('get')
            ->with('${API_PATH}', [])
            ->willReturn(\$expected);

        \$service = new ${SERVICE_CLASS}(\$mock);
        \$this->assertSame(\$expected, \$service->list());
    }

    public function testGetCallsCorrectEndpoint(): void
    {
        \$mock     = \$this->createMock(ApiClientInterface::class);
        \$expected = ['ok' => true, 'status' => 200, 'data' => ['id' => 'uuid-1']];

        \$mock->expects(\$this->once())
            ->method('get')
            ->with('${API_PATH}/uuid-1')
            ->willReturn(\$expected);

        \$service = new ${SERVICE_CLASS}(\$mock);
        \$this->assertSame(\$expected, \$service->get('uuid-1'));
    }

    public function testCreateCallsCorrectEndpoint(): void
    {
        \$mock     = \$this->createMock(ApiClientInterface::class);
        \$payload  = ['name' => 'Test'];
        \$expected = ['ok' => true, 'status' => 201, 'data' => ['id' => 'uuid-2']];

        \$mock->expects(\$this->once())
            ->method('post')
            ->with('${API_PATH}', \$payload)
            ->willReturn(\$expected);

        \$service = new ${SERVICE_CLASS}(\$mock);
        \$this->assertSame(\$expected, \$service->create(\$payload));
    }

    public function testDeleteCallsCorrectEndpoint(): void
    {
        \$mock     = \$this->createMock(ApiClientInterface::class);
        \$expected = ['ok' => true, 'status' => 200, 'data' => []];

        \$mock->expects(\$this->once())
            ->method('delete')
            ->with('${API_PATH}/uuid-3')
            ->willReturn(\$expected);

        \$service = new ${SERVICE_CLASS}(\$mock);
        \$this->assertSame(\$expected, \$service->delete('uuid-3'));
    }
}"

# ─── Auto-register service in Services.php ────────────────────────────────────

echo ""
echo -e "${YELLOW}Registering service in Services.php...${NC}"

if [[ "$DRY_RUN" == true ]]; then
    echo -e "  ${YELLOW}[dry-run] Would call: php bin/register-service.php ${MODULE} ${SERVICE_CLASS} ${SERVICE_IFACE} ${SERVICE_NAME} --client=${SERVICE_TARGET}${NC}"
    echo -e "  ${YELLOW}[dry-run] Service factory would emit: return new ${SERVICE_CLASS}(static::${CLIENT_FACTORY}());${NC}"
else
    REGISTER_RESULT=$(php bin/register-service.php "$MODULE" "$SERVICE_CLASS" "$SERVICE_IFACE" "$SERVICE_NAME" "--client=${SERVICE_TARGET}" 2>&1) || REGISTER_EXIT=$?
    REGISTER_EXIT=${REGISTER_EXIT:-0}

    if [[ $REGISTER_EXIT -eq 0 ]]; then
        echo -e "${GREEN}✓ ${REGISTER_RESULT}${NC}"
    else
        echo -e "${RED}✗ Auto-registration failed (exit ${REGISTER_EXIT}):${NC}"
        echo -e "${YELLOW}  ${REGISTER_RESULT}${NC}"
        echo ""
        echo -e "${BLUE}  ACTION REQUIRED — Add this block manually to app/Config/Services.php:${NC}"
        echo -e "${CYAN}"
        echo "    use App\\Modules\\${MODULE}\\Services\\${SERVICE_CLASS};"
        echo "    use App\\Modules\\${MODULE}\\Services\\${SERVICE_IFACE};"
        echo ""
        echo "    public static function ${SERVICE_NAME}(bool \$getShared = true): ${SERVICE_IFACE}"
        echo "    {"
        echo "        if (\$getShared) {"
        echo "            /** @var ${SERVICE_CLASS} */"
        echo "            return static::getSharedInstance('${SERVICE_NAME}');"
        echo "        }"
        echo "        return new ${SERVICE_CLASS}(static::${CLIENT_FACTORY}());"
        echo "    }"
        echo -e "${NC}"
    fi
fi

# ─── Code style on generated files ────────────────────────────────────────────

if [[ "$DRY_RUN" != true ]]; then
    if [[ -x vendor/bin/php-cs-fixer ]]; then
        echo ""
        echo -e "${YELLOW}Applying code style to generated PHP files...${NC}"

        GENERATED_PHP_FILES=()
        for f in \
            "${MODULE_DIR}/Services/${SERVICE_IFACE}.php" \
            "${MODULE_DIR}/Services/${SERVICE_CLASS}.php" \
            "${MODULE_DIR}/Requests/${STORE_REQUEST}.php" \
            "${MODULE_DIR}/Requests/${UPDATE_REQUEST}.php" \
            "${MODULE_DIR}/Controllers/${CONTROLLER_CLASS}.php" \
            "${MODULE_DIR}/Config/Routes.php" \
            "tests/feature/${RESOURCE}FlowTest.php" \
            "tests/unit/Services/${SERVICE_CLASS}Test.php"
        do
            [[ -f "$f" ]] && GENERATED_PHP_FILES+=("$f")
        done
        if [[ "$CSV_ENABLED" == true ]]; then
            for f in \
                "app/Views/components/table/export_button.php" \
                "app/Views/components/form/export_import.php" \
                "app/Views/components/form/import_preview.php"
            do
                [[ -f "$f" ]] && GENERATED_PHP_FILES+=("$f")
            done
        fi
        for locale in "${LANG_LOCALE_LIST[@]}"; do
            lang_file="${MODULE_DIR}/Language/${locale}/${MODULE}.php"
            [[ -f "$lang_file" ]] && GENERATED_PHP_FILES+=("$lang_file")
        done
        if [[ "$HAS_REORDER" == true ]]; then
            [[ -f "app/Views/${VIEW_PATH}/reorder.php" ]] && GENERATED_PHP_FILES+=("app/Views/${VIEW_PATH}/reorder.php")
        fi

        if [[ ${#GENERATED_PHP_FILES[@]} -gt 0 ]]; then
            if vendor/bin/php-cs-fixer fix "${GENERATED_PHP_FILES[@]}" --quiet >/dev/null 2>&1; then
                echo -e "${GREEN}✓ Code style applied${NC}"
            else
                echo -e "${YELLOW}⚠ php-cs-fixer reported issues — run 'composer format' to inspect${NC}"
            fi
        fi
    fi
fi

# ─── Post-generation validation ───────────────────────────────────────────────

if [[ "$DRY_RUN" != true ]]; then
    echo ""
    echo -e "${YELLOW}Validating generated files...${NC}"
    VALIDATION_FAILED=false

    EXPECTED_FILES=(
        "${MODULE_DIR}/Services/${SERVICE_IFACE}.php"
        "${MODULE_DIR}/Services/${SERVICE_CLASS}.php"
        "${MODULE_DIR}/Requests/${STORE_REQUEST}.php"
        "${MODULE_DIR}/Requests/${UPDATE_REQUEST}.php"
        "${MODULE_DIR}/Controllers/${CONTROLLER_CLASS}.php"
        "${MODULE_DIR}/Config/Routes.php"
        "app/Views/${VIEW_PATH}/index.php"
        "app/Views/${VIEW_PATH}/show.php"
        "app/Views/${VIEW_PATH}/create.php"
        "app/Views/${VIEW_PATH}/edit.php"
        "app/Views/${VIEW_PATH}/partials/filters.php"
        "app/Views/${VIEW_PATH}/partials/toolbar_actions.php"
        "tests/feature/${RESOURCE}FlowTest.php"
        "tests/unit/Services/${SERVICE_CLASS}Test.php"
    )
    if [[ "$CSV_ENABLED" == true ]]; then
        EXPECTED_FILES+=(
            "app/Views/components/table/export_button.php"
            "app/Views/components/form/export_import.php"
            "app/Views/components/form/import_preview.php"
        )
    fi
    for locale in "${LANG_LOCALE_LIST[@]}"; do
        EXPECTED_FILES+=("${MODULE_DIR}/Language/${locale}/${MODULE}.php")
    done
    if [[ "$HAS_REORDER" == true ]]; then
        EXPECTED_FILES+=("app/Views/${VIEW_PATH}/reorder.php")
    fi

    for f in "${EXPECTED_FILES[@]}"; do
        if [[ ! -f "$f" ]]; then
            echo -e "  ${RED}✗ Missing:  ${f}${NC}"
            VALIDATION_FAILED=true
        fi
    done

    for f in "${EXPECTED_FILES[@]}"; do
        if [[ "$f" == *.php && -f "$f" ]]; then
            if ! php -l "$f" >/dev/null 2>&1; then
                echo -e "  ${RED}✗ Syntax error: ${f}${NC}"
                php -l "$f"
                VALIDATION_FAILED=true
            fi
        fi
    done

    if ! grep -qF "'App\\Modules\\${MODULE}'" "$AUTOLOAD_FILE"; then
        echo -e "  ${YELLOW}⚠ PSR-4 namespace not found in ${AUTOLOAD_FILE} — add manually${NC}"
    fi

    if ! grep -qF "function ${SERVICE_NAME}(" "app/Config/Services.php"; then
        echo -e "  ${YELLOW}⚠ Service '${SERVICE_NAME}' not found in Services.php — add manually${NC}"
    fi

    # Semantic safety net: catch a regression in to_snake() that would produce
    # the `a_p_i_keys` pattern. Two or more single-letter underscored segments
    # in a row mean the acronym fix has broken upstream.
    if [[ "$RESOURCE_SNAKE" =~ (^|_)[a-z](_[a-z])+(_|$) ]]; then
        echo -e "${RED}✗ Resource snake form '${RESOURCE_SNAKE}' looks like split-acronym garbage."
        echo -e "  Regression in to_snake()? Expected acronyms to remain intact."
        echo -e "  Re-run with a canonical StudlyCase resource name (e.g. ApiKey instead of APIKey).${NC}"
        VALIDATION_FAILED=true
    fi

    if [[ "$VALIDATION_FAILED" == true ]]; then
        echo -e "${RED}✗ Validation failed — some files are missing, have syntax errors, or contain split-acronym artifacts.${NC}"
        echo -e "${YELLOW}  Recover with: bash bin/remove-module.sh ${RESOURCE} ${MODULE}${NC}"
    else
        echo -e "${GREEN}✓ All generated files present, syntax-clean, and semantically sane${NC}"
    fi
fi

register_sidebar_from_template

# ─── Summary ───────────────────────────────────────────────────────────────────

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
if [[ "$DRY_RUN" == true ]]; then
    echo -e "${YELLOW}✅ Dry-run complete — no files were written${NC}"
else
    echo -e "${GREEN}✅ Scaffolding complete!${NC}"
fi
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
if [[ "$IS_NEW_MODULE" == true ]]; then
echo "  1. Sidebar entry is auto-registered when CI4_TEMPLATE_JSON defines admin_sidebar."
echo "     If no template contract is available, add it manually in app/Views/layouts/partials/sidebar.php"
fi
echo "  2. Customize form fields in:"
echo "       ${MODULE_DIR}/Requests/${STORE_REQUEST}.php  (validation rules)"
echo "       app/Views/${VIEW_PATH}/create.php & edit.php (add/remove <div> field blocks)"
echo "       app/Views/${VIEW_PATH}/show.php              (add <dt>/<dd> rows for new fields)"
echo "       app/Views/${VIEW_PATH}/partials/filters.php  (add filter selects if needed)"
echo "       app/Views/${VIEW_PATH}/index.php             (add table columns for new fields)"
echo "  3. Add language keys for new fields in:"
for locale in "${LANG_LOCALE_LIST[@]}"; do
    echo "       ${MODULE_DIR}/Language/${locale}/${MODULE}.php"
done
echo "  4. Restart the dev server (routes are not hot-reloaded):"
echo "       pkill -f 'spark serve'; php spark serve --port 8082 &"
echo "  5. Run tests for the new module:"
echo "       vendor/bin/phpunit tests/unit/Services/${SERVICE_CLASS}Test.php"
echo "       vendor/bin/phpunit tests/feature/${RESOURCE}FlowTest.php"
echo ""
echo "Important:"
echo "  This scaffold is a CRUD shell, not a finished aggregate UI."
echo "  If the module needs custom actions, nested resources, option loaders,"
echo "  relation arrays, or richer domain forms, extend the generated code manually."
