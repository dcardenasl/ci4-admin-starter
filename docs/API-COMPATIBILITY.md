# API Compatibility: CI4 Admin Starter

## Objective

Define mandatory rules to ensure complete compatibility between this frontend (`ci4-admin-starter`) and the backend (`ci4-api-starter`).

## Architecture Principle

- This project is an **administrative frontend template**.
- The database and business logic belong to the backend.
- **Strict Contract:** The API expects and returns data in **`snake_case`**. The frontend must respect this standard in all JSON payloads.

## Mandatory Compatibility Rules

1. **Authentication:** JWT stored in server-side sessions (`access_token`, `refresh_token`). The `ApiClient` handles automatic token refresh.
2. **Naming Standard:** Always use `snake_case` for JSON keys (e.g., `first_name`, `original_name`).
3. **User Creation Flow:** User creation by an administrator triggers a **mandatory invitation**. The frontend must not attempt to set passwords or offer a toggle to bypass the invitation.
4. **Responses:** The `ApiClient` normalizes all responses (success and error) so the frontend doesn't have to deal with backend variations.

## Server-Driven Table Contract

- Supported query params: `search`, `filter[...]`, `sort`, `limit`, `page`, `cursor`.
- `cursor` takes priority over `page` when both exist.
- `sort` is forwarded intact to the backend, including the `-` prefix for descending order.
- The template must not translate `sort` to `order_by/order_dir` or `limit` to `per_page` in the public contract.

## File Compatibility (Upload/Download)

### File Upload (Base64)

To maximize reliability, the frontend converts files to Base64 and sends them via a standard `POST` JSON request:
- Field: `file` (Base64 Data URI).
- Field: `filename` (original filename).
- Size limit: `FILE_MAX_SIZE` (bytes), enforced with effective limit `min(FILE_MAX_SIZE, upload_max_filesize, post_max_size)` in Admin.

### Download and Preview

- The Admin controller must return the binary response with correct headers or redirect to a signed backend URL, without modifying the binary payload.
- This is critical to prevent middleware or development toolbars from corrupting the response.

## Error Normalization

The API returns errors in `snake_case`. The Admin must use form `name`/`id` attributes in `snake_case` so error association is direct, without compatibility mappings.

## Acceptance Criteria for Changes

Any architectural change (e.g., switching from Multipart to Base64 or vice versa) must be documented in this contract and verified in both projects simultaneously to prevent regressions.
