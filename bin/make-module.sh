#!/bin/bash
#
# ci4-admin-starter Module Scaffolding
# Creates a new resource within an admin module and registers PSR-4 autoloading.
#
# Usage:
#   bash bin/make-module.sh <Resource> <Module> <ApiPath> [RouteSegment]
#
# Examples:
#   bash bin/make-module.sh Product Catalog /catalog/products
#   bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories
#   bash bin/make-module.sh Order Orders /orders
#

set -e
set -o pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

RESOURCE=${1:-}
MODULE=${2:-}
API_PATH=${3:-}
ROUTE_SEGMENT=${4:-}

if [[ -z "$RESOURCE" || -z "$MODULE" || -z "$API_PATH" ]]; then
    echo -e "${RED}❌ Missing arguments${NC}"
    echo ""
    echo "Usage:"
    echo "  bash bin/make-module.sh <Resource> <Module> <ApiPath> [RouteSegment]"
    echo ""
    echo "Arguments:"
    echo "  <Resource>      StudlyCase resource name  (e.g. Product, SchoolCategory)"
    echo "  <Module>        StudlyCase module name    (e.g. Catalog, Education)"
    echo "  <ApiPath>       API path for the resource (e.g. /catalog/products)"
    echo "  [RouteSegment]  URL segment override      (default: resource_plural with dashes)"
    echo ""
    echo "Examples:"
    echo "  bash bin/make-module.sh Product Catalog /catalog/products"
    echo "  bash bin/make-module.sh SchoolCategory Education /education/school-categories school-categories"
    exit 1
fi

# Change to project root (ci4-admin-starter/)
cd "$(dirname "$0")/.."

# ─── Name derivations ──────────────────────────────────────────────────────────

# StudlyCase → snake_case  (SchoolCategory → school_category)
# Uses tr instead of sed \L to stay compatible with macOS BSD sed
to_snake() {
    echo "$1" | sed 's/\([A-Z]\)/_\1/g' | tr '[:upper:]' '[:lower:]' | sed 's/^_//'
}

# StudlyCase → lower camelCase  (Audience → audience, SchoolCategory → schoolCategory)
to_camel() {
    local s="$1"
    local first
    first=$(printf '%s' "${s:0:1}" | tr '[:upper:]' '[:lower:]')
    echo "${first}${s:1}"
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
RESOURCE_PLURAL=$(pluralize "$RESOURCE_SNAKE")                # school_categories
MODULE_LOWER=$(echo "$MODULE" | tr '[:upper:]' '[:lower:]')  # shows

if [[ -z "$ROUTE_SEGMENT" ]]; then
    ROUTE_SEGMENT="${RESOURCE_PLURAL//_/-}"                   # school-categories
fi

ROUTE_SEGMENT_UNDERSCORE="${ROUTE_SEGMENT//-/_}"              # school_categories (for view paths / route names)

SERVICE_NAME="${RESOURCE_CAMEL}ApiService"                    # audienceApiService
SERVICE_CLASS="${RESOURCE}ApiService"                         # AudienceApiService
SERVICE_IFACE="${RESOURCE}ApiServiceInterface"                 # AudienceApiServiceInterface
CONTROLLER_CLASS="${RESOURCE}Controller"                      # AudienceController
STORE_REQUEST="${RESOURCE}StoreRequest"                       # AudienceStoreRequest
UPDATE_REQUEST="${RESOURCE}UpdateRequest"                     # AudienceUpdateRequest
ROUTE_NAME="admin.${MODULE_LOWER}.${ROUTE_SEGMENT_UNDERSCORE}"  # admin.shows.audiences
LANG_PREFIX="${RESOURCE_PLURAL}"                              # school_categories  (used as lang key prefix)
VIEW_PATH="${MODULE_LOWER}/${ROUTE_SEGMENT_UNDERSCORE}"       # shows/audiences
MODULE_DIR="app/Modules/${MODULE}"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Admin Module Scaffolding — ${RESOURCE} in ${MODULE}${NC}"
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

# ─── PSR-4 registration ────────────────────────────────────────────────────────

AUTOLOAD_FILE="app/Config/Autoload.php"
IS_NEW_MODULE=false

if grep -qF "'App\\Modules\\${MODULE}'" "$AUTOLOAD_FILE"; then
    echo -e "${GREEN}✓ PSR-4 already registered (existing module)${NC}"
else
    IS_NEW_MODULE=true
    echo -e "${YELLOW}Registering PSR-4 namespace for new module ${MODULE}...${NC}"

    python3 - "$AUTOLOAD_FILE" "$MODULE" <<'PYEOF'
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
    print("WARN: could not auto-inject — add manually:")
    print(f"    {new_entry}")
    sys.exit(0)

with open(autoload_file, 'w') as f:
    f.write(new_content)
print("OK")
PYEOF

    echo -e "${GREEN}✓ PSR-4 entry added to Autoload.php${NC}"
fi

# ─── Directory structure ───────────────────────────────────────────────────────

echo ""
echo -e "${YELLOW}Creating directories...${NC}"
mkdir -p "${MODULE_DIR}/Controllers"
mkdir -p "${MODULE_DIR}/Services"
mkdir -p "${MODULE_DIR}/Requests"
mkdir -p "${MODULE_DIR}/Config"
mkdir -p "${MODULE_DIR}/Language/en"
mkdir -p "${MODULE_DIR}/Language/es"
mkdir -p "app/Views/${VIEW_PATH}/partials"
echo -e "${GREEN}✓ Done${NC}"

# ─── File generation ───────────────────────────────────────────────────────────

echo ""
echo -e "${YELLOW}Generating files...${NC}"

# Write a file only if it does not already exist
write_file() {
    local path="$1"
    local content="$2"
    if [[ -f "$path" ]]; then
        echo -e "  ${YELLOW}⚠ Skipped (exists):  ${path}${NC}"
    else
        printf '%s\n' "$content" > "$path"
        echo -e "  ${GREEN}✓ Created:           ${path}${NC}"
    fi
}

# Write a file from stdin (heredoc) only if it does not already exist
write_heredoc() {
    local path="$1"
    local content
    content=$(cat)
    if [[ -f "$path" ]]; then
        echo -e "  ${YELLOW}⚠ Skipped (exists):  ${path}${NC}"
    else
        printf '%s\n' "$content" > "$path"
        echo -e "  ${GREEN}✓ Created:           ${path}${NC}"
    fi
}

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

ROUTE_BLOCK="\n    // ${RESOURCE}\n    \$routes->get('${ROUTE_SEGMENT}', '${NS}::index', ['as' => '${ROUTE_NAME}']);\n    \$routes->get('${ROUTE_SEGMENT}/data', '${NS}::data', ['as' => '${ROUTE_NAME}.data']);\n    \$routes->get('${ROUTE_SEGMENT}/create', '${NS}::create', ['as' => '${ROUTE_NAME}.create']);\n    \$routes->post('${ROUTE_SEGMENT}', '${NS}::store', ['as' => '${ROUTE_NAME}.store']);\n    \$routes->get('${ROUTE_SEGMENT}/(:num)/edit', '${NS}::edit/\$1', ['as' => '${ROUTE_NAME}.edit']);\n    \$routes->post('${ROUTE_SEGMENT}/(:num)', '${NS}::update/\$1', ['as' => '${ROUTE_NAME}.update']);\n    \$routes->post('${ROUTE_SEGMENT}/(:num)/delete', '${NS}::delete/\$1', ['as' => '${ROUTE_NAME}.delete']);"

if [[ ! -f "$ROUTES_FILE" ]]; then
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
    \$routes->get('${ROUTE_SEGMENT}/(:num)/edit', '${NS}::edit/\$1', ['as' => '${ROUTE_NAME}.edit']);
    \$routes->post('${ROUTE_SEGMENT}/(:num)', '${NS}::update/\$1', ['as' => '${ROUTE_NAME}.update']);
    \$routes->post('${ROUTE_SEGMENT}/(:num)/delete', '${NS}::delete/\$1', ['as' => '${ROUTE_NAME}.delete']);
});" > "$ROUTES_FILE"
    echo -e "  ${GREEN}✓ Created:           ${ROUTES_FILE}${NC}"
else
    # Append the route block inside the existing group, before the closing });
    python3 - "$ROUTES_FILE" "$ROUTE_SEGMENT" "$NS" "$ROUTE_NAME" "$RESOURCE" <<'PYEOF'
import sys, re

routes_file, seg, ns, name, resource = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5]

with open(routes_file) as f:
    content = f.read()

block = (
    f"\n    // {resource}\n"
    f"    $routes->get('{seg}', '{ns}::index', ['as' => '{name}']);\n"
    f"    $routes->get('{seg}/data', '{ns}::data', ['as' => '{name}.data']);\n"
    f"    $routes->get('{seg}/create', '{ns}::create', ['as' => '{name}.create']);\n"
    f"    $routes->post('{seg}', '{ns}::store', ['as' => '{name}.store']);\n"
    f"    $routes->get('{seg}/(:num)/edit', '{ns}::edit/$1', ['as' => '{name}.edit']);\n"
    f"    $routes->post('{seg}/(:num)', '{ns}::update/$1', ['as' => '{name}.update']);\n"
    f"    $routes->post('{seg}/(:num)/delete', '{ns}::delete/$1', ['as' => '{name}.delete']);"
)

new_content = re.sub(r'(\n\}\);)', '\n' + block + r'\1', content, count=1)

with open(routes_file, 'w') as f:
    f.write(new_content)
PYEOF
    echo -e "  ${GREEN}✓ Route block appended: ${ROUTES_FILE}${NC}"
fi

# ── Language stubs ─────────────────────────────────────────────────────────────

RESOURCE_LABEL=$(echo "${RESOURCE_SNAKE//_/ }" | awk '{$1=toupper(substr($1,1,1))substr($1,2)}1')
RESOURCE_LOWER=$(echo "$RESOURCE_LABEL" | tr '[:upper:]' '[:lower:]')

write_lang() {
    local lang_file="$1"

    if [[ -f "$lang_file" ]]; then
        echo -e "  ${YELLOW}⚠ Skipped (exists):  ${lang_file}${NC}"
        echo -e "     → Add '${LANG_PREFIX}_*' keys to it manually"
        return
    fi

    printf '%s\n' "<?php

declare(strict_types=1);

return [
    // ${RESOURCE} — list & actions
    '${LANG_PREFIX}_title'          => '${RESOURCE_LABEL}s',
    '${LANG_PREFIX}_new'            => 'New ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_create'         => 'New ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_edit'           => 'Edit ${RESOURCE_LABEL}',
    '${LANG_PREFIX}_not_found'      => '${RESOURCE_LABEL} not found.',
    '${LANG_PREFIX}_create_success' => '${RESOURCE_LABEL} created successfully.',
    '${LANG_PREFIX}_create_failed'  => 'Could not create the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_update_success' => '${RESOURCE_LABEL} updated successfully.',
    '${LANG_PREFIX}_update_failed'  => 'Could not update the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_delete_success' => '${RESOURCE_LABEL} deleted successfully.',
    '${LANG_PREFIX}_delete_failed'  => 'Could not delete the ${RESOURCE_LOWER}.',
    '${LANG_PREFIX}_empty'          => 'No ${RESOURCE_LOWER}s registered yet.',
    '${LANG_PREFIX}_search_placeholder' => 'Search by name...',
    '${LANG_PREFIX}_loading'        => 'Loading ${RESOURCE_LOWER}s...',
    '${LANG_PREFIX}_no_results'     => 'No ${RESOURCE_LOWER}s found.',

    // ${RESOURCE} — form fields (add more as needed)
    'field_name'                    => 'Name',
];" > "$lang_file"
    echo -e "  ${GREEN}✓ Created:           ${lang_file}${NC}"
}

write_lang "${MODULE_DIR}/Language/en/${MODULE}.php"
write_lang "${MODULE_DIR}/Language/es/${MODULE}.php"

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

# Replace placeholders with actual derived values
sed -i '' \
    -e "s|VIEW_ROUTE_NAME|${ROUTE_NAME}|g" \
    -e "s|VIEW_MODULE|${MODULE}|g" \
    -e "s|VIEW_LANG_PREFIX_|${LANG_PREFIX}_|g" \
    -e "s|VIEW_VIEW_PATH|${VIEW_PATH}|g" \
    "app/Views/${VIEW_PATH}/index.php" 2>/dev/null || true

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

sed -i '' \
    -e "s|VIEW_ROUTE_NAME|${ROUTE_NAME}|g" \
    -e "s|VIEW_MODULE|${MODULE}|g" \
    -e "s|VIEW_LANG_PREFIX_|${LANG_PREFIX}_|g" \
    "app/Views/${VIEW_PATH}/create.php" 2>/dev/null || true

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

sed -i '' \
    -e "s|VIEW_ROUTE_NAME|${ROUTE_NAME}|g" \
    -e "s|VIEW_MODULE|${MODULE}|g" \
    -e "s|VIEW_LANG_PREFIX_|${LANG_PREFIX}_|g" \
    "app/Views/${VIEW_PATH}/edit.php" 2>/dev/null || true

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

sed -i '' \
    -e "s|VIEW_MODULE|${MODULE}|g" \
    -e "s|VIEW_LANG_PREFIX_|${LANG_PREFIX}_|g" \
    "app/Views/${VIEW_PATH}/partials/filters.php" 2>/dev/null || true

write_heredoc "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" << 'VIEW_EOF_MARKER'
<a href="<?= route_to('VIEW_ROUTE_NAME.create') ?>" class="<?= esc(action_button_class('primary')) ?>">
    <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
    <?= lang('VIEW_MODULE.VIEW_LANG_PREFIX_new') ?>
</a>
VIEW_EOF_MARKER

sed -i '' \
    -e "s|VIEW_ROUTE_NAME|${ROUTE_NAME}|g" \
    -e "s|VIEW_MODULE|${MODULE}|g" \
    -e "s|VIEW_LANG_PREFIX_|${LANG_PREFIX}_|g" \
    "app/Views/${VIEW_PATH}/partials/toolbar_actions.php" 2>/dev/null || true

# ─── Auto-register service in Services.php ────────────────────────────────────

echo ""
echo -e "${YELLOW}Registering service in Services.php...${NC}"

REGISTER_RESULT=$(php bin/register-service.php "$MODULE" "$SERVICE_CLASS" "$SERVICE_IFACE" "$SERVICE_NAME" 2>&1)
REGISTER_EXIT=$?

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
    echo "        return new ${SERVICE_CLASS}(static::apiClient());"
    echo "    }"
fi

# ─── Summary ───────────────────────────────────────────────────────────────────

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Scaffolding complete!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
if [[ "$IS_NEW_MODULE" == true ]]; then
echo "  1. Add sidebar entry:  app/Views/layouts/partials/sidebar.php"
fi
echo "  2. Customize form fields in:"
echo "       ${MODULE_DIR}/Requests/${STORE_REQUEST}.php  (validation rules)"
echo "       app/Views/${VIEW_PATH}/create.php & edit.php (add/remove <div> field blocks)"
echo "       app/Views/${VIEW_PATH}/partials/filters.php  (add filter selects if needed)"
echo "       app/Views/${VIEW_PATH}/index.php             (add table columns for new fields)"
echo "  3. Add language keys for new fields in:"
echo "       ${MODULE_DIR}/Language/en/${MODULE}.php"
echo "       ${MODULE_DIR}/Language/es/${MODULE}.php"
echo "  4. Restart the dev server (routes are not hot-reloaded):"
echo "       pkill -f 'spark serve'; php spark serve --port 8082 &"
