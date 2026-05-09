# TASKS_ARCHIVE — ci4-admin-starter

> Historial de tareas completadas.
> Última actualización: 2026-05-07

---

## ✅ Enterprise hardening (Milestone B5–B11, 2026-05-07)

| ID | Descripción | Estado |
|---|---|---|
| B5.1 | `SecurityHeadersFilter`: X-Frame-Options, X-CTO, Referrer-Policy, Permissions-Policy, HSTS. 6 unit tests. | ✅ |
| B5.2 | `GET /health` endpoint en módulo `System`. 200 OK con status/version/timestamp, 503 si DB unreachable. 2 feature tests. | ✅ |
| B5.3 | Dockerfile multi-stage + `USER www-data` + `.dockerignore`. | ✅ |
| B8.1 | `asset_url()` helper con `ASSET_VERSION` env, fallback mtime. Doc en `docs/DEPLOYMENT.md`. | ✅ |
| B8.2 | CI `lint:all` (broader scope), `lint-staged` widened `public/assets/js/**/*.js`. | ✅ |
| B8.3 | `scripts/i18n-check.php` + `composer i18n-check` + step en CI workflow. | ✅ |
| B8.4 | `field_aria_attrs()`, `field_error_id()`, `render_field_error` con id+role="alert". | ✅ |
| B8.5 | `revokeTokenWithRetry()` — 2 intentos, 250ms backoff, log warning si fallan ambos. | ✅ |
| B9.1 | Verificación: admin tests ya usan `Services::injectMock` consistentemente (audit falso positivo). | ✅ |
| B9.3 | `PermissionFilterTest` (5 tests). `BadgeHelperTest`/`ApiClientTest` ya existían. | ✅ |
| B9.4 | `scripts/check-coverage.php` (parsea clover XML, exit 1 si <70%). Composer alias `coverage:check`. CI step soft-fail. | ✅ |
| B10.1 | `CorrelationIdFilter` + `RequestIdHolder` + propagación en ApiClient. | ✅ |
| B10.2 | `JsonFileHandler` nativo CI4 (sin dep Monolog), self-disable cuando `LOG_FORMAT!=json`. | ✅ |
| B10.3 | `Config\Session` resuelve driver desde `SESSION_DRIVER` env. Doc Redis en `docs/DEPLOYMENT.md`. | ✅ |
| B10.4 | `MaintenanceFilter` (alias `maintenance`), bypass probes, `Retry-After` header, JSON/HTML según Accept. | ✅ |
| B11.4 | `TableA11y`/`TableColumns` resultaron usados (audit falso positivo). Sección "Frontend build" en `docs/DEPLOYMENT.md`. | ✅ |
| B11.6 | `bug_report.md` + `feature_request.md` + `PULL_REQUEST_TEMPLATE.md` — admin ya los tenía, confirmado completo. | ✅ |

---

## ✅ Base (2026-05-03)

| ID | Descripción | Fecha |
|---|---|---|
| ADM-000 | ci4-admin-starter v1.1.0: alineación CI4 ^4.5, refactor RBAC a `user_roles`, módulos IAM actualizados. Email inmutable salvo superadmin. Profile sin gate `users.write`. | 2026-05-03 |

---

## ✅ DX + release (2026-05-08)

| ID | Descripción | Estado |
|---|---|---|
| ADM-005 | GitHub release workflow — extrae sección del CHANGELOG correspondiente al tag y crea GitHub Release automáticamente. | ✅ |
| ADM-006 | Diagramas Mermaid en README para reemplazar diagrama ASCII de arquitectura. | ✅ |

---

*TASKS_ARCHIVE · ci4-admin-starter · 2026-05-08*
