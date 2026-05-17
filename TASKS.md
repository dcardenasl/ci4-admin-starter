# TASKS — ci4-admin-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-05-15 (ADM-004 cerrada — workflow ya cumple el alcance)

---

## 🔴 Bloqueante para v2.0.0 — implementar antes de publicar

### [ADM-005] DomainApiClient — Soporte multi-servicio en el admin

**Contexto:** El admin-starter tiene un único `apiClient` con un solo `baseUrl`. Para proyectos que combinan hub + domain-starter (ej. SubscriptionKit: hub para auth/users/IAM, subscription-domain para projects/subscribers/campaigns/deliveries), el admin necesita poder hablar con dos backends desde el mismo panel.

**Por qué es requisito de v2.0.0:** El SubscriptionKit es el primer proyecto real sobre el kit v2. Necesita esta funcionalidad para wiring el admin con la domain app. Sin esto, los módulos de dominio no se pueden agregar al admin.

| ID | Descripción | Estado |
|---|---|---|
| ADM-005a | Crear `Config\DomainApiClient` — mirrors `Config\ApiClient`, reads `domainApiClient.baseUrl` / `DOMAIN_API_BASE_URL` | ⏳ |
| ADM-005b | Crear `App\Libraries\DomainApiClient` + `DomainApiClientInterface` — implementación idéntica a `ApiClient` pero bound al config de dominio | ⏳ |
| ADM-005c | Registrar `Services::domainApiClient()` en `Config\Services.php` | ⏳ |
| ADM-005d | Actualizar `bin/make-module.sh` — agregar flag `--service=domain` que genera el módulo usando `domainApiClient` en lugar de `apiClient` | ⏳ |
| ADM-005e | Actualizar `bin/register-service.php` — soportar tipo de cliente al inyectar el service factory | ⏳ |
| ADM-005f | Actualizar `CLAUDE.md` + contratos de arquitectura en este TASKS.md: cuándo usar `apiClient` (hub) vs `domainApiClient` (domain apps) | ⏳ |
| ADM-005g | Tests + PHPStan L8 limpio. Módulos de hub existentes no se rompen | ⏳ |

**Criterio de completitud:**
- `DOMAIN_API_BASE_URL` en `.env` configura el segundo cliente sin cambios de código
- `bash bin/make-module.sh Project Subscription /projects --service=domain` genera módulo correcto
- Módulos hub existentes (Users, IAM, Audit, etc.) siguen usando `apiClient` sin cambios
- PHPStan Level 8 limpio · todos los tests verdes

---

## 🟡 Próximo (ordenado por prioridad)

*(vacío)*

---

## ⚪ Backlog

*(vacío)*

---

## 🏗️ Contratos de arquitectura

- **Módulos en `app/Modules/{Nombre}/`:** Controllers + Services + Requests + Language + Config/Routes.php. Views en `app/Views/{nombre}/`.
- **Services extienden `BaseApiService`:** toda comunicación con la API pasa por `ApiClient` (hub) o `DomainApiClient` (domain apps). Nunca llamadas HTTP directas.
- **Dos clientes HTTP:** `apiClient` (factory `Services::apiClient()`, config `Config\ApiClient`, target hub `:8080`) y `domainApiClient` (factory `Services::domainApiClient()`, config `Config\DomainApiClient`, target domain `:8090`). Scaffolding selector: `bash bin/make-module.sh ... --service=hub|domain` (default `hub`).
- **Tokens solo en sesión PHP:** nunca localStorage, nunca en JS. `ApiClient` inyecta el header automáticamente.
- **CSRF activo por defecto:** no desactivar. Usar `csrf_field()` en todos los forms.
- **Permisos en UI:** usar `has_permission(string $code)` (no `has_admin_access()` — legacy removido).
- **CSS:** correr `npm run dev:css` (Tailwind watcher) durante desarrollo. Build final: `npm run build:css`.
- **Módulo nuevo:** usar `bash bin/make-module.sh {Resource} {Module} /api/v1/path`. Registrar el service en `app/Config/Services.php` manualmente.
- **Tests:** `vendor/bin/phpunit tests/unit` + `vendor/bin/phpunit tests/feature`. Correr antes de hacer merge.
