# TASKS — ci4-admin-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-05-23 (ADM-005 completado · ADM-DEP-001 Tailwind v4 migration ✅ · ADM-DEP-002 lint-staged v17 en backlog)

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

### [ADM-DEP-001] Migración Tailwind v3 → v4 (2026-05-17)

- `tailwindcss` 3.4.19 → 4.3.0; añadido `@tailwindcss/cli` 4.3.0 (la CLI ya no viene en el paquete principal en v4).
- `tailwind.config.js` eliminado. Toda la configuración vive ahora en `src/css/app.css`:
  - `@import "tailwindcss"` reemplaza `@tailwind base/components/utilities`.
  - `@source "../../app/Views"`, `@source "../../app/Helpers"`, `@source "../../public/assets/js"` para detección explícita de paths (auto-detección de v4 funciona pero lo dejamos explícito).
  - `@theme { --color-brand-50…900: rgb(...); --font-sans; --font-mono }` define la paleta brand y las fuentes default.
  - `@source inline("…")` reemplaza el array `safelist` del config JS (no se soporta vía `@config` en v4): cubre los gradientes, `odd:/even:/hover:` de tabla, `py-3.5` y `text-[11px]`.
- `app/Views/layouts/partials/head.php`: las CSS vars `--color-brand-*` ahora se setean con `rgb(R G B)` completo en lugar del triplet RGB suelto. Razón: v4 elimina la indirección `<alpha-value>` y `bg-brand-X` genera `background-color: var(--color-brand-X)` directo, así que la variable debe contener un color válido. El override en runtime sigue funcionando porque el `<style>` inline gana por cascada sobre el linked `app.css`.
- Scripts `dev:css` / `build:css` sin cambios (binario `tailwindcss` ahora lo provee `@tailwindcss/cli`).
- Build: 30 KB → 42 KB minified (esperado: v4 ships más defaults + safelist).
- Verificado: `npm run build:css` ✅ · `npm run lint:all` ✅ · `php spark serve` + `curl /login` ✅ (HTML 200, CSS 200 text/css, vars brand renderizadas) · `ErrorPagesTest` ✅ (3 tests, renderiza head.php).

---

## 🏗️ Contratos de arquitectura

- **Módulos en `app/Modules/{Nombre}/`:** Controllers + Services + Requests + Language + Config/Routes.php. Views en `app/Views/{nombre}/`.
- **Services extienden `BaseApiService`:** toda comunicación con la API pasa por `ApiClient` (hub) o `DomainApiClient` (domain apps). Nunca llamadas HTTP directas.
- **Dos clientes HTTP:** `apiClient` (factory `Services::apiClient()`, config `Config\ApiClient`, target hub `:8080`) y `domainApiClient` (factory `Services::domainApiClient()`, config `Config\DomainApiClient`, target domain `:8090`). Scaffolding selector: `bash bin/make-module.sh ... --service=hub|domain` (default `hub`).
- **Tokens solo en sesión PHP:** nunca localStorage, nunca en JS. `ApiClient` inyecta el header automáticamente.
- **CSRF activo por defecto:** no desactivar. Usar `csrf_field()` en todos los forms.
- **Permisos en UI:** usar `has_permission(string $code)` (no `has_admin_access()` — legacy removido).
- **CSS:** Tailwind v4 (no hay `tailwind.config.js` — toda la config vive en `src/css/app.css` vía `@import "tailwindcss"`, `@theme`, `@source` y `@source inline()`). Brand colors en `@theme` y overridables en runtime desde `app/Views/layouts/partials/head.php`. Watcher: `npm run dev:css`. Build: `npm run build:css`. Binario `tailwindcss` lo provee `@tailwindcss/cli`.
- **Módulo nuevo:** usar `bash bin/make-module.sh {Resource} {Module} /api/v1/path`. Registrar el service en `app/Config/Services.php` manualmente.
- **Tests:** `vendor/bin/phpunit tests/unit` + `vendor/bin/phpunit tests/feature`. Correr antes de hacer merge.
