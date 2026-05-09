# TASKS — ci4-admin-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-05-07

---

## 🔴 En progreso

*(vacío)*

---

## 🟡 Próximo (ordenado por prioridad)

- **[ADM-001]** Módulo "Apps" — listar domain apps registradas. **Activar cuando:** SEÑAL-004 en `../TASKS.md` se dispare (>1 domain app en producción). Acción: módulo en `app/Modules/Apps/` que consuma `GET /api/v1/iam/applications`.
- **[ADM-002]** Mejorar `bin/make-module.sh` — validación de rutas duplicadas + tests básicos.
- **[ADM-004]** CI/CD pipeline de ejemplo — GitHub Actions (build CSS + PHPStan + tests).

---

## ⚪ Backlog

- [ADM-003] Docker out-of-the-box — coordinar con ci4-api-starter compose setup.

---

## 🏗️ Contratos de arquitectura

- **Módulos en `app/Modules/{Nombre}/`:** Controllers + Services + Requests + Language + Config/Routes.php. Views en `app/Views/{nombre}/`.
- **Services extienden `BaseApiService`:** toda comunicación con la API pasa por `ApiClient`. Nunca llamadas HTTP directas.
- **Tokens solo en sesión PHP:** nunca localStorage, nunca en JS. `ApiClient` inyecta el header automáticamente.
- **CSRF activo por defecto:** no desactivar. Usar `csrf_field()` en todos los forms.
- **Permisos en UI:** usar `has_permission(string $code)` (no `has_admin_access()` — legacy removido).
- **CSS:** correr `npm run dev:css` (Tailwind watcher) durante desarrollo. Build final: `npm run build:css`.
- **Módulo nuevo:** usar `bash bin/make-module.sh {Resource} {Module} /api/v1/path`. Registrar el service en `app/Config/Services.php` manualmente.
- **Tests:** `vendor/bin/phpunit tests/unit` + `vendor/bin/phpunit tests/feature`. Correr antes de hacer merge.
