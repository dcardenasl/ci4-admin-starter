# Configuracion de Google Login (CI4 Admin + CI4 API)

Guia oficial para activar login con Google usando el flujo implementado en este proyecto:

- `ci4-admin-starter`: renderiza boton Google y envia `id_token`.
- `ci4-api-starter`: valida `id_token` con `GOOGLE_CLIENT_ID` y resuelve login/alta pendiente.

## 1) Requisito clave del flujo actual

Este proyecto usa **Google Identity Services con `id_token` (popup/callback JS)**.  
No usa OAuth Authorization Code con redirect backend.

Por eso:

- Si necesitas completar campos en Google Cloud, **lo importante es `Authorized JavaScript origins`**.
- **`Authorized redirect URIs` no es parte del flujo actual**.

## 2) Crear OAuth Client ID en Google Cloud

1. Ir a **Google Cloud Console**.
2. Seleccionar o crear proyecto.
3. Configurar **OAuth consent screen** (si aun no existe).
4. Ir a **APIs & Services -> Credentials**.
5. Crear credencial: **OAuth Client ID**.
6. Tipo de aplicacion: **Web application**.
7. En **Authorized JavaScript origins** agregar:
8. Local admin: `http://localhost:8082`
9. Produccion admin: `https://admin.tudominio.com` (ajusta a tu dominio real)
10. Guardar y copiar el **Client ID** (`...apps.googleusercontent.com`).

## 3) Configurar `ci4-admin-starter`

En `.env` del admin:

```dotenv
GOOGLE_CLIENT_ID='tu-client-id.apps.googleusercontent.com'
app.baseURL='http://localhost:8082/'
apiClient.baseUrl='http://localhost:8080'
```

Notas:

- El boton Google solo aparece si `GOOGLE_CLIENT_ID` tiene valor.
- El login Google del admin hace POST a `/login/google` (ruta web interna).

## 4) Configurar `ci4-api-starter`

En `.env` del API:

```dotenv
GOOGLE_CLIENT_ID='tu-client-id.apps.googleusercontent.com'
```

Debe ser **exactamente el mismo Client ID** que usa el admin.

Ademas, asegurar CORS para el origen del admin:

```dotenv
CORS_ALLOWED_ORIGINS='http://localhost:8082,https://admin.tudominio.com'
```

## 5) Checklist local

1. Admin corriendo en `http://localhost:8082`.
2. API corriendo en `http://localhost:8080`.
3. Mismo `GOOGLE_CLIENT_ID` en ambos `.env`.
4. Origen `http://localhost:8082` cargado en Google Cloud.
5. CORS del API permite `http://localhost:8082`.
6. En `/login`, aparece boton Google.
7. Al autenticar:
8. `200`: crea sesion y entra a dashboard.
9. `202/403/409`: vuelve a login con mensaje del API.

## 6) Checklist produccion

1. Agregar origen final del admin en Google Cloud:
2. `https://admin.tudominio.com`
3. Configurar en admin:
4. `app.baseURL='https://admin.tudominio.com/'`
5. `GOOGLE_CLIENT_ID='...'`
6. Configurar en API:
7. `GOOGLE_CLIENT_ID='...'` (mismo valor)
8. `CORS_ALLOWED_ORIGINS` incluye `https://admin.tudominio.com`
9. Deploy de ambos servicios y reinicio de procesos.
10. Prueba real desde dominio final.

## 7) Problemas comunes

- **No aparece boton Google**:
  - `GOOGLE_CLIENT_ID` vacio o mal cargado en admin.
- **Error de origen no autorizado**:
  - Falta `http://localhost:8082` o dominio final en `Authorized JavaScript origins`.
- **API rechaza token Google**:
  - `GOOGLE_CLIENT_ID` distinto entre admin y API.
- **Falla por CORS en produccion**:
  - API no incluye el origen del admin en `CORS_ALLOWED_ORIGINS`.

## 8) Configuración de Seguridad (CSRF)

Como el callback de Google se realiza mediante un POST desde un origen externo (google.com) hacia la ruta `/login/google` de la aplicación, es necesario exceptuar esta ruta de la protección CSRF en `app/Config/Filters.php`:

```php
public array $globals = [
    'before' => [
        // ...
        'csrf' => ['except' => ['login/google']],
        // ...
    ],
];
```

## 9) Contrato funcional esperado

Endpoint backend usado por admin:

- `POST /api/v1/auth/google-login`
- payload: `id_token`, `client_base_url`

Respuestas relevantes:

- `200`: login exitoso con `access_token` + `refresh_token`
- `202`: alta/login recibido, cuenta pendiente de aprobacion
- `403`: cuenta pendiente/no habilitada
- `409`: conflicto de proveedor/identidad
