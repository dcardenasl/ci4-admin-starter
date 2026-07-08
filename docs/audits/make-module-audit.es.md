# Auditoría de `bin/make-module.sh` — 2026-04-29

> Plan ejecutado: 12 escenarios (M01–M12) sobre una copia desechable del starter en `/tmp/ci4-audit/audit-kit-admin/`. Evidencia bruta en `/tmp/ci4-audit/_audit/traces/` (no versionada). Esta auditoría es **solo diagnóstico** — los parches se discutirán por separado.

## Resumen ejecutivo

| ID  | Escenario                                  | Resultado          | Severidad |
|-----|--------------------------------------------|--------------------|-----------|
| M01 | happy path mínimo                          | ✅ PASS            | —         |
| M02 | route segment custom                       | ✅ PASS            | —         |
| M03 | `--dry-run`                                | ✅ PASS            | —         |
| M04 | `--force` overwrite                        | ✅ PASS            | —         |
| M05 | idempotencia sin `--force`                 | ✅ PASS            | —         |
| M06 | segundo recurso, mismo módulo              | ✅ PASS            | —         |
| M07 | acrónimo `APIKey` en `Security`            | ⚠ exit 0 pero módulo inservible | **P0** |
| M08 | argumentos inválidos (lowercase, no `/`)   | ✅ rechazo limpio  | —         |
| M09 | endpoint API inexistente                   | ✅ PASS (sin verificación) | P2 |
| M10 | smoke browser (route + auth filter)        | ✅ PASS (302 → /login) | —     |
| M11 | phpunit sobre tests generados              | ✅ 9/9 assertions  | —         |
| M12 | `Services.php` con comentario stray pre-existente | ✅ PASS     | —         |

**Conteo por severidad:** P0 = 1 · P1 = 0 · P2 = 4 · sin severidad = 7.  
**Hallazgos transversales con `make-crud.sh`:** los acrónimos generan basura simétrica en API y Admin (P0 común).

## Lo que funciona bien

1. **Generación completa de un módulo CRUD.** El happy path (M01) produce 16 archivos: Controller, Service + Interface, StoreRequest/UpdateRequest, Routes, Lang en/es, 6 vistas (index/show/create/edit + 2 partials) y 2 tests. Verificado con `find app/Modules/Catalog tests/feature tests/unit -name '*.php'`. Sin placeholders sin sustituir (`grep VIEW_ → 0 matches`).
2. **Auto-discovery de rutas y PSR-4.** El módulo nuevo es navegable inmediatamente (M10): `GET /admin/catalog/products` retorna 302 → `/login` por la combinación auth+admin filters. Esto demuestra que la registración PSR-4 (`app/Config/Autoload.php:53`), el route group del módulo (`app/Modules/Catalog/Config/Routes.php`) y el `BaseWebController` están todos cableados.
3. **Idempotencia de tres niveles.** M04 (`--force`) y M05 (sin `--force`):
   - `--force` sobrescribe los archivos del módulo pero **no** duplica entradas en `Autoload.php`, `Services.php`, ni `Routes.php` (`grep -c 'function productApiService(' Services.php` = 1 después de re-correr).
   - Sin `--force`, todo se salta con mensaje `⚠ Skipped (exists)` y deja el working tree sin diff.
   - El detector de PSR-4 (`bin/make-module.sh:241` con `grep -qF "'App\\Modules\\${MODULE}'"`) y el de servicios (`bin/register-service.php:33` con `grep -q 'function ${serviceKey}('`) son específicos por nombre, así que dos recursos del mismo módulo conviven sin pisarse (M06).
4. **`--dry-run` honesto.** M03 con `--dry-run` imprime cada acción que se haría (incluyendo PSR-4 y registro de servicio) sin escribir un solo byte (`git status --short → 0 lines`).
5. **Validación de inputs upfront.** M08 prueba los dos rechazos: regex `^[A-Z][a-zA-Z0-9]+$` para Resource/Module (`bin/make-module.sh:82-90`) y check `API_PATH != /*` (`bin/make-module.sh:92-95`). Mensajes son explícitos y exit 1.
6. **Robustez del registro de servicio.** M12 insertó un comentario stray inmediatamente después de `namespace Config;` y `bin/register-service.php` siguió localizando el último `use` y la `}` final, registrando el servicio sin perder ni el comentario ni el formato existente.
7. **Tests generados pasan en frío.** M11 (verificado en M01, M06, M09): `phpunit tests/feature/{R}FlowTest.php tests/unit/Services/{R}ApiServiceTest.php` retorna 9 tests / 24 assertions correctos sin tocar nada. Cubren: redirect a login, admin-only filter, index 200, validación store, mock de delete redirect, y los 5 verbos contra ApiClient.
8. **Validación post-write.** Bloque de `bin/make-module.sh:1171-1227` corre `php -l` sobre cada generado, verifica PSR-4 y comprueba registro de servicio antes de imprimir el banner final. Cuando M07 (acrónimo) generó archivos sintácticamente correctos pero semánticamente rotos, los `php -l` pasaron — no es un fallo de la validación, es una prueba de su límite (validación es de sintaxis, no de coherencia semántica).

## Lo que funciona mal

### 🔴 P0 · Acrónimos producen módulos sintácticamente válidos pero inservibles

**Reproducción (M07):**
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

**Causa raíz:** `to_snake()` en `bin/make-module.sh:103-105`:
```bash
to_snake() {
    echo "$1" | sed 's/\([A-Z]\)/_\1/g' | tr '[:upper:]' '[:lower:]' | sed 's/^_//'
}
```
Inserta `_` antes de **cada** mayúscula. Para `APIKey`:
- sed 1: `_A_P_I_Key`
- tr: `_a_p_i_key`
- sed 2: `a_p_i_key`

Luego `RESOURCE_PLURAL=$(pluralize "$RESOURCE_SNAKE")` → `a_p_i_keys`, `ROUTE_SEGMENT_UNDERSCORE` → `a_p_i_keys`, `LANG_PREFIX` → `a_p_i_keys`, `VIEW_PATH` → `security/a_p_i_keys`. El controlador y servicio mantienen `APIKey` (porque las variables de clase concatenan `${RESOURCE}` directo, p.ej. `${RESOURCE}Controller` en `bin/make-module.sh:141`).

**Doble culpa:** las cadenas legibles (`'A p i keys'`) se generan en `bin/make-module.sh:150-151` con `awk` capitalizando la primera palabra de `RESOURCE_SNAKE` (que ya está fragmentado).

**Impacto:** el dev tiene que renombrar manualmente:
- carpeta `app/Views/security/a_p_i_keys/` → `app/Views/security/api_keys/`
- 14 claves de lang (`a_p_i_keys_*` → `api_keys_*`)
- 14 valores de lang (`'A p i keys'` → `'API keys'`)
- 8 entradas de ruta (`a-p-i-keys` y `admin.security.a_p_i_keys`)
- referencias en views (`route_to('admin.security.a_p_i_keys.create')`)

Es decir, casi todo el módulo. La opción de pasar el cuarto argumento posicional `RouteSegment` corrige las rutas y nombres, **pero no corrige las claves de lang ni el path de vistas** (siguen derivando de `RESOURCE_SNAKE`). El usuario solo se entera del problema cuando carga la página y ve `A p i keys` en el `<h1>` o cuando los redirects fallan en producción.

**Hallazgo simétrico:** la API tiene el mismo bug en `app/Support/Scaffolding/StringHelper.php:59-62` (regex `(?<!^)[A-Z]`), donde `APIKey` produce `a_p_i_keys` para nombre de tabla. Misma raíz, distinto lenguaje (PHP vs bash). Solucionarlo en un sitio sin el otro deja inconsistente.

---

### 🟡 P2 · Sin verificación de que el endpoint API exista

M09: `bash bin/make-module.sh Phantom Catalog /this-does-not-exist` → exit 0, módulo completo. Por diseño documentado, pero el test **feature** generado pasa porque mockea `ApiClientInterface`, dando una falsa sensación de seguridad. El primer indicador de problema es cuando la página carga `/admin/catalog/phantom` y el `index.data()` devuelve un error de red o 404 silencioso. Posible mejora: el script podría aceptar un `--check-api[=URL]` opcional que haga `HEAD ${apiClient.baseUrl}${API_PATH}` y avise antes de scaffoldear.

---

### 🟡 P2 · Templates de vistas y forms son monocampo (`name`)

`bin/make-module.sh:363-405` genera StoreRequest/UpdateRequest hardcoded con un único campo `name` (validación `required|min_length[2]|max_length[255]`). Las vistas, las claves de lang (`field_name`) y los tests también asumen `name`. Para cualquier recurso que no sea trivial, el dev tiene que:
1. añadir campos a `Requests/{R}StoreRequest.php`
2. añadir `<input>` a `create.php` y `edit.php`
3. añadir `<dt>/<dd>` a `show.php`
4. añadir filtros a `partials/filters.php`
5. añadir columnas a `index.php`
6. añadir claves de lang en/es
7. actualizar tests

El “Next steps” del banner final (`bin/make-module.sh:1242-1257`) lo enumera, pero el costo de boilerplate por campo es alto. El motor de scaffolding del API recibe `--fields` con tipos y modificadores; aquí se pierde esa info al cruzar el boundary admin↔API (no hay un `--fields` simétrico en `make-module.sh`).

---

### 🟡 P2 · Idioma español del template arrastra `TODO` que rara vez se atiende

Reproducible en M01: `app/Modules/Catalog/Language/es/Catalog.php:5` queda con
```php
// TODO: Revisa todas las traducciones (singular/plural y género gramatical pueden variar).
```
…que un dev hispanohablante dejará pasar (suena a autocrítica del template) y un dev anglohablante no leerá. Para recursos con género incorrecto en español (“el casa” es absurdo, pero más sutil con “el especialidad”) las cadenas quedan rotas en producción. Mejor opción: emitir el TODO como comentario solo si el resource label tiene >1 palabra, o pedir al dev confirmar `género: m|f` interactivamente en una segunda pasada.

---

### 🟡 P2 · `.env` activo puede tener claves duplicadas tras `install.sh`

Observado al configurar el env del audit-kit (no es parte directo de `make-module.sh` pero del flujo end-to-end del orquestador). El template `env` envía:
```dotenv
# app.baseURL = ''
# app_baseURL = ''
```
Dos claves casi idénticas en líneas adyacentes (`app.baseURL` y `app_baseURL`). Mi `sed -i '' "s|^# app.baseURL = .*|app.baseURL = ...|"` matcheó ambas (el `.` en regex es comodín). CodeIgniter 4 lee la última, así que el comportamiento es correcto, pero el archivo queda con dos líneas activas que confunden al revisar. Bug del template, no del scaffold; queda anotado para ergonomía.

## Mejoras propuestas para impecabilidad

| Prioridad | Mejora | Archivos clave |
|----------|--------|----------------|
| **P0** | Reemplazar `to_snake` por una función que detecte runs de mayúsculas como una sola palabra. Ejemplo: el equivalente bash de `(?<!^)(?=[A-Z][a-z])` es factible con `sed`/`awk` o `python3` (que ya está en uso en el script). Así `APIKey → api_key`. Sincronizar con la fix del API (`StringHelper::toSnakeCase`). | `bin/make-module.sh:103-105`, `app/Support/Scaffolding/StringHelper.php:59-62` |
| **P0** | Validar el resource name al inicio del script: si contiene runs de mayúsculas (regex `[A-Z]{2,}`), emitir warning con sugerencia: *“Did you mean ApiKey instead of APIKey? Continue (y/n)?”*. Permitir bypass con flag `--accept-acronym`. | `bin/make-module.sh:80-95` |
| **P2** | Añadir flag opcional `--check-api[=URL]` que haga `curl -fs ${URL}${API_PATH}` con timeout 2s antes del scaffold y warn si no responde. URL default desde `apiClient.baseUrl` en `.env`. | `bin/make-module.sh` (nueva sección antes del directory creation) |
| **P2** | Aceptar `--fields name:string:required,price:decimal:required,description:text:nullable` (espejo del API) y generar Requests/Views/Lang con los campos correctos. Reduce el boilerplate post-scaffold de ~7 archivos a 0. | `bin/make-module.sh:363-405` (StoreRequest), generadores de view (`705-942`), `611-701` (lang) |
| **P2** | Detectar nombres con género ambiguo en español (matching simple a un set, p.ej. terminaciones `-ad/-ud/-ión` ≈ femenino) y rellenar los plurals/articulos correctamente, eliminando el TODO. Plan B: pedir interactivamente `Género (m/f)?` cuando no aplica `--dry-run`. | `bin/make-module.sh:611-701` |
| **P2** | Añadir validación post-scaffold de coherencia semántica: si `RESOURCE_SNAKE` matchea `^[a-z]_` (i.e. empieza con letra suelta seguida de underscore), emitir warning rojo. Catch tardío del bug P0 cuando el fix no se aplique. | `bin/make-module.sh:1171-1227` |
| **P2** | Rotular la línea de salida del scaffold como `UPDATED:` cuando el archivo destino preexistía (`Services.php`, `Autoload.php`, `Routes.php` del módulo en M06). Hoy se usa `✓ PSR-4 already registered` para uno y `✓ Created` para los otros — coherencia ayuda al log scanning. | `bin/make-module.sh:198-234` (`_write` y wrappers) |
| **P2** | Documentar en `bin/register-service.php` el contrato de formato esperado (`use` block contiguo, class final con `}` sola en su línea). Idealmente usar AST de `nikic/php-parser` (ya está en `vendor/`) en lugar de regex lineal — más robusto a refactors futuros del archivo. | `bin/register-service.php:38-87` |
| **P2** | Añadir `--remove` (o un script hermano `bin/remove-module.sh`) que invierta todo: borra el módulo, des-inyecta rutas/servicios, des-registra PSR-4. Symmetric a la propuesta de `make:crud:remove` del API. | nuevo `bin/remove-module.sh` o flag |

## Lista de regresiones recomendadas

Para sembrar contra futuras regresiones de los hallazgos:

1. **`tests/unit/Scaffolding/AcronymHandlingTest.php`** — invocar `bin/make-module.sh APIKey Security /security/api-keys --dry-run` y assertear que el output **no** contiene la cadena `a_p_i_keys` (ni en views, ni en lang prefix, ni en route names).
2. **`tests/feature/ScaffoldedModuleSmokeTest.php`** — escenario M10 automatizado: scaffold + `php spark serve` + `curl /admin/{module}/{segment}` + assert HTTP 302 a `/login`. Garantiza que las rutas registradas son alcanzables.
3. **`tests/unit/Scaffolding/ServiceRegistrationRobustnessTest.php`** — variantes del M12: comentario, namespaced use disordenado, atributos PHPStan, etc. Asegura que `register-service.php` no se rompe con formatos no canónicos.
4. **`tests/unit/Scaffolding/IdempotencyMatrixTest.php`** — corre `make-module.sh Resource Module /api` dos veces y assertea que `Autoload.php`, `Services.php`, y `Routes.php` no han cambiado tras la segunda corrida (excepto si `--force`).
5. **`tests/unit/Scaffolding/PlaceholderSubstitutionTest.php`** — hardcode el conjunto de placeholders esperados (`VIEW_ROUTE_NAME`, `VIEW_MODULE`, `VIEW_LANG_PREFIX_`, `VIEW_VIEW_PATH`) y assertea que ningún archivo generado contiene ninguno post-scaffold (catch para el bug donde una vista omite un placeholder y queda `VIEW_ROUTE_NAME` literal en runtime).

## Re-verificación (2026-04-30)

Tras el commit `f9ed0b4` que implementó las recomendaciones de arriba, se re-corrió la misma matriz contra el entorno desechable en `/tmp/ci4-audit/audit-kit-admin/` (logs en `_audit/reverify/`).

### Hallazgos cerrados

| Hallazgo | Estado | Evidencia |
|---|---|---|
| M07 P0 — acrónimo `APIKey` produce `a_p_i_keys` | ✅ Cerrado | `to_snake()` reescrito en Python, trata corridas de mayúsculas como una sola palabra. Verificado: prefix lang `api_keys_*`, ruta `api-keys`, view dir `security/api_keys/`. |
| P2 — sin verificación de endpoint API | ✅ Cerrado | `--check-api[=URL]` añadido (HEAD con timeout 2s). |
| P2 — sin script inverso | ✅ Cerrado | `bin/remove-module.sh` añadido (archivos + rutas + registro de servicio; PSR-4 preservado por diseño). |

Idempotencia (M05) y robustez de `Services.php` (M12) siguen verdes: una segunda invocación de `Product/Catalog` deja `Autoload.php`/`Services.php`/`Routes.php` sin cambios; el conteo de `productApiService(` se mantiene exactamente en 1.

### Hallazgo nuevo · 🔴 P0 · Colisión de acrónimo con starter no detectada; `remove-module.sh` luego daña el starter

Estaba oculto en el M07 original porque la aserción solo miraba las cadenas del output (`a_p_i_keys`). Aparece sólo cuando la forma `to_camel()` del nombre del recurso coincide con una factoría de servicio que el starter ya expone, **y** cuando el filesystem es case-insensitive (macOS HFS+/APFS, Windows NTFS).

**Reproducción:**

```bash
# Paso 1 — generar
bash bin/make-module.sh APIKey Security /security/api-keys
# → exit 0
# Output (líneas relevantes):
#   ⚠ Skipped (exists):  tests/feature/APIKeyFlowTest.php          ← en realidad es ApiKeyFlowTest.php del starter
#   ⚠ Skipped (exists):  tests/unit/Services/APIKeyApiServiceTest.php
#   ✓ SKIP: apiKeyApiService already registered in Services.php   ← en realidad es la factoría del starter

# El controlador generado se cablea al servicio equivocado:
grep apiKeyApiService app/Modules/Security/Controllers/APIKeyController.php
# → $this->apiKeyService = service('apiKeyApiService');
#   …que resuelve a App\Modules\ApiKeys\Services\ApiKeyApiService (starter), NO a
#   App\Modules\Security\Services\APIKeyApiService (recién generado). Miswire silencioso.

# Paso 2 — intentar limpiar
bash bin/remove-module.sh APIKey Security
# → exit 0, output:
#   ✓ Deleted: tests/feature/APIKeyFlowTest.php           ← borra test del STARTER
#   ✓ Deleted: tests/unit/Services/APIKeyApiServiceTest.php  ← borra test del STARTER
#   ✓ Un-registered apiKeyApiService from app/Config/Services.php  ← rompe módulo ApiKeys del STARTER

git status --short
# →  M app/Config/Services.php
#    D tests/feature/ApiKeyFlowTest.php             ← archivo del starter, perdido
#    D tests/unit/Services/ApiKeyApiServiceTest.php
```

El `App\Modules\ApiKeys\Controllers\ApiKeyController` del starter ahora llama `service('apiKeyApiService')` contra una factoría que ya no existe. La página `/admin/api-keys` queda rota hasta que el dev se da cuenta y revierte.

**Causa raíz #1 — `make-module.sh` no chequea colisiones case-insensitive.** `bin/make-module.sh:1147-1182` (los wrappers `_write`) usan `[[ -f "$path" ]]` para decidir skip-vs-create. En HFS+/APFS esto retorna true para `APIKeyFlowTest.php` porque el path resuelve al `ApiKeyFlowTest.php` del starter. El script lo trata como un caso benigno "ya existe, skipping", sin advertir nunca que el archivo resuelto pertenece a otro recurso. **El equivalente en API (`MakeCrud`) hace el chequeo simétrico** (`ScaffoldingOrchestrator::validateFilesDoNotExist()` lanza con sugerencia explícita) — admin no lo tiene.

**Causa raíz #2 — `register-service.php` matchea solo por nombre de factoría.** `bin/register-service.php:33` hace `grep -q "function ${serviceKey}("` contra `Services.php`. Para `APIKey`, `to_camel()` produce `apiKey`, la clave de la factoría es `apiKeyApiService`, y el starter ya expone `apiKeyApiService(): App\Modules\ApiKeys\Services\ApiKeyApiServiceInterface`. Match encontrado ⇒ skip. El chequeo nunca compara el FQCN del tipo de retorno de la factoría existente con el FQCN de la clase que el módulo nuevo recién generó.

**Causa raíz #3 — `remove-module.sh` es simétrico al #1 pero en dirección destructiva.** `bin/remove-module.sh:110-127` arma una lista de paths absolutos (`tests/feature/${RESOURCE}FlowTest.php` etc.) y llama `rm -f` sobre cada uno. En un FS case-insensitive, `${RESOURCE}` = `APIKey` resuelve al `ApiKeyFlowTest.php` del starter y el archivo se borra. Misma forma para la des-registración en `Services.php`: `bin/remove-module.sh:225-258` busca el nombre de la factoría en `Services.php`, encuentra la del starter, y la quita.

**Impacto:** Cualquier nombre de recurso cuya forma `to_camel` colisione con una factoría que el starter ya ship (`apiKey`, `auditLog`, `user`, `file`, `metric`, `health`) sobre un FS case-insensitive produce un scaffold silenciosamente incorrecto. Correr `remove-module.sh` para limpiar luego rompe el starter. En Linux ext4 (case-sensitive) el modo de falla es distinto — los nuevos tests sí se escriben, pero el controlador sigue cableado a la factoría de servicio equivocada porque la causa raíz #2 sigue disparándose. El cableado-equivocado es la forma más peligrosa porque no es visible en `git status`.

**Propuesta de fix (para la conversación de parche posterior):**

| Capa | Fix | Archivos |
|---|---|---|
| Detección (`make-module.sh`) | Antes del primer `_write`, recorrer `app/Modules/*/Services/` y `tests/feature/`+`tests/unit/Services/` buscando filenames cuyo `realpath` iguale al path que el nuevo recurso escribiría. Si se encuentra, abortar con: *"El recurso '{X}' tapará a {fqcn-existente} en filesystems case-insensitive. Usa un nombre distinto (e.g. {canonical}) o elimina primero el módulo en conflicto."* Espejo de `ScaffoldingOrchestrator::validateFilesDoNotExist()` del API. | `bin/make-module.sh:1147-1182` |
| Chequeo de registración (`register-service.php`) | No matchear solo por nombre de factoría. Resolver también el FQCN del tipo de retorno de la factoría existente y compararlo con el FQCN que el módulo nuevo inyectaría. Si difieren, abortar con: *"Factoría `{key}` ya registrada para `{otroFqcn}`. Negándose a saltar silenciosamente — elige otro nombre de recurso o quita la registración en conflicto primero."* | `bin/register-service.php:33-45` |
| Seguridad de removal (`remove-module.sh`) | Antes de cada `rm`, leer la primera declaración `namespace`/`class` PHP del archivo objetivo. Negarse a borrar a menos que el namespace matchee `App\Modules\{MODULE}\…`. Mismo gate antes de des-registrar: negarse a des-registrar una factoría cuyo FQCN de retorno no viva bajo el módulo objetivo. | `bin/remove-module.sh:131-138` y `:223-259` |
| Documentación | La sección "Module-Based Organization" del CLAUDE.md puede mencionar: *"Nombres de recurso cuya forma camelCase (`to_camel`) coincida con una clave de factoría de servicio existente son rechazados — ver el contrato de make-module.sh / remove-module.sh para la lista completa."* | `ci4-admin-starter/CLAUDE.md` |

**Regresiones recomendadas (extender la lista de arriba):**

6. **`tests/unit/Scaffolding/CaseInsensitiveCollisionTest.php`** — invocar `make-module.sh APIKey Security /security/api-keys` contra un starter sintético que ship `ApiKey*`; assertear exit ≠ 0 y cero cambios en working tree.
7. **`tests/unit/Scaffolding/RemoveModuleNamespaceGuardTest.php`** — generar un módulo `Foo/Bar`, luego invocar `remove-module.sh Foo Other` (módulo equivocado). Assertear que el script se niega a tocar los archivos.
8. **`tests/unit/Scaffolding/ServiceFactoryFqcnMismatchTest.php`** — pre-sembrar `Services.php` con una factoría `productApiService` cuyo FQCN de retorno apunte al módulo `Inventory`; luego correr `make-module.sh Product Catalog /…`. Assertear que el script aborta en vez de saltar la registración silenciosamente.

**Por qué P0 y no P1:** El audit original etiquetó M07 como P0 porque el síntoma era visible (`a p i keys` en la UI). La nueva manifestación es **invisible** para el dev — el scaffold reporta éxito, el smoke test pasa (mockeado), el cableado roto se nota sólo cuando la página se renderiza contra una API real y silenciosamente llama al dominio equivocado. P0 sigue siendo P0; la superficie sólo se movió de formato de strings a aislamiento de módulos.

## Apéndice — cómo reproducir

```bash
# 1. Copia desechable del starter
rsync -a --exclude=vendor --exclude=node_modules --exclude=.git \
  ci4-admin-starter/ /tmp/ci4-audit/audit-kit-admin/

# 2. Bootstrap
cd /tmp/ci4-audit/audit-kit-admin
cp env .env
sed -i '' 's|^# CI_ENVIRONMENT.*|CI_ENVIRONMENT = development|' .env
sed -i '' "s|^# app.baseURL.*|app.baseURL = 'http://localhost:8182/'|" .env
sed -i '' "s|^# apiClient.baseUrl.*|apiClient.baseUrl = 'http://localhost:8180'|" .env
mkdir -p writable/{cache,logs,session,uploads,debugbar} && chmod -R 0777 writable
composer install --no-interaction
git init -q && git add -A && git commit -q -m baseline

# 3. Ejecutar los 12 escenarios — comandos exactos en results.csv
cat /tmp/ci4-audit/_audit/results.csv | grep ',make-module.sh,'
```

Trazas crudas en `/tmp/ci4-audit/_audit/traces/` (no versionadas).
