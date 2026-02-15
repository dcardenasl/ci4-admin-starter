# CI4 Admin Starter

Aplicacion web (frontend server-rendered) basada en CodeIgniter 4 para consumir [`ci4-api-starter`](https://github.com/dcardenasl/ci4-api-starter).

## Objetivo

Este repositorio sirve como base para un panel administrativo web que consume un API CI4 externo (autenticacion, perfil, archivos, usuarios, auditoria y metricas).

Arquitectura objetivo:

`Browser -> CI4 Admin Starter (este repo) -> ci4-api-starter`

## Estado actual del repositorio

- Base de proyecto CI4 inicial (scaffold).
- Ruta activa por defecto: `/` (vista `welcome_message`).
- Aun no estan implementados los modulos funcionales del panel (auth/dashboard/perfil/archivos/admin).
- El plan de implementacion vive en `docs/plan/PLAN-CI4-CLIENT.md`.

## Requisitos

- PHP `^8.1`
- Composer 2.x
- Extensiones PHP minimas:
  - `intl`
  - `mbstring`
- Extensiones recomendadas segun uso:
  - `curl` (cliente HTTP hacia API)
  - `json` (normalmente habilitada)

## Instalacion

```bash
composer install
```

## Configuracion local

1. Crear archivo de entorno:

```bash
cp env .env
```

2. Ajustar valores clave en `.env`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8081/'
```

3. Si vas a conectar contra [`ci4-api-starter`](https://github.com/dcardenasl/ci4-api-starter), define tambien su URL base en el punto donde implementes el cliente API (segun el plan de `docs/plan/PLAN-CI4-CLIENT.md`).

## Ejecutar en desarrollo

```bash
php spark serve --port 8081
```

App disponible en: `http://localhost:8081`

## Pruebas

Ejecutar suite:

```bash
vendor/bin/phpunit
```

Con cobertura (opcional):

```bash
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/
```

## Estructura relevante

- `app/` codigo de aplicacion (controladores, config, vistas, filtros).
- `public/` front controller y assets publicos.
- `writable/` logs, cache, sesiones y archivos temporales.
- `tests/` pruebas unitarias e infraestructura de test.
- `docs/plan/PLAN-CI4-CLIENT.md` roadmap funcional del admin starter.

## Roadmap funcional

El alcance definido incluye:

1. Infraestructura core (ApiClient, filtros auth/admin, helpers UI).
2. Modulo de autenticacion.
3. Dashboard inicial.
4. Perfil de usuario.
5. Gestion de archivos.
6. Modulos admin (usuarios, auditoria, metricas).

Detalle completo: `docs/plan/PLAN-CI4-CLIENT.md`.

## Notas de seguridad y despliegue

- El `DocumentRoot` del servidor debe apuntar a `public/`, nunca a la raiz del repositorio.
- No subir secretos (`.env`, tokens, credenciales).
- `writable/` debe ser escribible por el usuario del servidor web.

## Referencias

- CodeIgniter 4 User Guide: <https://codeigniter.com/user_guide/>
- CI4 API Starter: <https://github.com/dcardenasl/ci4-api-starter>
- Testing en CI4: `tests/README.md`
