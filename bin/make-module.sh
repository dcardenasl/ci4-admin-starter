#!/bin/bash
#
# ci4-admin-starter Module Scaffolding
# Creates a new resource within an admin module and registers PSR-4 autoloading.
#
# Usage:
#   bash bin/make-module.sh <Resource> <Module> <ApiPath> [RouteSegment] [--dry-run] [--force]
#
# Examples:
#   bash bin/make-module.sh Product Catalog /catalog/products
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

Arguments:
  <Resource>      StudlyCase resource name  (e.g. Product, SchoolCategory)
  <Module>        StudlyCase module name    (e.g. Catalog, Education)
  <ApiPath>       API path for the resource (e.g. /catalog/products)
  [RouteSegment]  URL segment override      (default: resource_plural with dashes)

Flags:
  --dry-run         Print what would be generated without writing any file
  --force           Overwrite existing files (skipped by default)
  --service=hub|domain
                    Which backend the new service should target. Default is 'hub'
                    (apiClient, port 8080). Pass 'domain' to wire the service
                    against domainApiClient (port 8090 — a ci4-domain-starter app).
  --check-api[=URL] Probe the API endpoint with a 2s HEAD request before scaffolding
                    and warn if it doesn't respond. Default URL is read from
                    apiClient.baseUrl in .env (or domainApiClient.baseUrl when
                    --service=domain).

Examples:
  bash bin/make-module.sh Product Catalog /catalog/products
  bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories
  bash bin/make-module.sh Product Catalog /catalog/products --dry-run
  bash bin/make-module.sh Product Catalog /catalog/products --check-api
  bash bin/make-module.sh Product Catalog /catalog/products --check-api=http://localhost:8080
USAGE
}

# ─── Argument parsing (positionals + flags interleaved) ────────────────────────

POSITIONAL=()
DRY_RUN=false
FORCE=false
CHECK_API_URL=""
CHECK_API_REQUESTED=false
SERVICE_TARGET="hub"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=true; shift ;;
        --force)   FORCE=true;   shift ;;
        --service=*) SERVICE_TARGET="${1#--service=}"; shift ;;
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
ROUTE_SEGMENT=${4:-}

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

# Change to project root (ci4-admin-starter/)
cd "$(dirname "$0")/.."

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
ROUTE_NAME="admin.${MODULE_LOWER}.${ROUTE_SEGMENT_UNDERSCORE}"  # admin.shows.audiences
LANG_PREFIX="${RESOURCE_PLURAL}"                              # school_categories  (used as lang key prefix)
VIEW_PATH="${MODULE_LOWER}/${ROUTE_SEGMENT_UNDERSCORE}"       # shows/audiences
MODULE_DIR="app/Modules/${MODULE}"

# Human-readable labels for language stubs
RESOURCE_LABEL=$(echo "${RESOURCE_SNAKE//_/ }" | awk '{$1=toupper(substr($1,1,1))substr($1,2)}1')
RESOURCE_LOWER=$(echo "$RESOURCE_LABEL" | tr '[:upper:]' '[:lower:]')

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
    "${MODULE_DIR}/Language/en/${MODULE}.php"
    "${MODULE_DIR}/Language/es/${MODULE}.php"
    "app/Views/${VIEW_PATH}/index.php"
    "app/Views/${VIEW_PATH}/show.php"
    "app/Views/${VIEW_PATH}/create.php"
    "app/Views/${VIEW_PATH}/edit.php"
    "app/Views/${VIEW_PATH}/partials/filters.php"
    "app/Views/${VIEW_PATH}/partials/toolbar_actions.php"
    "tests/feature/${RESOURCE}FlowTest.php"
    "tests/unit/Services/${SERVICE_CLASS}Test.php"
)

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
import sys, re

autoload_file = sys.argv[1]
module = sys.argv[2]

with open(autoload_file) as f:
    content = f.read()

new_entry = f"        'App\\\\Modules\\\\{module}'  => APPPATH . 'Modules/{module}',"

# Insert immediately before the line that closes the $psr4 array.
# That line is "    ];" and is followed by a blank line + "    /**" (Class Map comment).
pattern = r'(    \];\n\n    /\*\*\n     \* -{10,}\n     \* Class Map)'
new_content = re.sub(pattern, new_entry + '\n' + r'\1', content, count=1)

if new_content == content:
    sys.exit(1)

with open(autoload_file, 'w') as f:
    f.write(new_content)
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
    mkdir -p "${MODULE_DIR}/Language/en"
    mkdir -p "${MODULE_DIR}/Language/es"
    mkdir -p "app/Views/${VIEW_PATH}/partials"
    mkdir -p "tests/feature"
    mkdir -p "tests/unit/Services"
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
        return ['name'];
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min_length[2]|max_length[255]',
        ];
    }

    public function payload(): array
    {
        return [
            'name' => \$this->postString('name'),
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
        ]);
    }

    public function data(): ResponseInterface
    {
        return \$this->tableDataResponse(
            [],
            ['name', 'created_at'],
            fn (array \$params) => \$this->${RESOURCE_CAMEL}Service->list(\$params),
        );
    }

    public function show(string \$id): string
    {
        \$response = \$this->safeApiCall(fn () => \$this->${RESOURCE_CAMEL}Service->get(\$id));

        return \$this->renderResourceShow(
            '${VIEW_PATH}/show',
            lang('${MODULE}.${LANG_PREFIX}_details'),
            '${RESOURCE_CAMEL}',
            \$response,
            lang('${MODULE}.${LANG_PREFIX}_not_found'),
        );
    }

    public function create(): string
    {
        return \$this->render('${VIEW_PATH}/create', [
            'title' => lang('${MODULE}.${LANG_PREFIX}_create'),
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
});" > "$ROUTES_FILE"
        echo -e "  ${GREEN}✓ Created:           ${ROUTES_FILE}${NC}"
    fi
else
    # Append the route block inside the existing group, before the closing });
    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would append route block to: ${ROUTES_FILE}${NC}"
    else
        if python3 - "$ROUTES_FILE" "$ROUTE_SEGMENT" "$NS" "$ROUTE_NAME" "$RESOURCE" <<'PYEOF'
import sys, re

routes_file, seg, ns, name, resource = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5]

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

# Skip if exists semantics for language files (matches write_file behavior)
write_lang() {
    local lang_file="$1"
    local locale="${2:-en}"

    if [[ "$DRY_RUN" == true ]]; then
        if [[ -f "$lang_file" ]]; then
            echo -e "  ${YELLOW}[dry-run] Would skip (exists): ${lang_file}${NC}"
        else
            echo -e "  ${GREEN}[dry-run] Would create:        ${lang_file}${NC}"
        fi
        return
    fi

    if [[ -f "$lang_file" && "$FORCE" != true ]]; then
        echo -e "  ${YELLOW}⚠ Skipped (exists):  ${lang_file}${NC}"
        echo -e "     → Add '${LANG_PREFIX}_*' keys to it manually"
        return
    fi

    local content
    if [[ "$locale" == "es" ]]; then
        content="<?php

declare(strict_types=1);

// TODO: Revisa todas las traducciones (singular/plural y género gramatical pueden variar).
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

    // ${RESOURCE} — form fields (add more as needed)
    'field_name'                        => 'Nombre',
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

    // ${RESOURCE} — form fields (add more as needed)
    'field_name'                        => 'Name',
];"
    fi

    if [[ -f "$lang_file" && "$FORCE" == true ]]; then
        printf '%s\n' "$content" > "$lang_file"
        echo -e "  ${GREEN}✓ Overwritten:       ${lang_file}${NC}"
    else
        printf '%s\n' "$content" > "$lang_file"
        echo -e "  ${GREEN}✓ Created:           ${lang_file}${NC}"
    fi
}

write_lang "${MODULE_DIR}/Language/en/${MODULE}.php" "en"
write_lang "${MODULE_DIR}/Language/es/${MODULE}.php" "es"

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

    <?= view('layouts/partials/filter_panel', [
        'actionUrl'          => route_to('VIEW_ROUTE_NAME'),
        'clearUrl'           => route_to('VIEW_ROUTE_NAME'),
        'hasFilters'         => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults'     => ['limit' => '25'],
        'fieldsView'         => 'VIEW_VIEW_PATH/partials/filters',
        'fieldsData'         => [
            'limitOptions' => $limitOptions ?? [10, 25, 50, 100],
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_no_results') ?></p>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('name')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('name')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('VIEW_MODULE.field_name')])) ?>">
                                <span><?= lang('VIEW_MODULE.field_name') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('name')"></span>
                            </button>
                        </th>
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
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.name ?? '-')"></td>
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
    "VIEW_VIEW_PATH"     "${VIEW_PATH}"

write_heredoc "app/Views/${VIEW_PATH}/show.php" << 'VIEW_EOF_MARKER'
<?php $VIEW_RESOURCE_CAMEL = $VIEW_RESOURCE_CAMEL ?? []; ?>
<div class="mb-4">
    <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_title') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($VIEW_RESOURCE_CAMEL)): ?>
    <?php $itemId = (string) ($VIEW_RESOURCE_CAMEL['id'] ?? ''); ?>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_details') ?></h3>
            <div class="flex items-center gap-2">
                <a href="<?= route_to('VIEW_ROUTE_NAME.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                <form method="post" action="<?= route_to('VIEW_ROUTE_NAME.delete', $itemId) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
                    <?= csrf_field() ?>
                    <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                        <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                        <?= esc(lang('App.delete')) ?>
                    </button>
                </form>
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500"><?= lang('VIEW_MODULE.field_name') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($VIEW_RESOURCE_CAMEL['name'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($VIEW_RESOURCE_CAMEL['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>
    </section>
<?php endif; ?>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/show.php" \
    "VIEW_ROUTE_NAME"      "${ROUTE_NAME}" \
    "VIEW_MODULE"          "${MODULE}" \
    "VIEW_LANG_PREFIX_"    "${LANG_PREFIX}_" \
    "VIEW_RESOURCE_CAMEL"  "${RESOURCE_CAMEL}"

write_heredoc "app/Views/${VIEW_PATH}/create.php" << 'VIEW_EOF_MARKER'
<div class="mb-4">
    <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_create')) ?></h3>

    <form method="post" action="<?= route_to('VIEW_ROUTE_NAME.store') ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="name"><?= esc(lang('VIEW_MODULE.field_name')) ?> <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255" value="<?= esc(old('name', '')) ?>"
                class="<?= esc(input_class('name')) ?>">
            <?= render_field_error('name') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.create')) ?></button>
            <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/create.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_"

write_heredoc "app/Views/${VIEW_PATH}/edit.php" << 'VIEW_EOF_MARKER'
<?php $item = $item ?? []; ?>
<div class="mb-4 flex items-center justify-between">
    <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
    <form method="post" action="<?= route_to('VIEW_ROUTE_NAME.delete', (string) ($item['id'] ?? '')) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
        <?= csrf_field() ?>
        <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
            <?= esc(lang('App.delete')) ?>
        </button>
    </form>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_edit')) ?></h3>

    <form method="post" action="<?= route_to('VIEW_ROUTE_NAME.update', (string) ($item['id'] ?? '')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="name"><?= esc(lang('VIEW_MODULE.field_name')) ?> <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255"
                value="<?= esc(old('name', (string) ($item['name'] ?? ''))) ?>"
                class="<?= esc(input_class('name')) ?>">
            <?= render_field_error('name') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.update')) ?></button>
            <a href="<?= route_to('VIEW_ROUTE_NAME') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/edit.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_"

write_heredoc "app/Views/${VIEW_PATH}/partials/filters.php" << 'VIEW_EOF_MARKER'
<?php /** @var array $limitOptions */ ?>

<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>"
            placeholder="<?= esc(lang('VIEW_MODULE.VIEW_LANG_PREFIX_search_placeholder')) ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/partials/filters.php" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_"

write_heredoc "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" << 'VIEW_EOF_MARKER'
<a href="<?= route_to('VIEW_ROUTE_NAME.create') ?>" class="<?= esc(action_button_class('primary')) ?>">
    <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
    <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_new') ?>
</a>
VIEW_EOF_MARKER

substitute_placeholders "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" \
    "VIEW_ROUTE_NAME"    "${ROUTE_NAME}" \
    "VIEW_MODULE"        "${MODULE}" \
    "VIEW_LANG_PREFIX_"  "${LANG_PREFIX}_"

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
            'user'         => ['role' => 'user'],
        ])->get('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}');

        \$result->assertRedirectTo(site_url('dashboard'));
    }

    public function testIndexRendersForAdmin(): void
    {
        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/${MODULE_LOWER}/${ROUTE_SEGMENT}');

        \$result->assertStatus(200);
    }

    public function testStoreValidationFailureRedirectsBack(): void
    {
        \$result = \$this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
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
            'user'         => ['role' => 'admin'],
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
        echo -e "${YELLOW}⚠ Auto-registration failed (exit ${REGISTER_EXIT}): ${REGISTER_RESULT}${NC}"
        echo ""
        echo "  Add manually to app/Config/Services.php:"
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
            "${MODULE_DIR}/Language/en/${MODULE}.php" \
            "${MODULE_DIR}/Language/es/${MODULE}.php" \
            "tests/feature/${RESOURCE}FlowTest.php" \
            "tests/unit/Services/${SERVICE_CLASS}Test.php"
        do
            [[ -f "$f" ]] && GENERATED_PHP_FILES+=("$f")
        done

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
        "${MODULE_DIR}/Language/en/${MODULE}.php"
        "${MODULE_DIR}/Language/es/${MODULE}.php"
        "app/Views/${VIEW_PATH}/index.php"
        "app/Views/${VIEW_PATH}/show.php"
        "app/Views/${VIEW_PATH}/create.php"
        "app/Views/${VIEW_PATH}/edit.php"
        "app/Views/${VIEW_PATH}/partials/filters.php"
        "app/Views/${VIEW_PATH}/partials/toolbar_actions.php"
        "tests/feature/${RESOURCE}FlowTest.php"
        "tests/unit/Services/${SERVICE_CLASS}Test.php"
    )

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
echo "  1. Add sidebar entry:  app/Views/layouts/partials/sidebar.php"
fi
echo "  2. Customize form fields in:"
echo "       ${MODULE_DIR}/Requests/${STORE_REQUEST}.php  (validation rules)"
echo "       app/Views/${VIEW_PATH}/create.php & edit.php (add/remove <div> field blocks)"
echo "       app/Views/${VIEW_PATH}/show.php              (add <dt>/<dd> rows for new fields)"
echo "       app/Views/${VIEW_PATH}/partials/filters.php  (add filter selects if needed)"
echo "       app/Views/${VIEW_PATH}/index.php             (add table columns for new fields)"
echo "  3. Add language keys for new fields in:"
echo "       ${MODULE_DIR}/Language/en/${MODULE}.php"
echo "       ${MODULE_DIR}/Language/es/${MODULE}.php  (review TODO header)"
echo "  4. Restart the dev server (routes are not hot-reloaded):"
echo "       pkill -f 'spark serve'; php spark serve --port 8082 &"
echo "  5. Run tests for the new module:"
echo "       vendor/bin/phpunit tests/unit/Services/${SERVICE_CLASS}Test.php"
echo "       vendor/bin/phpunit tests/feature/${RESOURCE}FlowTest.php"
