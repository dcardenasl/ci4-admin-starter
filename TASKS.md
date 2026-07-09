# TASKS — ci4-admin-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-06-10 (ADM-010 ✅ completado · CSV export/import scaffold opcional; ADM-009 ✅ relation-aware)

---

## 🔴 Bloqueante para v2.0.0 — implementar antes de publicar

*(vacío — ADM-005 ya quedó completado y documentado en `TASKS_ARCHIVE.md`)*

---

## 🟡 Próximo (ordenado por prioridad)

*(vacío)*

---

## ⚪ Backlog

### [ADM-DEP-002] lint-staged 16 → 17 (espera Node 22 baseline)

**Contexto:** `lint-staged@17.x` requiere Node `>=22.22.1`. El admin pinea `engines.node` en `^20.19.0 || ^22.13.0 || >=24` y `lint-staged@16.4.0` (última v16) ya da todo lo que necesitamos (no hay features nuevas relevantes en v17).

**Señal de activación:** Cuando el baseline de Node del repo (CI, prod, dev) suba a 22 LTS por otra razón (p. ej. al alinear con otros repos del kit o por requerimiento del hosting).

**Acción:** `npm install --save-dev lint-staged@^17` · bump `engines.node` a `>=22.22.1` · `npm audit` · verificar que el hook `pre-commit` sigue corriendo `eslint --fix` sobre `public/assets/js/**/*.js`.

---

## ✅ Completadas

### [ADM-010] CSV export/import scaffold opcional para módulos admin (2026-06-10)

- `bin/make-module.sh` now supports `--csv` so generated admin modules can include export/import hooks without hand-writing the boilerplate each time.
- The scaffold emits the export route/button tied to the current index filters, plus import preview and row-level validation feedback for rejected rows.
- Documentation was updated in `README.md` and `CLAUDE.md` to keep the scope clear: CSV is an optional extension for repetitive admin ingestion, not a replacement for domain-specific workflows.

### [ADM-009] Scaffolding relation-aware end-to-end (2026-06-10)

- `bin/make-module.sh` now supports basic `relation` fields end-to-end: relation-aware form components, option loaders in the generated controller/service, index filters, and relation lookups in `show` views.
- The scaffold accepts an explicit relation path override with `categories=/catalog/categories` when the related endpoint does not follow the default derived path.
- Coverage was extended in `tests/unit/Support/ScaffoldingScriptsTest.php` to validate the generated controller, service, views, and filter contract for relation fields.

### [FAQ-010] Reemplazar texto plano de tablas vacías con empty_state (2026-06-04)

- Replaced plain `<p>` empty-state text with the existing `components/display/empty_state` component in all legacy index views: `users`, `api_keys`, `audit`, `iam/applications`, `iam/roles`, `iam/permissions`, `files/trash`, `files/partials/list_section`, `admin/universal`.
- Each view now uses the appropriate Lucide icon. Modules with a create action (users, api_keys, roles, permissions) pass `actionUrl` and `actionLabel` so the component renders a primary button.
- Read-only views (audit, applications) and the files list (drag-and-drop upload) receive context-appropriate icons and descriptions without an action button.
- `make-module.sh` was already generating the component correctly for new modules (KICK-018); this closes the gap for all pre-existing starter modules.

### [ADM-008] Diccionario de iconos y logs de scaffolding (2026-05-25)

- **Iconos**: Añadidos términos de negocio comunes (`cart`, `warehouse`, `box`, `truck`, `wallet`, `bank`, `settings`, `mail`, `bell`, `calendar`, `map-pin`, `tag`, `ticket`, `store`) al mapa de Lucide en `ui_helper.php`. Esto evita que módulos generados para eCommerce o logística lancen `InvalidArgumentException`.
- **Logs**: `bin/make-module.sh` ahora emite bloques de código coloreados y listos para copiar al fallar la auto-inyección de servicios en `Config/Services.php`, guiando mejor al desarrollador en los pasos manuales restantes.

### [ADM-007] Hooks mínimos de extensión para módulos admin (2026-05-24)

- `bin/make-module.sh` ahora acepta `--action=<verb>` repetible para scaffoldear acciones POST por item sin rehacer a mano service/controller/routes/views/lang. Cubre el caso común `approve/publish/archive/restore`.
- Cada acción genera: método extra en `*ApiServiceInterface`, implementación en `*ApiService`, action en controller, route `POST .../{id}/{verb}`, botón en `show.php` y keys base de i18n.
- Cobertura añadida en `tests/unit/Support/ScaffoldingScriptsTest.php`: scaffold end-to-end con `--action=approve --action=publish` + rechazo de `--action` inválido.

### [ADM-006] Clarificar alcance de `make-module.sh` (2026-05-24)

- `CLAUDE.md`, `README.md` y el help/banner de `bin/make-module.sh` ahora dejan explícito que el scaffold genera un **CRUD shell**: controller, service, requests, routes, views y tests base alineados con el backend elegido (`hub` o `domain`).
- Se documentó también lo que **no** hace: no modela aggregates complejos, nested resources, custom actions, relation arrays, option loaders, file pickers ni formularios ricos de dominio.
- El banner final del script ahora empuja al dev a completar manualmente la integración real del módulo en vez de sugerir implícitamente que el scaffold ya dejó una UI de producción.

...
### 🏗️ Contratos de arquitectura

- **Módulos en `app/Modules/{Nombre}/`:** Controllers + Services + Requests + Language + Config/Routes.php. Views en `app/Views/{nombre}/`.
- **Services extienden `BaseApiService`:** toda comunicación con la API pasa por `ApiClient` (hub) o `DomainApiClient` (domain apps). Nunca llamadas HTTP directas.
- **Dos clientes HTTP:** `apiClient` (factory `Services::apiClient()`, config `Config\ApiClient`, target hub `:8180`) y `domainApiClient` (factory `Services::domainApiClient()`, config `Config\DomainApiClient`, target domain `:8190`). Scaffolding selector: `bash bin/make-module.sh ... --service=hub|domain` (default `hub`).
- **`make-module.sh` genera un shell, no un aggregate listo para producción:** úsalo para establecer estructura, wiring y tests base. Si el módulo necesita acciones custom, nested resources, dropdowns dependientes, relation arrays o media/file-picker flows, la extensión manual sigue siendo obligatoria.
- **Hook mínimo soportado:** `make-module.sh --action=<verb>` añade wiring completo para acciones POST por item. Úsalo para workflows simples sobre un recurso ya existente; no reemplaza módulos aggregate con read models, loaders auxiliares o nested resources.
- **Tokens solo en sesión PHP:** nunca localStorage, nunca en JS. `ApiClient` inyecta el header automáticamente.
- **CSRF activo por defecto:** no desactivar. Usar `csrf_field()` en todos los forms.
- **Permisos en UI:** usar `has_permission(string $code)` (no `has_admin_access()` — legacy removido).
- **CSS:** Tailwind v4 (no hay `tailwind.config.js` — toda la config vive en `src/css/app.css` vía `@import "tailwindcss"`, `@theme`, `@source` y `@source inline()`). Brand colors en `@theme` y overridables en runtime desde `app/Views/layouts/partials/head.php`. Watcher: `npm run dev:css`. Build: `npm run build:css`. Binario `tailwindcss` lo provee `@tailwindcss/cli`.
- **Módulo nuevo:** usar `bash bin/make-module.sh {Resource} {Module} /api/v1/path`. Registrar el service en `app/Config/Services.php` manualmente.
- **Tests:** `vendor/bin/phpunit tests/unit` + `vendor/bin/phpunit tests/feature`. Correr antes de hacer merge.

### 🚧 Technical Debt (Scaffolding)
- [x] **Service Auto-Injection**: Update make-module.sh to use AST-based editing for automatic registration of generated services in Config/Services.php. ✅ 2026-05-28
- [x] **Icon Dictionary**: Add missing business icons (cart, warehouse, etc.) to ui_helper.php to avoid InvalidArgumentException during UI generation. ✅ 2026-05-25
