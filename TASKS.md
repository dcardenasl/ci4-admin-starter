# TASKS — ci4-admin-starter

> Fuente de verdad para trabajo en este repo.
> Gestionado desde Cowork/VentureOS. Ejecutado desde Claude Code.
> Última actualización: 2026-05-06

---

## 🔴 En progreso

### [B5.2] GET /health endpoint
**Milestone:** Enterprise hardening B5 — Críticos transversales
**Ver contexto completo en:** `../TASKS.md`

**Objetivo:** Endpoint `GET /health` en módulo `System` para monitoreo y readiness checks.

**Criterios de aceptación:**
- [ ] `GET /health` devuelve 200 con payload `{ status: "ok", version: "...", timestamp: "..." }`
- [ ] Devuelve 503 si la app no está lista (DB unreachable, etc.)
- [ ] 2 feature tests (healthy + unhealthy)
- [ ] No requiere autenticación

---

## 🟡 Próximo (ordenado por prioridad)

### [B5.3] Dockerfile multi-stage + USER www-data + .dockerignore
**Milestone:** Enterprise hardening B5

### [B8.1] Asset cache-busting (`asset_url()` con `ASSET_VERSION`)
**Milestone:** Enterprise hardening B8 — Frontend hardening
**Bloqueado por:** B5 completo

### [B8.2] ESLint en CI + pre-commit (lint-staged)
**Bloqueado por:** B5 completo

### [B8.3] i18n parity check en CI admin
**Bloqueado por:** B5 completo

### [B8.4] ARIA en `form_helper.php` + focus trap en modales
**Bloqueado por:** B5 completo

### [B8.5] Logout: revoke token con retry + log de auditoría
**Bloqueado por:** B5 completo

---

## ⚪ Backlog

- [B9.1] API mocking en admin tests (Guzzle MockHandler / double sobre `ApiClientInterface`)
- [B9.3] Cobertura para `PermissionFilter`, `badge_helper`, `ApiClient` 4xx/5xx
- [B9.4] Coverage gates ≥70% en CI
- [B10.2] Structured JSON logging (portar `MonologHandler`)
- [B10.3] Sesión multi-server (Redis default) + lock distribuido para refresh
- [B10.4] Maintenance mode (`MAINTENANCE_MODE=true` → 503)
- [B10.5] Release workflow (.github/workflows/release.yml)
- [B11.4] Limpiar dead code (`TableA11y.php`, `TableColumns.php`); doc `npm run build:vendor`
- [B11.6] `CONTRIBUTING.md` + `ISSUE_TEMPLATE` + `PR_TEMPLATE`
- [ADM-001] Módulo "Apps" — listar domain apps registradas (activar cuando SEÑAL-004 se dispare)
- [ADM-002] Mejorar `bin/make-module.sh` — validación de rutas duplicadas + tests básicos
- [ADM-003] Docker out-of-the-box coordinar con ci4-api-starter (ver B5.3)
- [ADM-004] CI/CD pipeline de ejemplo — GitHub Actions (build CSS + PHPStan + tests)

---

## ✅ Completadas recientes

- **[B5.1] SecurityHeadersFilter** (2026-05-06) — `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `HSTS`. 6 unit tests verdes.
- **[ADM-000] Alineación CI4 ^4.5 + refactor RBAC** (2026-05-03) — ci4-admin-starter v1.1.0. Módulos IAM actualizados a `user_roles`. Reglas de modificación de usuarios: email inmutable salvo superadmin, Profile sin gate `users.write`.

---

## 🏗️ Contratos de arquitectura

> Restricciones que se deben respetar siempre al tocar este repo. No negociables.

- **Módulos en `app/Modules/{Nombre}/`**: Controllers + Services + Requests + Language + Config/Routes.php. Views en `app/Views/{nombre}/`.
- **Services extienden `BaseApiService`**: toda comunicación con la API pasa por `ApiClient`. Nunca llamadas HTTP directas.
- **Tokens solo en sesión PHP**: nunca localStorage, nunca en JS. `ApiClient` inyecta el header automáticamente.
- **CSRF activo por defecto**: no desactivar. Usar `csrf_field()` en todos los forms.
- **Permisos en UI**: usar `has_permission(string $code)` (no `has_admin_access()` — legacy removido).
- **CSS**: correr `npm run dev:css` (Tailwind watcher) durante desarrollo. Build final: `npm run build:css`.
- **Módulo nuevo**: usar `bash bin/make-module.sh {Resource} {Module} /api/v1/path`. Registrar el service en `app/Config/Services.php` manualmente (el script imprime el snippet).
- **Tests**: `vendor/bin/phpunit tests/unit` (rápido) + `vendor/bin/phpunit tests/feature` (HTTP). Correr antes de hacer merge.
