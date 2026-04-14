# Contributing

Guia corta para mantener este repositorio como template enterprise reutilizable.

## Antes de empezar

- Trabaja desde una rama enfocada a un solo cambio.
- Mantén controllers delgados; la logica HTTP/API debe vivir en servicios.
- Usa `lang(...)` para mensajes visibles al usuario.
- No agregues acceso directo a base de datos ni reglas de negocio persistentes en este repo.

## Checklist para añadir un modulo nuevo

Sigue este orden para mantener consistencia:

1. Crear el servicio del modulo y su interfaz.
2. Registrar el binding en `app/Config/Services.php` si corresponde.
3. Crear o ajustar controller del modulo usando la interfaz, no la clase concreta.
4. Crear requests de validacion en `app/Requests` cuando haya formularios.
5. Crear views del modulo siguiendo los layouts y helpers existentes.
6. Registrar rutas en `app/Config/Routes.php` o en la config del modulo.
7. Añadir archivos de idioma en `app/Language/en` y `app/Language/es`.
8. Añadir tests unitarios y feature del flujo principal y de errores.
9. Verificar compatibilidad con el contrato de `ci4-api-starter`.

## Checklist de calidad antes de abrir PR

Ejecuta desde la raiz:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse --debug
vendor/bin/php-cs-fixer fix --dry-run --diff
npm run build:css
```

Si cambias frontend visual, valida tambien el watch local:

```bash
npm run dev:css
```

## Criterios de aceptacion

- No dejar strings de sesion, rutas o contratos JSON duplicados si ya existe una abstraccion.
- No introducir nuevas dependencias a implementaciones concretas cuando exista interfaz.
- Toda correccion de bug debe traer al menos un test que capture la regresion.
- Mantener README, tests/README y documentacion tecnica alineados cuando cambie el flujo de trabajo.
