# TASKS_ARCHIVE — ci4-admin-starter

> Historial de tareas completadas.
> Última actualización: 2026-05-15

---

## ✅ CI/CD pipeline (2026-05-15)

| ID | Descripción | Estado |
|---|---|---|
| ADM-004 | CI/CD pipeline GitHub Actions — `.github/workflows/ci.yml` cubre matriz PHP 8.2/8.3 con composer validate, install, npm ci, build CSS, PHPStan, CS-Fixer (dry-run), composer audit, lint JS (`npm run lint:all`), i18n parity, PHPUnit + coverage gate (≥70% soft-fail en 8.2). Cerrada documentalmente — el workflow ya cumplía el alcance de la tarea desde su última actualización. | ✅ |

---

## ✅ Multi-backend admin + scaffolding hardening (2026-05-15)

| ID | Descripción | Estado |
|---|---|---|
| ADM-005 | DomainApiClient — segundo cliente HTTP que apunta al puerto de un ci4-domain-starter en paralelo al hub. `Config\DomainApiClient` espejo de `ApiClient` leyendo `domainApiClient.*` / `DOMAIN_API_*`. `App\Libraries\DomainApiClient extends ApiClient implements DomainApiClientInterface`. Factory `Services::domainApiClient()` registrado. `bin/make-module.sh --service=hub\|domain` (default `hub`) cambia la factory call; `bin/register-service.php --client=hub\|domain` propaga. `.env` + `.env.example` documentan el bloque opcional. CLAUDE.md + TASKS.md (sección de contratos) explican cuándo usar cada cliente. Tests: `tests/unit/Libraries/DomainApiClientTest.php` (10 tests) + 3 casos nuevos en `ScaffoldingScriptsTest`. PHPStan L8 limpio. 260 unit tests verdes (sin regresiones). | ✅ |
| ADM-002 | `bin/make-module.sh` rechaza colisiones cross-módulo del `ROUTE_NAME` con exit 6 — escanea `app/Modules/*/Config/Routes.php` + `app/Config/Routes.php` antes de generar. Test `testMakeModuleRejectsCrossModuleRouteNameCollision` ejercita el path. Nota: la propuesta original de bats se omitió porque el sandbox PHPUnit del `ScaffoldingScriptsTest` ya invoca scripts shell reales y cubre el caso de extremo a extremo — agregar bats sumaba dependencia (`brew install bats-core`) sin cobertura adicional. | ✅ |
| ADM-001 | Módulo Apps (read-only) — `App\Modules\Iam\Controllers\ApplicationController` con `index/data/show`, reusa `ApplicationApiService` que ya existía. Vistas en `app/Views/iam/applications/` (index server-driven, show con detalle). Rutas bajo `admin/iam/applications` gated por filtro `superadmin`. Sidebar entry "Applications" bajo Identity & Access (ícono `layers`). Strings en/es completas. Feature tests en `tests/feature/ApplicationFlowTest.php` (6 tests, 17 assertions). | ✅ |
| ADM-003 | Docker red unificada — ambos compose (admin + api-starter) usan red externa `ci4-platform` (kebab-case, bridge). Setup: `docker network create ci4-platform` una vez en el host. Admin gana `.env.docker.example` apuntando a `http://ci4-api-app:80`. `docker/README.md` documenta el flujo end-to-end + smoke test + extensión a domain-starter. | ✅ |

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
