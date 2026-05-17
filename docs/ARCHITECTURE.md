# Architecture & Core Concepts

This document explains the technical foundations of the **CI4 Admin Starter** and how it interacts with the backend API.

## 🏛️ Architectural Overview

This project is a **Server-Rendered Frontend (SRF)**. Unlike a traditional SPA (Single Page Application), it uses CodeIgniter 4 to handle routing, session management, and view rendering, but it **never accesses a database directly**.

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          End User's Browser                              │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  HTML Pages  │  Tailwind CSS  │  Alpine.js  │  Lucide Icons    │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │ HTTP Request/Response (+ Session Cookie)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    CI4 Admin Starter (Port 8082)                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │
│  │   Routes     │  │ Controllers  │  │   Services   │  │ ApiClient  │  │
│  │              │→ │              │→ │              │→ │            │  │
│  │ /users/data  │  │ UserController│ │UserApiService│ │ HTTP Client│  │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘  │
│         │                │                    │                │         │
│         │                ▼                    │                │         │
│         │           ┌──────────────┐          │                │         │
│         │           │ FormRequest  │          │                │         │
│         │           │              │          │                │         │
│         │           │ • rules()    │          │                │         │
│         │           │ • validate() │          │                │         │
│         │           │ • payload()  │          │                │         │
│         │           └──────────────┘          │                │         │
│         │                                     │                │         │
│         └──────────────────────────────────────┼────────────────┘         │
│                    PHP Session Storage        │                         │
│                (access_token, user, locale)   │                         │
│                                               ▼                         │
│                         ┌────────────────────────────┐                  │
│                         │  View Rendering (PHP)      │                  │
│                         │                            │                  │
│                         │  • Layout with Sidebar     │                  │
│                         │  • Data passed to template │                  │
│                         │  • HTML response           │                  │
│                         └────────────────────────────┘                  │
└─────────────────────────────────┬──────────────────────────────────────┘
                                 │ HTTP Response (HTML)
                                 │
                                 │ JWT Token Sent via Header
                                 │ Authorization: Bearer <token>
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              CI4 API Starter (Backend) (Port 8080)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │
│  │   Routes     │  │ Controllers  │  │   Services   │  │ Middleware │  │
│  │              │→ │              │→ │              │→ │            │  │
│  │ /api/v1/users│  │ UserController│ │ UserService  │  │ Auth Check │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘  │
│         │                │                    │                │         │
│         │                ▼                    ▼                ▼         │
│         │           ┌───────────────────────────────────────┐            │
│         │           │  Database Layer                       │            │
│         └──────────→│  • Models & Repositories             │            │
│                     │  • Business Logic                    │            │
│                     │  • Data Persistence                  │            │
│                     └───────────────────────────────────────┘            │
└─────────────────────────────────┬──────────────────────────────────────┘
                                 │ JSON Response
                                 │ {ok, data, messages, errors}
                                 ▼
                            ┌──────────────┐
                            │  Database    │
                            │  (MySQL/etc) │
                            └──────────────┘
```

### Request/Response Flow

1. **User makes request** → Browser sends HTTP request to Admin (port 8082)
2. **Router dispatches** → `app/Config/Routes.php` routes to appropriate Controller
3. **Validation** → Controller instantiates `FormRequest` and validates input
4. **Service layer** → Controller calls appropriate Service method
5. **API communication** → Service uses `ApiClient` to send HTTP request to Backend (port 8080)
6. **Backend processing** → Backend API validates, processes business logic, queries database
7. **Response normalization** → `ApiClient` normalizes JSON response to standard format
8. **View rendering** → Controller renders PHP template with response data
9. **HTML response** → Admin sends complete HTML page back to browser
10. **Display** → Browser renders HTML with Tailwind CSS and Alpine.js for interactivity

---

## 🛰️ The `ApiClient` Deep Dive

The `ApiClient` (`app/Libraries/ApiClient.php`) is the heart of the application. It encapsulates all complexity regarding HTTP communication.

### 1. Automatic Token Refresh

When a request fails with a `401 Unauthorized` status, the ApiClient automatically refreshes the token:

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Make API Request                                             │
│    GET /api/v1/users                                            │
│    Authorization: Bearer <access_token> (expired)               │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Backend returns 401 Unauthorized                             │
│    (access_token has expired)                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. ApiClient Intercepts 401                                     │
│    Reads refresh_token from PHP session                         │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Send Refresh Request                                         │
│    POST /api/v1/auth/refresh                                    │
│    {refresh_token: "..."}                                       │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Backend Returns New Tokens                                   │
│    {                                                             │
│      access_token: "eyJ...",                                    │
│      refresh_token: "...",                                      │
│      expires_in: 3600                                           │
│    }                                                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Update Session & Retry Original Request                      │
│    session('access_token', new_token)                           │
│    session('token_expires_at', now + 3600)                      │
│    GET /api/v1/users (retried with new token)                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Original Request Succeeds                                    │
│    Returns 200 OK with user data                                │
│    All transparent to controller!                               │
└─────────────────────────────────────────────────────────────────┘
```

**Key Points:**
- Automatic refresh is **transparent** to controllers and services
- Token stored in **server-side PHP session**, never in browser
- If refresh fails (refresh token expired), session is destroyed and user redirected to login
- Each refresh updates both `access_token` and `refresh_token` for security

### 2. Response Normalization
Every call returns a consistent array structure:
- `ok` (bool): `true` for 2xx status codes.
- `status` (int): HTTP status code.
- `data` (array): The main payload from the API.
- `messages` (array): General success or error messages.
- `fieldErrors` (array): Validation errors mapped to form field names.
- `raw` (string): The original JSON body.

### 3. Localization Synchronization
The `ApiClient` automatically injects the `Accept-Language` header based on the user's current session locale (`en` or `es`), ensuring the Backend returns messages in the correct language.

---

## 🛡️ Security Patterns

### Session-Based JWT
While the Backend is stateless (JWT), the Admin is **stateful** (PHP Sessions).
- **Storage:** JWT tokens are stored in server-side PHP sessions, never in the browser's `localStorage` or `cookies` (except for the `session_id`). This mitigates XSS risks.
- **Lifetime:** The Admin tracks the `expires_at` value to proactively handle session expiration.

### Content Security Policy (CSP)
The project is designed to work with strict CSP headers.
- **Nonces:** All inline scripts and styles (where used) must include a CSP nonce via `csp_script_nonce()`.
- **External Resources:** Tailwind, Alpine and Lucide are built/vendored locally (`npm run build:all`) and served from `public/assets/`. The layout transparently falls back to pinned jsdelivr CDN URLs only when a vendored copy is missing (e.g. on a fresh clone before `npm install`). If you re-enable the CDN path in production, whitelist `cdn.jsdelivr.net` in `Config/ContentSecurityPolicy.php`.

### Data Redaction
To prevent leaking sensitive information in logs, the `ApiClient` includes a `redactData()` method that automatically masks passwords, Base64 file strings, and large payloads before they reach the `log_message()` system.

---

## 📂 Data Flow: Form to API

```
┌────────────────────────────────────────────────────────────────┐
│ 1. User Submits Form (HTML POST)                               │
│    <form method="POST" action="/users">                         │
│      <input name="first_name" value="John"/>                    │
│      <input name="email" value="john@example.com"/>             │
│    </form>                                                      │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 2. Controller Instantiates FormRequest                          │
│    $request = service('formRequest',                            │
│        UserStoreRequest::class, false);                         │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 3. Validation (FormRequest::rules())                            │
│    Rules check:                                                 │
│    • first_name is required                                    │
│    • email is valid email format                               │
│    • email max 255 chars                                       │
│                                                                 │
│    If validation fails → redirect with field errors            │
└────────────────┬───────────────────────────────────────────────┘
                 │ (if valid)
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 4. Payload Normalization (FormRequest::payload())               │
│    Input Form Data:                                             │
│    [                                                            │
│      'first_name' => 'John',                                    │
│      'email' => 'john@example.com'                              │
│    ]                                                            │
│                                                                 │
│    Output (normalized for API):                                │
│    [                                                            │
│      'first_name' => 'John',  // trimmed                        │
│      'email' => 'john@example.com'  // lowercased               │
│    ]                                                            │
│                                                                 │
│    Tasks:                                                       │
│    • Trim whitespace                                           │
│    • Convert types (string to int, bool)                       │
│    • Filter empty fields                                       │
│    • Map field names to API conventions                        │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 5. Service Layer Call                                           │
│    $response = $this->safeApiCall(                              │
│        fn() => $this->userService->create(                      │
│            $request->payload()                                  │
│        )                                                        │
│    );                                                           │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 6. ApiClient Makes HTTP Request                                │
│    POST /api/v1/users                                           │
│    Authorization: Bearer <access_token>                         │
│    Accept-Language: en                                          │
│    X-App-Key: <optional>                                        │
│                                                                 │
│    Body:                                                        │
│    {                                                            │
│      "first_name": "John",                                      │
│      "email": "john@example.com"                                │
│    }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 7a. Backend Validation Success                                  │
│     Response: 201 Created                                       │
│     {                                                           │
│       "ok": true,                                               │
│       "data": {id: 123, first_name: "John", email: "..."}       │
│     }                                                           │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 7b. Backend Validation Failure                                  │
│     Response: 422 Unprocessable Entity                          │
│     {                                                           │
│       "ok": false,                                              │
│       "errors": {                                               │
│         "email": "Email already exists"                         │
│       }                                                         │
│     }                                                           │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 8. ApiClient Normalizes Response                                │
│    Standard format (always):                                    │
│    {                                                            │
│      'ok' => true/false,                                        │
│      'status' => 201/422/500,                                   │
│      'data' => [...],                                           │
│      'messages' => [...],                                       │
│      'fieldErrors' => {email: 'Email already exists'}           │
│    }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 9. Controller Handles Response                                  │
│                                                                 │
│    If success ($response['ok']):                                │
│      → Redirect with flash success message                      │
│                                                                 │
│    If failure (!$response['ok']):                               │
│      → Check for fieldErrors                                    │
│      → If field errors: Redirect with field error data          │
│      → Else: Show general error message                         │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 10. View Rendering & Display                                    │
│     Controller renders PHP template with:                       │
│     • Form (if validation failed)                               │
│     • Old field values (repopulate)                             │
│     • Field error messages                                      │
│     • Flash success/error messages                              │
└────────────────────────────────────────────────────────────────┘
```

**Key Concepts:**

- **FormRequest validation** happens before the API call (frontend validation)
- **Backend validation** happens on the API (business logic validation)
- **Automatic error mapping** from API response back to form fields
- **safeApiCall()** wraps the API call for exception handling
- **All responses normalized** to consistent format by ApiClient
