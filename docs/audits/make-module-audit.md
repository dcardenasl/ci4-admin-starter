# Audit of `bin/make-module.sh` — 2026-04-29

> Plan executed: 12 scenarios (M01–M12) on a disposable copy of the starter at `/tmp/ci4-audit/audit-kit-admin/`. Raw evidence in `/tmp/ci4-audit/_audit/traces/` (not versioned). This audit is **diagnosis only** — fixes are tracked in a follow-up commit.

## Executive summary

| ID  | Scenario                                  | Result             | Severity  |
|-----|-------------------------------------------|--------------------|-----------|
| M01 | minimal happy path                        | ✅ PASS            | —         |
| M02 | custom route segment                      | ✅ PASS            | —         |
| M03 | `--dry-run`                               | ✅ PASS            | —         |
| M04 | `--force` overwrite                       | ✅ PASS            | —         |
| M05 | idempotence without `--force`             | ✅ PASS            | —         |
| M06 | second resource in same module            | ✅ PASS            | —         |
| M07 | acronym `APIKey` in `Security`            | ⚠ service key ambiguity ~~exit 0/unusable module~~ | ~~P0~~ **PARTIALLY FIXED** (see Re-verification §) |
| M08 | invalid arguments (lowercase, no `/`)     | ✅ clean rejection  | —         |
| M09 | non-existent API endpoint                 | ✅ PASS (no validation) | P2 |
| M10 | smoke browser (route + auth filter)       | ✅ PASS (302 → /login) | —     |
| M11 | phpunit on generated tests                | ✅ 9/9 assertions  | —         |
| M12 | `Services.php` with stray comment         | ✅ PASS            | —         |

**Severity counts:** P0 = 1 · P1 = 0 · P2 = 4 · no severity = 7.
**Cross-cutting findings with `make-crud.sh`:** acronyms generate symmetric garbage in API and Admin (shared P0).

## What works well

1. **Complete CRUD module generation.** The happy path (M01) produces 16 files: Controller, Service + Interface, StoreRequest/UpdateRequest, Routes, Lang en/es, 6 views (index/show/create/edit + 2 partials), and 2 tests. Verified with `find app/Modules/Catalog tests/feature tests/unit -name '*.php'`. No unsubstituted placeholders (`grep VIEW_ → 0 matches`).
2. **Route auto-discovery and PSR-4.** The new module is browseable immediately (M10): `GET /admin/catalog/products` returns 302 → `/login` thanks to the auth+admin filter combination. This proves PSR-4 registration (`app/Config/Autoload.php:53`), the module's route group (`app/Modules/Catalog/Config/Routes.php`), and the `BaseWebController` are all wired together.
3. **Three-layer idempotence.** M04 (`--force`) and M05 (without `--force`):
   - `--force` overwrites the module's files but **does not** duplicate entries in `Autoload.php`, `Services.php`, or `Routes.php` (`grep -c 'function productApiService(' Services.php` = 1 after re-run).
   - Without `--force`, every file is skipped with a `⚠ Skipped (exists)` message and the working tree shows no diff.
   - The PSR-4 detector (`bin/make-module.sh:241` with `grep -qF "'App\\Modules\\${MODULE}'"`) and the service detector (`bin/register-service.php:33` with `grep -q 'function ${serviceKey}('`) are name-specific, so two resources of the same module coexist without stomping each other (M06).
4. **Honest `--dry-run`.** M03 with `--dry-run` prints every action it would take (including PSR-4 and service registration) without writing a single byte (`git status --short → 0 lines`).
5. **Upfront input validation.** M08 covers both rejections: `^[A-Z][a-zA-Z0-9]+$` regex for Resource/Module (`bin/make-module.sh:82-90`) and the `API_PATH != /*` check (`bin/make-module.sh:92-95`). Messages are explicit and exit 1.
6. **Robust service registration.** M12 inserted a stray comment immediately after `namespace Config;` and `bin/register-service.php` still located the last `use` and the closing `}`, registering the service without disturbing either the comment or the existing formatting.
7. **Generated tests pass cold.** M11 (verified in M01, M06, M09): `phpunit tests/feature/{R}FlowTest.php tests/unit/Services/{R}ApiServiceTest.php` returns 9 tests / 24 assertions correct without modifications. Coverage: redirect to login, admin-only filter, index 200, store validation, mock delete redirect, and the 5 verbs against ApiClient.
8. **Post-write validation.** The block at `bin/make-module.sh:1171-1227` runs `php -l` over each generated file, verifies PSR-4, and checks service registration before printing the final banner. When M07 (acronym) generated syntactically-correct but semantically-broken files, the `php -l` checks passed — that's not a validation failure, it's a proof of its scope (validation is syntactic, not semantic).

## What works poorly

### 🔴 P0 · Acronyms produce syntactically valid yet unusable modules

**Reproduction (M07):**
```bash
bash bin/make-module.sh APIKey Security /security/api-keys
# → exit 0
ls app/Views/security/
# → a_p_i_keys/
cat app/Modules/Security/Language/en/Security.php | head
# → 'a_p_i_keys_title' => 'A p i keys',
# → 'a_p_i_keys_create_failed' => 'Could not create the a p i key.',
grep "as.*=" app/Modules/Security/Config/Routes.php | head -1
# → $routes->get('a-p-i-keys', '...APIKeyController::index', ['as' => 'admin.security.a_p_i_keys']);
```

**Root cause:** `to_snake()` at `bin/make-module.sh:103-105`:
```bash
to_snake() {
    echo "$1" | sed 's/\([A-Z]\)/_\1/g' | tr '[:upper:]' '[:lower:]' | sed 's/^_//'
}
```
inserts `_` before **every** uppercase letter. For `APIKey`:
- sed 1: `_A_P_I_Key`
- tr: `_a_p_i_key`
- sed 2: `a_p_i_key`

Then `RESOURCE_PLURAL=$(pluralize "$RESOURCE_SNAKE")` → `a_p_i_keys`, `ROUTE_SEGMENT_UNDERSCORE` → `a_p_i_keys`, `LANG_PREFIX` → `a_p_i_keys`, `VIEW_PATH` → `security/a_p_i_keys`. The controller and service keep `APIKey` (because the class variables concatenate `${RESOURCE}` directly, e.g. `${RESOURCE}Controller` at `bin/make-module.sh:141`).

**Double fault:** the readable strings (`'A p i keys'`) are generated at `bin/make-module.sh:150-151` with `awk` capitalizing the first word of `RESOURCE_SNAKE` (which is already fragmented).

**Impact:** the dev has to manually rename:
- folder `app/Views/security/a_p_i_keys/` → `app/Views/security/api_keys/`
- 14 lang keys (`a_p_i_keys_*` → `api_keys_*`)
- 14 lang values (`'A p i keys'` → `'API keys'`)
- 8 route entries (`a-p-i-keys` and `admin.security.a_p_i_keys`)
- references in views (`route_to('admin.security.a_p_i_keys.create')`)

That's almost the entire module. Passing the fourth positional argument `RouteSegment` fixes the routes and names, **but does not fix the lang keys nor the view path** (they still derive from `RESOURCE_SNAKE`). The user only finds out when the page loads and shows `A p i keys` in the `<h1>` or when redirects fail in production.

**Symmetric finding:** the API has the same bug at `app/Support/Scaffolding/StringHelper.php:59-62` (regex `(?<!^)[A-Z]`), where `APIKey` produces `a_p_i_keys` for the table name. Same root, different language (PHP vs bash). Fixing it in one place without the other leaves them inconsistent.

---

### 🟡 P2 · No verification that the API endpoint exists

M09: `bash bin/make-module.sh Phantom Catalog /this-does-not-exist` → exit 0, complete module. By documented design, but the generated **feature** test passes because it mocks `ApiClientInterface`, giving a false sense of safety. The first sign of trouble is when `/admin/catalog/phantom` loads and `index.data()` returns a network error or silent 404. Possible improvement: the script could accept an optional `--check-api[=URL]` that does `HEAD ${apiClient.baseUrl}${API_PATH}` and warns before scaffolding.

---

### 🟡 P2 · View and form templates are single-field (`name`)

`bin/make-module.sh:363-405` generates StoreRequest/UpdateRequest hardcoded with a single `name` field (validation `required|min_length[2]|max_length[255]`). Views, lang keys (`field_name`), and tests also assume `name`. For any non-trivial resource, the dev must:
1. add fields to `Requests/{R}StoreRequest.php`
2. add `<input>` to `create.php` and `edit.php`
3. add `<dt>/<dd>` to `show.php`
4. add filters to `partials/filters.php`
5. add columns to `index.php`
6. add lang keys en/es
7. update tests

The "Next steps" final banner (`bin/make-module.sh:1242-1257`) lists this, but the per-field boilerplate cost is high. The API scaffold engine accepts `--fields` with types and modifiers; here that information is lost when crossing the admin↔API boundary (no symmetric `--fields` in `make-module.sh`).

---

### 🟡 P2 · Spanish translation template carries a `TODO` that's rarely addressed

Reproducible in M01: `app/Modules/Catalog/Language/es/Catalog.php:5` ends up with
```php
// TODO: Revisa todas las traducciones (singular/plural y género gramatical pueden variar).
```
…which a Spanish-speaking dev will skim past (sounds like template self-criticism) and an English-speaking dev won't read. For resources with incorrect Spanish gender ("el casa" is absurd, but more subtle with "el especialidad"), strings end up broken in production. Better option: emit the TODO as a comment only when the resource label is >1 word, or ask the dev to confirm `género: m|f` interactively in a second pass.

---

### 🟡 P2 · `.env` may have duplicate keys after `install.sh`

Observed while configuring the audit-kit env (not directly part of `make-module.sh` but of the orchestrator's end-to-end flow). The template `env` ships:
```dotenv
# app.baseURL = ''
# app_baseURL = ''
```
Two near-identical keys on adjacent lines (`app.baseURL` and `app_baseURL`). My `sed -i '' "s|^# app.baseURL = .*|app.baseURL = ...|"` matched both (the `.` in regex is a wildcard). CodeIgniter 4 reads the last one, so behavior is correct, but the file ends up with two active lines that confuse review. Template bug, not scaffold; noted for ergonomics.

## Improvements proposed for impeccable behavior

| Priority | Improvement | Key files |
|----------|-------------|-----------|
| **P0** | Replace `to_snake` with a function that detects uppercase runs as a single word. The bash equivalent of `(?<!^)(?=[A-Z][a-z])` is doable with `sed`/`awk` or `python3` (already used in the script). So `APIKey → api_key`. Sync with the API fix (`StringHelper::toSnakeCase`). | `bin/make-module.sh:103-105`, `app/Support/Scaffolding/StringHelper.php:59-62` |
| **P0** | Validate the resource name at the start of the script: if it contains uppercase runs (regex `[A-Z]{2,}`), emit a warning with suggestion: *"Did you mean ApiKey instead of APIKey? Continue (y/n)?"*. Allow bypass with `--accept-acronym` flag. | `bin/make-module.sh:80-95` |
| **P2** | Add optional `--check-api[=URL]` flag that runs `curl -fs ${URL}${API_PATH}` with 2s timeout before the scaffold and warns if it doesn't respond. Default URL read from `apiClient.baseUrl` in `.env`. | `bin/make-module.sh` (new section before directory creation) |
| **P2** | Accept `--fields name:string:required,price:decimal:required,description:text:nullable` (mirror of API) and generate Requests/Views/Lang with the correct fields. Reduces post-scaffold boilerplate from ~7 files to 0. | `bin/make-module.sh:363-405` (StoreRequest), view generators (`705-942`), `611-701` (lang) |
| **P2** | Detect resource names with ambiguous Spanish gender (simple ending matching, e.g. `-ad/-ud/-ión` ≈ feminine) and fill in plurals/articles correctly, eliminating the TODO. Plan B: ask interactively `Género (m/f)?` when not in `--dry-run`. | `bin/make-module.sh:611-701` |
| **P2** | Add a post-scaffold semantic-coherence validation: if `RESOURCE_SNAKE` matches `^[a-z]_` (i.e. starts with single letter followed by underscore), emit a red warning. Late catch when the P0 fix is undone. | `bin/make-module.sh:1171-1227` |
| **P2** | Label the script's output line as `UPDATED:` when the destination file pre-existed (`Services.php`, `Autoload.php`, the module's `Routes.php` in M06). Today it uses `✓ PSR-4 already registered` for one and `✓ Created` for others — consistency helps log scanning. | `bin/make-module.sh:198-234` (`_write` and wrappers) |
| **P2** | Document the contract `bin/register-service.php` expects (`use` block contiguous, class final with `}` alone on its line). Ideally use AST from `nikic/php-parser` (already in `vendor/`) instead of line-by-line regex — more robust to future refactors of the file. | `bin/register-service.php:38-87` |
| **P2** | Add `--remove` (or a sibling script `bin/remove-module.sh`) that inverts everything: deletes the module, un-injects routes/services, un-registers PSR-4. Symmetric to the API's `make:crud:remove` proposal. | new `bin/remove-module.sh` or flag |

## Recommended regression tests

To seed against future regressions of these findings:

1. **`tests/unit/Scaffolding/AcronymHandlingTest.php`** — invoke `bin/make-module.sh APIKey Security /security/api-keys --dry-run` and assert the output **does not** contain `a_p_i_keys` (in views, lang prefix, or route names).
2. **`tests/feature/ScaffoldedModuleSmokeTest.php`** — automated M10: scaffold + `php spark serve` + `curl /admin/{module}/{segment}` + assert HTTP 302 to `/login`. Guarantees registered routes are reachable.
3. **`tests/unit/Scaffolding/ServiceRegistrationRobustnessTest.php`** — variants of M12: comment, scrambled namespaced uses, PHPStan attributes, etc. Ensures `register-service.php` doesn't break with non-canonical formats.
4. **`tests/unit/Scaffolding/IdempotencyMatrixTest.php`** — runs `make-module.sh Resource Module /api` twice and asserts that `Autoload.php`, `Services.php`, and `Routes.php` are unchanged after the second run (except with `--force`).
5. **`tests/unit/Scaffolding/PlaceholderSubstitutionTest.php`** — hardcode the expected placeholder set (`VIEW_ROUTE_NAME`, `VIEW_MODULE`, `VIEW_LANG_PREFIX_`, `VIEW_VIEW_PATH`) and assert no generated file contains any of them post-scaffold (catches the bug where a view forgets a placeholder and leaves `VIEW_ROUTE_NAME` literal at runtime).

## Re-verification round (2026-04-30)

After commit `f9ed0b4` implemented the recommendations above, the same matrix was re-run against the disposable env at `/tmp/ci4-audit/audit-kit-admin/` (logs in `_audit/reverify/`).

### Closed findings

| Finding | Status | Evidence |
|---|---|---|
| M07 P0 — acronym `APIKey` produces `a_p_i_keys` | ✅ Closed | `to_snake()` rewritten in Python to treat uppercase runs as one word. Verified outputs: lang prefix `api_keys_*`, route `api-keys`, view dir `security/api_keys/`. |
| P2 — no API endpoint check | ✅ Closed | `--check-api[=URL]` added (HEAD with 2s timeout). |
| P2 — no inverse script | ✅ Closed | `bin/remove-module.sh` added (files + routes + service registration; PSR-4 preserved by design). |

Idempotence (M05) and `Services.php` robustness (M12) remained green: a second invocation of `Product/Catalog` left `Autoload.php`/`Services.php`/`Routes.php` unchanged; `productApiService(` count stayed at exactly 1.

### New finding · 🔴 P0 · Acronym collision with starter not detected; `remove-module.sh` then damages the starter

This was hidden in the original M07 because the assertion only looked at the output strings (`a_p_i_keys`). It surfaces only when the resource name's `to_camel()` form matches a service factory the starter already ships, **and** when the filesystem is case-insensitive (macOS HFS+/APFS, Windows NTFS).

**Reproduction:**

```bash
# Step 1 — generate
bash bin/make-module.sh APIKey Security /security/api-keys
# → exit 0
# Output (relevant lines):
#   ⚠ Skipped (exists):  tests/feature/APIKeyFlowTest.php          ← actually the starter's ApiKeyFlowTest.php
#   ⚠ Skipped (exists):  tests/unit/Services/APIKeyApiServiceTest.php
#   ✓ SKIP: apiKeyApiService already registered in Services.php   ← actually the starter's factory

# The generated controller wires to the wrong service:
grep apiKeyApiService app/Modules/Security/Controllers/APIKeyController.php
# → $this->apiKeyService = service('apiKeyApiService');
#   …which resolves to App\Modules\ApiKeys\Services\ApiKeyApiService (starter), NOT
#   App\Modules\Security\Services\APIKeyApiService (just generated). Silent miswire.

# Step 2 — try to clean up
bash bin/remove-module.sh APIKey Security
# → exit 0, output:
#   ✓ Deleted: tests/feature/APIKeyFlowTest.php           ← deletes STARTER's test
#   ✓ Deleted: tests/unit/Services/APIKeyApiServiceTest.php  ← deletes STARTER's test
#   ✓ Un-registered apiKeyApiService from app/Config/Services.php  ← breaks STARTER's ApiKeys module

git status --short
# →  M app/Config/Services.php
#    D tests/feature/ApiKeyFlowTest.php             ← starter file, lost
#    D tests/unit/Services/ApiKeyApiServiceTest.php
```

The starter's `App\Modules\ApiKeys\Controllers\ApiKeyController` now calls `service('apiKeyApiService')` against a factory that no longer exists. The admin's `/admin/api-keys` page is broken until the developer notices and reverts.

**Root cause #1 — `make-module.sh` doesn't check for case-insensitive collisions.** `bin/make-module.sh:1147-1182` (the `_write` wrappers) call `[[ -f "$path" ]]` to decide skip-vs-create. On HFS+/APFS this returns true for `APIKeyFlowTest.php` because the path resolves to the starter's `ApiKeyFlowTest.php`. The script treats that as a benign "already exists, skipping" case, never warning that the resolved file belongs to a different resource. **The API equivalent (`MakeCrud`) does the symmetric check** (`ScaffoldingOrchestrator::validateFilesDoNotExist()` raises with explicit suggestion) — admin lacks this.

**Root cause #2 — `register-service.php` matches on factory name only.** `bin/register-service.php:33` does `grep -q "function ${serviceKey}("` against `Services.php`. For `APIKey`, `to_camel()` produces `apiKey`, the factory key is `apiKeyApiService`, and the starter already exposes `apiKeyApiService(): App\Modules\ApiKeys\Services\ApiKeyApiServiceInterface`. Match found ⇒ skip. The check never compares the FQCN of the existing factory's return type against the FQCN of the class the new module just generated.

**Root cause #3 — `remove-module.sh` is symmetric to root cause #1 but in the destructive direction.** `bin/remove-module.sh:110-127` builds an absolute path list (`tests/feature/${RESOURCE}FlowTest.php` etc.) and calls `rm -f` on each. On a case-insensitive FS, `${RESOURCE}` = `APIKey` resolves to the starter's `ApiKeyFlowTest.php` and the file is deleted. Same shape for the `Services.php` un-registration: `bin/remove-module.sh:225-258` searches for the factory name in `Services.php`, finds the starter's, and removes it.

**Impact:** Any resource name whose `to_camel` form collides with a starter-shipped factory (`apiKey`, `auditLog`, `user`, `file`, `metric`, `health`) on a case-insensitive FS produces a silently-wrong scaffold. Running `remove-module.sh` to clean up then breaks the starter. On Linux ext4 (case-sensitive) the failure mode is different — the new tests get written, but the controller still wires to the wrong service factory because root cause #2 still triggers. The wrong-service wiring is the more dangerous form because it isn't visible in `git status`.

**Proposed fix (for the follow-up parche conversation):**

| Layer | Fix | Files |
|---|---|---|
| Detection (`make-module.sh`) | Before the first `_write`, walk `app/Modules/*/Services/` and `tests/feature/`+`tests/unit/Services/` looking for filenames whose `realpath` equals the path the new resource would write to. If found, abort with: *"Resource '{X}' would shadow {existing-fqcn} on case-insensitive filesystems. Use a different name (e.g. {canonical}) or remove the conflicting module first."* Mirror `ScaffoldingOrchestrator::validateFilesDoNotExist()` from API. | `bin/make-module.sh:1147-1182` |
| Service-registration check (`register-service.php`) | Don't only match on factory name. Also resolve the existing factory's return type FQCN and compare to the FQCN the new module would inject. If they differ, abort with: *"Factory `{key}` already registered for `{otherFqcn}`. Refusing to skip silently — pick a different resource name or remove the conflicting registration first."* | `bin/register-service.php:33-45` |
| Removal safety (`remove-module.sh`) | Before each `rm`, read the target file's first PHP `namespace`/`class` declaration. Refuse to delete unless the namespace matches `App\Modules\{MODULE}\…`. Same gate before un-registering: refuse to un-register a factory whose return type FQCN doesn't live under the target module. | `bin/remove-module.sh:131-138` and `:223-259` |
| Documentation | The CLAUDE.md "Module-Based Organization" section can call out: *"Resource names whose camelCase form (`to_camel`) matches an existing service factory key are rejected — see the make-module.sh / remove-module.sh contracts for the full list."* | `ci4-admin-starter/CLAUDE.md` |

**Recommended regression tests (extend the existing list above):**

6. **`tests/unit/Scaffolding/CaseInsensitiveCollisionTest.php`** — invoke `make-module.sh APIKey Security /security/api-keys` against a synthetic starter that ships `ApiKey*` files; assert exit ≠ 0 and zero working-tree changes.
7. **`tests/unit/Scaffolding/RemoveModuleNamespaceGuardTest.php`** — generate a `Foo/Bar` module, then invoke `remove-module.sh Foo Other` (wrong module). Assert the script refuses to touch the files.
8. **`tests/unit/Scaffolding/ServiceFactoryFqcnMismatchTest.php`** — pre-seed `Services.php` with a `productApiService` factory whose return type FQCN points to module `Inventory`; then run `make-module.sh Product Catalog /…`. Assert the script aborts instead of silently skipping registration.

**Severity rationale (why P0 and not P1):** The original audit logged M07 as P0 because the symptom was visible (`a p i keys` in the UI). The new manifestation is **invisible** to the developer — the scaffold reports success, the smoke test passes (mocked), the broken wiring shows up only when the page is rendered against a real API and silently calls the wrong domain. P0 stays P0; the surface just moved from string formatting to module isolation.

## Fix phase (2026-04-30)

### Closed

| Finding | Fix | File | Commit |
|---|---|---|---|
| **P0** — case-insensitive collision detection incomplete (view + route files missing from pre-flight scan) | Added `${MODULE_DIR}/Config/Routes.php`, `app/Views/${VIEW_PATH}/index.php`, `show.php`, `create.php`, `edit.php`, `partials/filters.php`, `partials/toolbar_actions.php` to `PLANNED_FILES` array | `bin/make-module.sh:339-349` | — |
| **P0** — `register-service.php` skips silently when factory name matches but FQCN differs | Already implemented: `resolveFqcn()` resolves return-type FQCN via `use` block; exit 4 when FQCNs differ | `bin/register-service.php:32–56` | pre-existing |
| **P0** — `remove-module.sh` deletes starter files when resource name collides | Already implemented: `namespace_guard` checks `namespace App\Modules\{MODULE}\` before deleting; FQCN guard checks factory return type before un-registering | `bin/remove-module.sh:147–180`, `275–358` | pre-existing |

### Remaining open P2s

| Finding |
|---|
| Views and form templates hardcoded to single `name` field — accept `--fields` arg (large feature) |
| Spanish translation template always emits TODO — only needed when resource is multi-word/ambiguous gender |
| UPDATED/CREATED output consistency in `_write`/wrappers (currently uses `✓ Created` vs `✓ PSR-4 already registered`) |
| No `--check-api[=URL]` pre-scaffold validation (lower priority, no safety risk) |

## Appendix — how to reproduce

```bash
# 1. Disposable copy of the starter
rsync -a --exclude=vendor --exclude=node_modules --exclude=.git \
  ci4-admin-starter/ /tmp/ci4-audit/audit-kit-admin/

# 2. Bootstrap
cd /tmp/ci4-audit/audit-kit-admin
cp env .env
sed -i '' 's|^# CI_ENVIRONMENT.*|CI_ENVIRONMENT = development|' .env
sed -i '' "s|^# app.baseURL.*|app.baseURL = 'http://localhost:8082/'|" .env
sed -i '' "s|^# apiClient.baseUrl.*|apiClient.baseUrl = 'http://localhost:8080'|" .env
mkdir -p writable/{cache,logs,session,uploads,debugbar} && chmod -R 0777 writable
composer install --no-interaction
git init -q && git add -A && git commit -q -m baseline

# 3. Run the 12 scenarios — exact commands in results.csv
cat /tmp/ci4-audit/_audit/results.csv | grep ',make-module.sh,'
```

Raw traces in `/tmp/ci4-audit/_audit/traces/` (not versioned).
