# Guía de Resolución de Problemas

Soluciones para problemas comunes en **CI4 Admin Starter**.

## Problemas de Configuración Inicial

### "Connection refused" o "localhost:8180 no responde"

**Problema:** La API del backend no está accesible.

**Solución:**
1. Asegúrate de que la API está ejecutándose: `php spark serve --port 8180` en proyecto backend
2. Verifica `apiClient.baseUrl` en `.env` (sin trailing slash): `http://localhost:8180`
3. Reinicia servidor PHP
4. Verifica conectividad: `curl http://localhost:8180/api/v1/health`

### "Errores CORS" o "Solicitud bloqueada"

**Problema:** El navegador bloquea solicitudes a la API con error CORS.

**Solución:**
1. Verifica mensaje CORS exacto en consola del navegador
2. Verifica que el backend tiene encabezados CORS para tu dominio frontend
3. Asegúrate de que `apiClient.baseUrl` no tiene trailing slash

### "Los estilos no se aplican" (Página rota)

**Problema:** Sin colores, layout roto.

**Solución:**
1. Detén `npm run dev:css` (Ctrl+C)
2. Ejecuta `npm run build:css` para reconstruir
3. Reinicia con `npm run dev:css`
4. Recarga el navegador (Cmd+Shift+R o Ctrl+Shift+R)

---

## Problemas de Autenticación y Sesión

### "Las cookies de sesión no se establecen"

**Problema:** El login no persiste; te desconectas después de refrescar.

**Solución:**
1. Verifica `app/Config/Session.php`:
   ```php
   public string $driver = 'database';  // o 'files'
   ```
2. Si usas `database`, asegúrate que tabla `sessions` existe: `php spark migrate`

### "Errores 401 Unauthorized en todas las solicitudes"

**Problema:** Todas las solicitudes retornan 401.

**Solución:**
1. Si usas `apiClient.appKey`, verifica que sea correcto (clave incorrecta causa 401)
2. Intenta remover `apiClient.appKey` temporalmente para aislar el problema
3. Verifica en logs del backend

### "Sesión expirada, necesito reloguearme constantemente"

**Problema:** El JWT expira muy rápido.

**Solución:**
1. Verifica el `expires_in` devuelto por el backend
2. Ajusta `apiClient.timeout` si es necesario
3. Verifica que el servidor tiene hora sincronizada (NTP)

---

## Problemas de Carga y Descargas de Archivos

### "Los archivos no se cargan"

**Problema:** Error al intentar cargar archivos.

**Solución:**
1. Verifica límite en `FILE_MAX_SIZE` en `.env`
2. Verifica configuración PHP: `upload_max_filesize`, `post_max_size`
3. Verifica que la API está recibiendo las solicitudes (revisar logs backend)

### "Error 413 Payload Too Large"

**Problema:** El archivo es demasiado grande.

**Solución:**
1. Aumenta en `.env`: `FILE_MAX_SIZE = 52428800` (50MB)
2. Aumenta en `php.ini`: `upload_max_filesize = 100M`, `post_max_size = 100M`

---

## Problemas de Base de Datos (Backend)

### "Error: No such table"

**Problema:** Faltan migraciones.

**Solución:**
1. En proyecto backend: `php spark migrate`
2. Verifica status: `php spark migrate:status`

---

## Solicitud de Ayuda

Si tu problema no está aquí:
1. Revisa [FAQ.md](./FAQ.md) para preguntas generales
2. Revisa [ARCHITECTURE.md](./ARCHITECTURE.md) para entender el flujo
3. Verifica logs en `writable/logs/`
4. Abre un issue en GitHub con detalles del problema
