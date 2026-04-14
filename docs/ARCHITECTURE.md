# Architecture & Core Concepts

This document explains the technical foundations of the **CI4 Admin Starter** and how it interacts with the backend API.

## 🏛️ Architectural Overview

This project is a **Server-Rendered Frontend (SRF)**. Unlike a traditional SPA (Single Page Application), it uses CodeIgniter 4 to handle routing, session management, and view rendering, but it **never accesses a database directly**.

### The Decoupled Flow
`Browser <-> CI4 Admin Starter (Frontend) <-> CI4 API Starter (Backend) <-> Database`

1.  **Browser:** Sends a request to a route in the Admin.
2.  **Admin Controller:** Receives the request, validates input (via `FormRequest`), and calls a **Service**.
3.  **Service:** Uses the `ApiClient` to perform an HTTP request to the Backend.
4.  **Backend API:** Processes business logic and returns a standardized JSON.
5.  **ApiClient:** Normalizes the JSON response for the Admin.
6.  **Admin Controller:** Renders a PHP view (with Tailwind/Alpine) and sends it back to the Browser.

---

## 🛰️ The `ApiClient` Deep Dive

The `ApiClient` (`app/Libraries/ApiClient.php`) is the heart of the application. It encapsulates all complexity regarding HTTP communication.

### 1. Automatic Token Refresh
When a request fails with a `401 Unauthorized` status:
1.  The `ApiClient` intercepts the error.
2.  It automatically calls the `/auth/refresh` endpoint using the `refresh_token` stored in the session.
3.  If successful, it updates the session with the new `access_token` and **re-tries the original request** transparently.

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
- **External Resources:** Tailwind and Alpine are loaded via CDN, which must be whitelisted in `Config/ContentSecurityPolicy.php`.

### Data Redaction
To prevent leaking sensitive information in logs, the `ApiClient` includes a `redactData()` method that automatically masks passwords, Base64 file strings, and large payloads before they reach the `log_message()` system.

---

## 📂 Data Flow: Form to API

1.  **Request Object:** An `app/Requests` class defines validation rules (e.g., `required`, `valid_email`).
2.  **Validation:** The Controller uses `$this->validateRequest($request)`.
3.  **Payload Preparation:** The `payload()` method in the Request class converts form data into the `snake_case` JSON structure expected by the API.
4.  **Service Call:** The Service sends this payload to the corresponding endpoint.
5.  **Error Handling:** If the API returns validation errors, they are automatically mapped back to the form fields via `fieldErrors`.
