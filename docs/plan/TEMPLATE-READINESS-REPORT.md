# Template Readiness Report — CI4 Admin Starter

**Fecha:** 2026-04-13
**Repositorio:** `ci4-admin-starter`
**Objetivo evaluado:** determinar si el estado actual permite publicarlo como template empresarial reutilizable
**Decisión:** `No-Go`
**Estado alternativo:** `Go condicionado` tras una remediación corta

## Resumen Ejecutivo

El proyecto tiene una base técnica sólida y ya resuelve buena parte de lo esperable en un starter serio: arquitectura por capas, validación desacoplada, cliente API con refresh de token, i18n real, suite de tests útil y pipeline CI con análisis estático, lint y build.

No está listo todavía para publicarse como template empresarial generalista. El principal problema no es la arquitectura, sino la terminación del producto: la experiencia de arranque local no es limpia, el frontend de producción sigue acoplado a CDNs/runtime, faltan parámetros operativos clave para despliegue enterprise y la documentación de auditoría vigente no coincide con el estado real del código.

## Veredicto

### No-Go hoy

No se recomienda publicarlo hoy como template empresarial de referencia para múltiples equipos o terceros.

### Go condicionado

Sí puede quedar listo con una remediación breve si se cierran estos cuatro frentes:

1. frontend determinístico sin dependencia operativa del Play CDN de Tailwind
2. experiencia local limpia para `composer test`
3. documentación/configuración explícita para despliegue enterprise
4. alineación entre documentación de auditoría y estado real del repositorio

## Validación Realizada

Se verificó el estado actual del repositorio y se ejecutaron checks locales.

### Comandos ejecutados

```bash
composer validate --strict
composer quality
npm run lint:js
composer test
npm run build:css
```

### Resultado

- `composer validate --strict`: OK
- `composer quality`: OK
- `npm run lint:js`: OK
- `npm run build:css`: OK
- `composer test`: la suite funcional pasó, pero el comando terminó con error por warning de cobertura ausente

### Observación importante

La suite PHPUnit ejecutó `117` tests correctamente en esta máquina, pero `composer test` devolvió código de salida no cero porque `phpunit.xml.dist` tiene `failOnWarning="true"` y no había driver de cobertura disponible.

## Fortalezas Confirmadas

- Arquitectura por capas consistente: controladores delgados, servicios por dominio y cliente API centralizado.
- Validación desacoplada mediante `FormRequest`.
- Protección de sesión corregida en login con regeneración de sesión.
- Saneamiento del nombre de archivo en descarga ya implementado.
- Configuración de seguridad razonable para cookies, CSRF y CSP base.
- CI ya incluye PHPStan, PHP CS Fixer, ESLint y build de CSS.
- Cobertura funcional suficiente para un starter en crecimiento.
- Documentación amplia de compatibilidad con backend y despliegue.

## Hallazgos que Bloquean el Release

### 1. Frontend de producción todavía atado a runtime/CDN

**Impacto:** alto

Aunque existe pipeline de build para Tailwind, la app sigue cargando `https://cdn.tailwindcss.com` y mantiene comentado el CSS compilado:

- [app/Views/layouts/partials/head.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Views/layouts/partials/head.php:7)
- [app/Views/layouts/partials/head.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Views/layouts/partials/head.php:9)

Esto afecta:

- reproducibilidad
- performance
- estrategia CSP
- compliance en entornos corporativos
- independencia de terceros en producción

El pipeline ya compila correctamente `public/assets/css/app.css`, por lo que el gap es de integración y decisión final, no de capacidad técnica.

### 2. `composer test` no es un comando confiable de arranque local

**Impacto:** alto

El script:

- [composer.json](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/composer.json:63)

apunta a `phpunit`, mientras la configuración:

- [phpunit.xml.dist](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/phpunit.xml.dist:11)
- [phpunit.xml.dist](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/phpunit.xml.dist:13)

hace fallar el proceso si no existe driver de coverage, aun cuando los tests pasen.

Para un template empresarial esto es un problema de DX: un equipo nuevo puede interpretar falsamente que el template está roto.

### 3. Configuración enterprise incompleta para despliegue repetible

**Impacto:** alto

Hay piezas correctas, pero la plantilla no deja completamente cerrado el escenario enterprise real:

- `WEBAPP_BASE_URL` se usa en [app/Controllers/BaseWebController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/BaseWebController.php:128) y no está documentado en [.env.example](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/.env.example:1)
- `proxyIPs` sigue vacío en [app/Config/App.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/App.php:183)
- la sesión usa filesystem por defecto en [app/Config/Session.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/Session.php:24)

Esto no invalida el proyecto, pero sí obliga a conocimiento tácito del autor para desplegarlo bien detrás de reverse proxy o con múltiples instancias.

### 4. Cambio de estado por `GET` en el selector de idioma

**Impacto:** medio

La ruta:

- [app/Config/Routes.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/Routes.php:8)

termina escribiendo sesión en:

- [app/Controllers/LanguageController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/LanguageController.php:10)

No es un fallo crítico, pero es una señal débil para un template enterprise porque introduce mutación de estado mediante `GET`.

### 5. La auditoría histórica del repo ya no es confiable

**Impacto:** medio-alto

El documento:

- [docs/plan/ENTERPRISE-AUDIT.md](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/docs/plan/ENTERPRISE-AUDIT.md:37)

marca como críticos varios problemas ya corregidos en el código actual, por ejemplo:

- regeneración de sesión en login: [app/Controllers/AuthController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/AuthController.php:223)
- saneamiento de filename: [app/Controllers/FileController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/FileController.php:141)
- comentario y excepción CSRF en Google login: [app/Config/Filters.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/Filters.php:72)
- CSP sin `unsafe-inline`: [app/Config/ContentSecurityPolicy.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/ContentSecurityPolicy.php:187)
- análisis estático en CI: [ci.yml](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/.github/workflows/ci.yml:44)

Eso reduce la confianza documental del template.

## Riesgos No Bloqueantes

### Coverage sin umbral real

La cobertura se genera, pero no hay umbral exigido en CI:

- [phpunit.xml.dist](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/phpunit.xml.dist:18)
- [ci.yml](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/.github/workflows/ci.yml:53)

### Dependencia externa de librerías frontend

Además del Play CDN de Tailwind, la UI todavía depende de CDN para Alpine y Lucide:

- [app/Views/layouts/partials/head.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Views/layouts/partials/head.php:10)
- [app/Views/layouts/partials/head.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Views/layouts/partials/head.php:11)

### Parámetros operativos ausentes en `.env.example`

Faltan variables útiles para el escenario real de despliegue, por ejemplo `WEBAPP_BASE_URL` y la ruta de reportes CSP.

## Hallazgos Positivos Relevantes

Estos puntos sí apoyan una futura aprobación:

- `persistAuthSession()` ya regenera sesión:
  - [app/Controllers/AuthController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/AuthController.php:223)
- el filename de descarga ya se sanea:
  - [app/Controllers/FileController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/FileController.php:141)
- Google login valida `iss`, `aud` y `exp`:
  - [app/Controllers/AuthController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/AuthController.php:237)
  - [app/Controllers/AuthController.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Controllers/AuthController.php:262)
- `regenerateDestroy` está en `true`:
  - [app/Config/Session.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/Session.php:92)
- cookies seguras por producción:
  - [app/Config/Cookie.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/Cookie.php:57)
- HTTPS y CSP activables automáticamente en producción:
  - [app/Config/App.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/App.php:160)
  - [app/Config/App.php](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/app/Config/App.php:201)
- CI ya corre análisis estático y lint:
  - [ci.yml](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/.github/workflows/ci.yml:44)

## Plan de Remediación de 1 Semana

### Día 1

- activar definitivamente `public/assets/css/app.css`
- eliminar Tailwind Play CDN
- decidir si Alpine y Lucide quedan vendorizados o permitidos por política

### Día 2

- separar `composer test` de `composer test:coverage`
- dejar una ruta local sin dependencia de Xdebug/PCOV
- documentar el flujo de validación mínima para nuevos equipos

### Día 3

- extender `.env.example` con `WEBAPP_BASE_URL`
- documentar reverse proxy, `proxyIPs` y estrategia de sesiones distribuidas
- definir recomendación oficial de storage de sesión para producción

### Día 4

- revisar cambios de estado por `GET`
- decidir si `/language/set` se mantiene como excepción o se migra a `POST`

### Día 5

- reemplazar o deprecar la auditoría anterior
- publicar checklist formal de release del template
- hacer smoke test final en entorno limpio

## Checklist de Aprobación

El template queda aprobado cuando se cumpla todo lo siguiente:

- [ ] `composer validate --strict` pasa
- [ ] `composer quality` pasa
- [ ] `npm run lint:js` pasa
- [ ] `composer test` pasa localmente sin depender de coverage
- [ ] CI pasa en la matriz soportada
- [ ] no existe dependencia obligatoria del Play CDN de Tailwind
- [ ] `.env.example` cubre variables de despliegue reales
- [ ] la documentación técnica coincide con el estado actual del código
- [ ] existe checklist de release y despliegue

## Decisión Final

### Hoy

`No-Go`

### Después de remediación corta

`Go`

## Nota sobre la auditoría anterior

Este documento refleja el estado verificado el 2026-04-13 y debe considerarse más confiable que [ENTERPRISE-AUDIT.md](/Users/davidcardenas/Developer/PHP/ci4-admin-starter/docs/plan/ENTERPRISE-AUDIT.md:1), que contiene hallazgos históricos ya superados por el código actual.
