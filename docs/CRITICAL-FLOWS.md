# Critical Flows in the Admin

This document details specific implementations that guarantee Admin stability when communicating with the API. **Do not change these logics without understanding their impact.**

## 1. File Upload (Base64 Mode)

To ensure maximum compatibility and avoid cURL errors or multipart protocol limits, the Admin uses **Base64** as the primary upload method.

- **Location:** `App\Services\FileApiService::upload()`
- **Logic:** The file is read from disk, encoded to Base64, and sent in a JSON payload via a standard `POST` request.
- **Advantage:** It is immune to multipart "boundary" issues and allows the API to process the file resiliently.

## 2. ApiClient Retry Logic (Rewind)

The `ApiClient` has automatic JWT token refresh logic. If a request fails with a `401`, it attempts to refresh the token and resend the original request.

- **⚠️ Critical Point:** If the original request contained streams, they are consumed on the first attempt.
- **Solution:** In `ApiClient::request()`, before the retry, the `multipart` array is traversed and `rewind($stream)` is applied to ensure the second attempt doesn't send an empty body.

## 3. Image Display and Downloads

Images and downloads are proxied through the Admin (`FileController::view` and `FileController::download`) to inject the API authentication headers.

- **⚠️ The Debug Toolbar Problem:** CodeIgniter attempts to inject the "Debug Toolbar" HTML code in all responses. If the response is a binary image, this corrupts the file and throws a `TypeError`.
- **Solution:** The controller must return the binary response with correct `Content-Type` and `Content-Disposition` headers, or redirect to the backend download URL, avoiding any mutation of the binary body.

## 4. Validation Error Normalization

The API and Admin use the same `snake_case` standard for validation keys.

- **Logic:** No `camelCase` compatibility layer is maintained.
- **Impact:** If a new field is added to the API, the same `snake_case` name must be used in Admin forms to preserve direct error mapping.
