#!/bin/bash
#
# ci4-admin-starter Module Scaffolding · Remover
# Inverse of bin/make-module.sh: deletes the files generated for a resource and
# un-wires its routes/services so the developer can retry from a clean tree.
#
# Usage:
#   bash bin/remove-module.sh <Resource> <Module> [RouteSegment] [--dry-run]
#
# Examples:
#   bash bin/remove-module.sh Product Catalog
#   bash bin/remove-module.sh SchoolCategory Education school-categories
#   bash bin/remove-module.sh Product Catalog --dry-run

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
  bash bin/remove-module.sh <Resource> <Module> [RouteSegment] [--dry-run]

Arguments:
  <Resource>      StudlyCase resource name  (e.g. Product, SchoolCategory)
  <Module>        StudlyCase module name    (e.g. Catalog, Education)
  [RouteSegment]  URL segment override      (default: resource_plural with dashes)

Flags:
  --dry-run       Print what would be removed without touching the filesystem
USAGE
}

POSITIONAL=()
DRY_RUN=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=true; shift ;;
        --help|-h) usage; exit 0 ;;
        --*)
            echo -e "${RED}❌ Unknown flag: $1${NC}"
            usage
            exit 1
            ;;
        *) POSITIONAL+=("$1"); shift ;;
    esac
done
set -- "${POSITIONAL[@]}"

RESOURCE=${1:-}
MODULE=${2:-}
ROUTE_SEGMENT=${3:-}

if [[ -z "$RESOURCE" || -z "$MODULE" ]]; then
    echo -e "${RED}❌ Missing arguments${NC}"
    usage
    exit 1
fi

cd "$(dirname "$0")/.."

# Same name derivations as make-module.sh — must stay in sync.
to_snake() {
    python3 -c '
import sys, re
v = sys.argv[1]
v = re.sub(r"([a-z0-9])([A-Z])", r"\1_\2", v)
v = re.sub(r"([A-Z]+)([A-Z][a-z])", r"\1_\2", v)
print(v.lower())
' "$1"
}

pluralize() {
    local w="$1"
    if [[ "$w" =~ [^aeiou]y$ ]]; then echo "${w%y}ies"
    elif [[ "$w" =~ (s|x|z|ch|sh)$ ]]; then echo "${w}es"
    else echo "${w}s"
    fi
}

RESOURCE_SNAKE=$(to_snake "$RESOURCE")
RESOURCE_PLURAL=$(pluralize "$RESOURCE_SNAKE")
MODULE_LOWER=$(echo "$MODULE" | tr '[:upper:]' '[:lower:]')
[[ -z "$ROUTE_SEGMENT" ]] && ROUTE_SEGMENT="${RESOURCE_PLURAL//_/-}"
ROUTE_SEGMENT_UNDERSCORE="${ROUTE_SEGMENT//-/_}"
SERVICE_NAME="$(python3 -c '
import sys, re
v = sys.argv[1]
v = re.sub(r"([A-Z]+)([A-Z][a-z])", lambda m: m.group(1).capitalize() + m.group(2), v)
v = re.sub(r"([A-Z]+)$", lambda m: m.group(1).capitalize(), v)
print(v[:1].lower() + v[1:])
' "$RESOURCE")ApiService"

MODULE_DIR="app/Modules/${MODULE}"
VIEW_DIR="app/Views/${MODULE_LOWER}/${ROUTE_SEGMENT_UNDERSCORE}"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Admin Module Removal — ${RESOURCE} from ${MODULE}${NC}"
[[ "$DRY_RUN" == true ]] && echo -e "${YELLOW}DRY-RUN MODE — no files will be deleted${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""

# ─── Files to delete ───────────────────────────────────────────────────────────
FILES=(
    "${MODULE_DIR}/Controllers/${RESOURCE}Controller.php"
    "${MODULE_DIR}/Services/${RESOURCE}ApiService.php"
    "${MODULE_DIR}/Services/${RESOURCE}ApiServiceInterface.php"
    "${MODULE_DIR}/Requests/${RESOURCE}StoreRequest.php"
    "${MODULE_DIR}/Requests/${RESOURCE}UpdateRequest.php"
    "tests/feature/${RESOURCE}FlowTest.php"
    "tests/unit/Services/${RESOURCE}ApiServiceTest.php"
)

VIEW_FILES=(
    "${VIEW_DIR}/index.php"
    "${VIEW_DIR}/show.php"
    "${VIEW_DIR}/create.php"
    "${VIEW_DIR}/edit.php"
    "${VIEW_DIR}/partials/filters.php"
    "${VIEW_DIR}/partials/toolbar_actions.php"
)

ALL_FILES=("${FILES[@]}" "${VIEW_FILES[@]}")

action() {
    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${GREEN}[dry-run] Would delete: $1${NC}"
    else
        rm -f "$1"
        echo -e "  ${GREEN}✓ Deleted: $1${NC}"
    fi
}

# Refuse to delete a file whose PHP namespace lives outside App\Modules\{MODULE}\.
# Catches case-insensitive collisions where 'tests/feature/APIKeyFlowTest.php'
# resolves to the starter's 'ApiKeyFlowTest.php' (different module).
#
# - For module files: the namespace itself must be App\Modules\{MODULE}\...
# - For test files (Tests\Feature, Tests\Unit\Services\): the file must
#   reference the target module via 'use App\Modules\{MODULE}\...'.
namespace_guard() {
    local file="$1"
    python3 - "$file" "$MODULE" <<'PYEOF'
import re, sys
file_path, module = sys.argv[1], sys.argv[2]
try:
    with open(file_path, 'r') as f:
        content = f.read()
except OSError as exc:
    print(f"unreadable: {exc}", file=sys.stderr)
    sys.exit(1)

ns_match = re.search(r'^\s*namespace\s+([A-Za-z0-9_\\]+)\s*;', content, re.MULTILINE)
if ns_match is None:
    print("no namespace declaration", file=sys.stderr)
    sys.exit(1)

namespace = ns_match.group(1)
expected_module_ns = f"App\\Modules\\{module}"

if namespace.startswith(expected_module_ns + "\\") or namespace == expected_module_ns:
    sys.exit(0)

# Test files: namespace is Tests\... — require a use App\Modules\{MODULE}\...
if namespace.startswith("Tests\\"):
    if re.search(rf'^\s*use\s+App\\Modules\\{re.escape(module)}\\', content, re.MULTILINE):
        sys.exit(0)
    print(f"test file does not reference App\\Modules\\{module}", file=sys.stderr)
    sys.exit(1)

print(f"namespace '{namespace}' is outside App\\Modules\\{module}", file=sys.stderr)
sys.exit(1)
PYEOF
}

deleted_any=false
for f in "${ALL_FILES[@]}"; do
    if [[ -f "$f" ]]; then
        if GUARD_REASON=$(namespace_guard "$f" 2>&1); then
            action "$f"
            deleted_any=true
        else
            echo -e "  ${RED}✗ Refused to delete (${GUARD_REASON}): $f${NC}"
        fi
    fi
done

# Try to remove now-empty subdirectories of the module + view dir.
if [[ "$DRY_RUN" != true ]]; then
    rmdir "${VIEW_DIR}/partials" 2>/dev/null || true
    rmdir "${VIEW_DIR}" 2>/dev/null || true
    for sub in Controllers Services Requests Config Language/en Language/es Language; do
        rmdir "${MODULE_DIR}/${sub}" 2>/dev/null || true
    done
    rmdir "${MODULE_DIR}" 2>/dev/null || true
fi

# ─── Un-inject routes ──────────────────────────────────────────────────────────
ROUTES_FILE="${MODULE_DIR}/Config/Routes.php"
if [[ -f "$ROUTES_FILE" ]]; then
    if grep -q "${RESOURCE}Controller" "$ROUTES_FILE"; then
        if [[ "$DRY_RUN" == true ]]; then
            echo -e "  ${YELLOW}[dry-run] Would strip ${RESOURCE} routes from ${ROUTES_FILE}${NC}"
        else
            python3 - "$ROUTES_FILE" "$RESOURCE" "$ROUTE_SEGMENT" <<'PYEOF'
import sys, re

routes_file = sys.argv[1]
resource = sys.argv[2]
route_segment = sys.argv[3]

with open(routes_file, 'r') as f:
    content = f.read()

lines = content.splitlines(keepends=True)
out = []
in_block = False

for line in lines:
    stripped = line.strip()

    # Heuristic: route block for this resource is bounded above by a comment
    # mentioning the resource (`// Product`, `// Product Routes`, `// products`).
    if stripped.startswith('//'):
        comment_lower = stripped.lower()
        if (
            resource.lower() in comment_lower
            or route_segment.lower() in comment_lower
            or route_segment.replace('-', '_').lower() in comment_lower
        ):
            in_block = True
            continue  # drop the comment

    # Drop any route line that references this resource's controller.
    if f"{resource}Controller::" in line:
        in_block = True
        continue

    # If we were in the resource block and hit a blank line, close it but keep
    # one separator to avoid collapsing the file.
    if in_block:
        if stripped == '':
            in_block = False
            out.append(line)
            continue
        # Different resource block (controller ref or new comment) — exit ours.
        if 'Controller::' in line or stripped.startswith('//'):
            in_block = False
        else:
            continue

    out.append(line)

with open(routes_file, 'w') as f:
    f.write(''.join(out))
PYEOF
            echo -e "  ${GREEN}✓ Stripped ${RESOURCE} routes from ${ROUTES_FILE}${NC}"
            deleted_any=true
        fi
    fi
fi

# ─── Un-register service from app/Config/Services.php ──────────────────────────
SERVICES_FILE="app/Config/Services.php"
if grep -q "function ${SERVICE_NAME}(" "$SERVICES_FILE" 2>/dev/null; then
    if [[ "$DRY_RUN" == true ]]; then
        echo -e "  ${YELLOW}[dry-run] Would un-register ${SERVICE_NAME} from ${SERVICES_FILE}${NC}"
    else
        UNREGISTER_OUTPUT=$(python3 - "$SERVICES_FILE" "$SERVICE_NAME" "${RESOURCE}ApiService" "${RESOURCE}ApiServiceInterface" "$MODULE" 2>&1 <<'PYEOF'
import sys, re

services_file = sys.argv[1]
service_name = sys.argv[2]
service_class = sys.argv[3]
service_iface = sys.argv[4]
module = sys.argv[5]

with open(services_file, 'r') as f:
    content = f.read()

# Guard: refuse to un-register if the existing factory's return type FQCN
# does not live under App\Modules\{module}\Services\. Catches the case where
# 'apiKeyApiService' was registered by the starter for App\Modules\ApiKeys\
# and we'd otherwise destroy it while removing 'APIKey' from 'Security'.
sig_pat = re.compile(
    r'function\s+' + re.escape(service_name) + r'\s*\([^)]*\)\s*:\s*([\\A-Za-z0-9_]+)'
)
sig_match = sig_pat.search(content)
if sig_match is None:
    print(f"ERROR: could not locate signature of {service_name} in {services_file}", file=sys.stderr)
    sys.exit(1)

short_type = sig_match.group(1).lstrip('\\')
expected_ns = f"App\\Modules\\{module}\\Services"

# Resolve the short type to its FQCN via the file's `use` block.
fqcn = None
if '\\' in short_type:
    fqcn = short_type
else:
    use_pat = re.compile(r'^use\s+([A-Za-z0-9_\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?\s*;', re.MULTILINE)
    for use_match in use_pat.finditer(content):
        full, alias = use_match.group(1), use_match.group(2)
        if alias is not None:
            if alias == short_type:
                fqcn = full
                break
        else:
            if full.split('\\')[-1] == short_type:
                fqcn = full
                break

if fqcn is None:
    print(f"ERROR: could not resolve FQCN for return type '{short_type}' of {service_name}", file=sys.stderr)
    sys.exit(2)

if not fqcn.startswith(expected_ns + "\\"):
    print(
        f"REFUSED: factory '{service_name}' returns '{fqcn}', which lives outside "
        f"'{expected_ns}\\'. Refusing to un-register a factory that belongs to "
        f"another module.",
        file=sys.stderr,
    )
    sys.exit(3)

# Drop the public static factory method block.
pattern = re.compile(
    r"\n[ \t]*public static function " + re.escape(service_name) + r"\([^)]*\)[^{]*\{.*?\n    \}\n",
    re.DOTALL
)
content = pattern.sub('', content, count=1)

# Drop the use statements (class + interface) for this service.
for ident in (service_class, service_iface):
    use_pat = re.compile(rf"^use App\\Modules\\{re.escape(module)}\\Services\\{re.escape(ident)};\n", re.MULTILINE)
    content = use_pat.sub('', content, count=1)

with open(services_file, 'w') as f:
    f.write(content)

print(f"OK: un-registered {service_name}")
PYEOF
        ) || UNREGISTER_EXIT=$?
        UNREGISTER_EXIT=${UNREGISTER_EXIT:-0}

        if [[ $UNREGISTER_EXIT -eq 0 ]]; then
            echo -e "  ${GREEN}✓ Un-registered ${SERVICE_NAME} from ${SERVICES_FILE}${NC}"
            deleted_any=true
        else
            echo -e "  ${RED}✗ ${UNREGISTER_OUTPUT}${NC}"
        fi
    fi
fi

# ─── Final summary ─────────────────────────────────────────────────────────────
echo ""
if [[ "$deleted_any" != true ]]; then
    echo -e "${YELLOW}Nothing to remove. Did you mean a different resource or module?${NC}"
    exit 0
fi

if [[ "$DRY_RUN" == true ]]; then
    echo -e "${BLUE}Dry-run complete. Re-run without --dry-run to apply.${NC}"
else
    echo -e "${GREEN}✅ Module removed. Restart 'php spark serve' to drop the cached routes.${NC}"
    echo -e "${YELLOW}Note: PSR-4 entry for module '${MODULE}' is preserved (other resources may live there).${NC}"
    echo -e "${YELLOW}      Remove manually from app/Config/Autoload.php if the entire module is gone.${NC}"
fi
