# Tests Guide

Guia operativa para trabajar con la suite de tests del template.

## Ejecutar suites

Instala dependencias primero:

```bash
composer install
```

Comandos habituales:

```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/unit
vendor/bin/phpunit tests/feature
vendor/bin/phpunit --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/
```

La CI exige cobertura minima de lineas del `80%`. Para generar coverage localmente necesitas un driver compatible (`xdebug` o `pcov`).

## Convenciones del repositorio

- `tests/unit/`: logica aislada, helpers, requests, filters, services y librerias con dependencias mockeadas.
- `tests/feature/`: flujos HTTP end-to-end usando el test layer de CodeIgniter.
- `tests/database/`: reservado para casos que realmente necesiten DB.
- `tests/_support/`: utilidades compartidas, seeds y dobles de apoyo.

Regla practica:

- Si el cambio vive en un helper, request, filter o servicio puro, empieza por `unit`.
- Si el cambio afecta redirects, flash messages, auth, rutas o rendering condicionado, agrega `feature`.
- Si corriges una regresion, el test debe fallar antes del fix y cubrir exactamente ese comportamiento.

## Convenciones de mocks

- Mockea `ApiClientInterface` o la interfaz del servicio, no clases concretas si existe contrato.
- Mantén los fixtures pequeños y pegados al caso de prueba; evita payloads enormes que oculten la intención.
- Verifica shape de respuesta del wrapper (`ok`, `status`, `data`, `messages`, `fieldErrors`) cuando pruebes servicios/API client, pero recuerda que el API canónico habla `status`, `message`, `data`, `errors`.
- En controllers, usa stubs del servicio para forzar ramas de exito, validacion y error de API.
- No mockees detalles internos de CodeIgniter salvo que el framework sea precisamente lo que se quiere aislar.

## Cobertura esperada al añadir un modulo

Checklist minimo para cualquier modulo nuevo:

- Test unitario del servicio API del modulo.
- Tests unitarios de requests/form payloads si el modulo recibe formularios.
- Tests feature para `index`, `create/store`, `edit/update` y el principal camino de error.
- Tests de permisos/filtros si el modulo introduce restricciones nuevas.
- Archivos de idioma cubiertos indirectamente por assertions basadas en `lang(...)` cuando aplique.

Si el modulo agrega helpers o transformaciones de presentacion reutilizables, cubrelos con tests unitarios dedicados.

## Recursos

- [CodeIgniter 4 Testing Guide](https://codeigniter.com/user_guide/testing/index.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
