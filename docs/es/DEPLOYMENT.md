# Despliegue y DevOps

Este documento proporciona directrices para pasar **CI4 Admin Starter** de desarrollo a producción.

## ⚙️ Variables de Entorno (`.env`)

Configura estos valores en tu archivo `.env` para producción. **Nunca hagas commit de tu archivo `.env` al control de versiones.**

### 🖥️ Configuración de Aplicación
- `CI_ENVIRONMENT = production`: Desactiva Debug Toolbar y reportes verbosos de error.
- `app.baseURL = 'https://admin.tudominio.com/'`: La URL pública final (debe ser HTTPS).
- `app.forceGlobalSecureRequests = true`: Asegura que todas las solicitudes se redirigen a HTTPS.
- `app.CSPEnabled = true`: Activa los encabezados de Política de Seguridad de Contenido.
- `cookie.secure = true`: Asegura que las cookies de sesión solo se envíen por HTTPS.

### 🔌 Configuración de Cliente API
- `apiClient.baseUrl = 'https://api.tudominio.com'`: URL de tu backend.
- `apiClient.apiPrefix = '/api/v1'`: Ruta base para endpoints de API.
- `apiClient.appKey = 'apk_...'`: (Opcional) Tu clave API para límites de velocidad más altos.
- `apiClient.logRequests = false`: Desactiva en producción a menos que debuguees problemas de conexión.
- `WEBAPP_BASE_URL = 'https://admin.tudominio.com'`: Usado para deep-linking en emails enviados por la API.

### 📁 Configuración de Carga
- `FILE_MAX_SIZE = 10485760`: Tamaño máximo de archivo en bytes (10MB). Asegúrate de que coincida o sea menor que el límite del backend.

---

## 🔒 Endurecimiento de Seguridad

### 1. Permisos de Archivo
Solo los siguientes directorios requieren permisos de escritura del servidor web (p.ej., `www-data`):
- `writable/cache/`
- `writable/logs/`
- `writable/session/`
- `writable/uploads/` (si se usa localmente)

**Pro Tip:** Ejecuta `chmod -R 775 writable` y `chown -R :www-data writable`.

### 2. Document Root
Tu servidor web **DEBE** apuntar al directorio `public/` como su document root. Esto asegura que los archivos core del framework y configuración no sean accesibles públicamente.

### 3. Configuración de PHP
Asegúrate de que estos valores estén configurados en tu `php.ini` o virtual host:
- `display_errors = Off`
- `log_errors = On`
- `session.cookie_httponly = 1`
- `session.use_strict_mode = 1`

---

## 🌐 Ejemplos de Servidor Web

### Nginx
```nginx
server {
    listen 80;
    server_name admin.tudominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin.tudominio.com;

    root /var/www/ci4-admin-starter/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## 🚀 Checklist de Despliegue en Producción

1. [ ] **Composer:** Ejecuta `composer install --no-dev --optimize-autoloader`.
2. [ ] **Entorno:** Establece `CI_ENVIRONMENT = production`.
3. [ ] **HTTPS:** Verifica que `app.baseURL` use HTTPS y `app.forceGlobalSecureRequests` sea true.
4. [ ] **Conexión API:** Prueba que el Admin pueda alcanzar la API Backend (verifica logs).
5. [ ] **Assets:** Ejecuta `npm ci && npm run build:css` para generar estilos optimizados.
6. [ ] **Permisos:** Verifica que `writable/` sea escribible por el servidor web.
7. [ ] **Seguridad:** Verifica que `app.CSPEnabled = true` y `cookie.secure = true`.
