# Google Login Configuration (CI4 Admin + CI4 API)

Official guide to enable Google login using the flow implemented in this project:

- `ci4-admin-starter`: renders Google button and sends `id_token`.
- `ci4-api-starter`: validates `id_token` with `GOOGLE_CLIENT_ID` and resolves login/signup pending.

## 1) Key Requirement of the Current Flow

This project uses **Google Identity Services with `id_token` (popup/callback JS)**.  
It does not use OAuth Authorization Code with backend redirect.

Therefore:

- If you need to complete fields in Google Cloud, **what matters is `Authorized JavaScript origins`**.
- **`Authorized redirect URIs` is not part of the current flow**.

## 2) Create OAuth Client ID in Google Cloud

1. Go to **Google Cloud Console**.
2. Select or create a project.
3. Configure **OAuth consent screen** (if it doesn't exist yet).
4. Go to **APIs & Services → Credentials**.
5. Create credential: **OAuth Client ID**.
6. Application type: **Web application**.
7. Add to **Authorized JavaScript origins**:
   - Local admin: `http://localhost:8082`
   - Production admin: `https://admin.yourdomain.com` (adjust to your real domain)
8. Save and copy the **Client ID** (`...apps.googleusercontent.com`).

## 3) Configure `ci4-admin-starter`

In the admin's `.env`:

```dotenv
GOOGLE_CLIENT_ID='your-client-id.apps.googleusercontent.com'
app.baseURL='http://localhost:8082/'
apiClient.baseUrl='http://localhost:8080'
```

Notes:

- The Google button only appears if `GOOGLE_CLIENT_ID` has a value.
- The admin's Google login makes a POST to `/login/google` (internal web route).

## 4) Configure `ci4-api-starter`

In the API's `.env`:

```dotenv
GOOGLE_CLIENT_ID='your-client-id.apps.googleusercontent.com'
```

It must be **exactly the same Client ID** used by the admin.

Also, ensure CORS for the admin's origin:

```dotenv
CORS_ALLOWED_ORIGINS='http://localhost:8082,https://admin.yourdomain.com'
```

## 5) Local Checklist

1. Admin running on `http://localhost:8082`.
2. API running on `http://localhost:8080`.
3. Same `GOOGLE_CLIENT_ID` in both `.env` files.
4. Origin `http://localhost:8082` loaded in Google Cloud.
5. API CORS allows `http://localhost:8082`.
6. Google button appears on `/login`.
7. When authenticating:
   - `200`: creates session and enters dashboard.
   - `202/403/409`: returns to login with message from API.

## 6) Production Checklist

1. Add the final admin origin to Google Cloud:
   - `https://admin.yourdomain.com`
2. Configure in admin:
   - `app.baseURL='https://admin.yourdomain.com/'`
   - `GOOGLE_CLIENT_ID='...'`
3. Configure in API:
   - `GOOGLE_CLIENT_ID='...'` (same value)
   - `CORS_ALLOWED_ORIGINS` includes `https://admin.yourdomain.com`
4. Deploy both services and restart processes.
5. Test from the final domain.

## 7) Common Issues

- **Google button doesn't appear**:
  - `GOOGLE_CLIENT_ID` is empty or not loaded in admin.
- **Unauthorized origin error**:
  - Missing `http://localhost:8082` or final domain in `Authorized JavaScript origins`.
- **API rejects Google token**:
  - `GOOGLE_CLIENT_ID` is different between admin and API.
- **CORS failure in production**:
  - API doesn't include the admin's origin in `CORS_ALLOWED_ORIGINS`.

## 8) CSRF Security Configuration

Since the Google callback is performed via a POST from an external origin (google.com) to the `/login/google` route of the application, it's necessary to exempt this route from CSRF protection in `app/Config/Filters.php`:

```php
public array $globals = [
    'before' => [
        // ...
        'csrf' => ['except' => ['login/google']],
        // ...
    ],
];
```

## 9) Expected Functional Contract

Backend endpoint used by admin:

- `POST /api/v1/auth/google-login`
- Payload: `id_token`, `client_base_url`

Relevant responses:

- `200`: successful login with `access_token` + `refresh_token`
- `202`: signup/login received, account pending approval
- `403`: account pending/disabled
- `409`: provider/identity conflict
