# Configuración de Google Login (CI4 Admin + CI4 API)

Guía oficial para activar login con Google usando el flujo implementado en este proyecto:

- `ci4-admin-starter`: renderiza botón Google y envía `id_token`.
- `ci4-api-starter`: valida `id_token` con `GOOGLE_CLIENT_ID` y resuelve login/alta pendiente.

## 1) Requisito Clave del Flujo Actual

Este proyecto usa **Google Identity Services con `id_token` (popup/callback JS)**.  
No usa OAuth Authorization Code con redirect backend.

Por eso:

- Si necesitas completar campos en Google Cloud, **lo importante es `Authorized JavaScript origins`**.
- **`Authorized redirect URIs` no es parte del flujo actual**.

## 2) Crear OAuth Client ID en Google Cloud

1. Ir a **Google Cloud Console**.
2. Seleccionar o crear proyecto.
3. Configurar **OAuth consent screen** (si aún no existe).
4. Ir a **APIs & Services → Credentials**.
5. Crear credencial: **OAuth Client ID**.
6. Tipo de aplicación: **Web application**.
7. En **Authorized JavaScript origins** agregar:
   - Local admin: `http://localhost:8082`
   - Producción admin: `https://admin.tudominio.com` (ajusta a tu dominio real)
8. Guardar y copiar el **Client ID** (`...apps.googleusercontent.com`).

## 3) Configurar `ci4-admin-starter`

En `.env` del admin:

```dotenv
GOOGLE_CLIENT_ID='tu-client-id.apps.googleusercontent.com'
app.baseURL='http://localhost:8082/'
apiClient.baseUrl='http://localhost:8080'
```

Notas:

- El botón Google solo aparece si `GOOGLE_CLIENT_ID` tiene valor.
- El login Google del admin hace POST a `/login/google` (ruta web interna).

## 4) Configurar `ci4-api-starter`

En `.env` del API:

```dotenv
GOOGLE_CLIENT_ID='tu-client-id.apps.googleusercontent.com'
```

Debe ser **exactamente el mismo Client ID** que usa el admin.

Además, asegurar CORS para el origen del admin:

```dotenv
CORS_ALLOWED_ORIGINS='http://localhost:8082,https://admin.tudominio.com'
```

## 5) Checklist Local

1. Admin corriendo en `http://localhost:8082`.
2. API corriendo en `http://localhost:8080`.
3. Mismo `GOOGLE_CLIENT_ID` en ambos `.env`.
4. Origen `http://localhost:8082` cargado en Google Cloud.
5. CORS del API permite `http://localhost:8082`.
6. En `/login`, aparece botón Google.
7. Al autenticar:
   - `200`: crea sesión y entra a dashboard.
   - `202/403/409`: vuelve a login con mensaje del API.

## 6) Checklist Producción

1. Agregar origen final del admin en Google Cloud: `https://admin.tudominio.com`
2. Configurar en admin: `app.baseURL='https://admin.tudominio.com/'`, `GOOGLE_CLIENT_ID='...'`
3. Configurar en API: `GOOGLE_CLIENT_ID='...'` (mismo valor), `CORS_ALLOWED_ORIGINS` incluye `https://admin.tudominio.com`
4. Deploy de ambos servicios y reinicio de procesos.
5. Prueba real desde dominio final.

## 7) Problemas Comunes

- **No aparece botón Google**: `GOOGLE_CLIENT_ID` vacío o mal cargado.
- **Error de origen no autorizado**: Falta origen en `Authorized JavaScript origins`.
- **API rechaza token Google**: `GOOGLE_CLIENT_ID` distinto entre admin y API.
- **Falla por CORS**: API no incluye origen del admin en `CORS_ALLOWED_ORIGINS`.

## 8) Configuración de Seguridad (CSRF)

Como el callback de Google se realiza mediante POST desde Google hacia `/login/google`, es necesario exceptuar esta ruta de CSRF en `app/Config/Filters.php`:

```php
public array $globals = [
    'before' => [
        // ...
        'csrf' => ['except' => ['login/google']],
        // ...
    ],
];
```

## 9) Contrato Funcional Esperado

Endpoint backend usado por admin: `POST /api/v1/auth/google-login`
Payload: `id_token`, `client_base_url`
Respuestas: `200` (éxito), `202` (pendiente), `403` (bloqueado), `409` (conflicto)
