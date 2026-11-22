# Craigslist Upgrade Backend API Contract

## A. Overview

- Architecture: Laravel backend with a Next.js frontend.
- Backend: Laravel 13, PHP 8.5, Sanctum, SQLite for tests, MySQL for local development.
- Local API origin: `http://localhost:8000`
- API base path: `/api/v1`
- Required request headers for JSON requests:
  - `Accept: application/json`
  - `Content-Type: application/json` for JSON bodies
- JSON response conventions:
  - Laravel `JsonResource` responses are wrapped in `data`; some endpoints use custom envelopes, such as token-auth profile returning `{"user": ...}`.
  - Paginated responses use `data`, `links`, and `meta`.
  - Validation failures return HTTP 422 with an `errors` object.
  - Authentication failures return HTTP 401 JSON.
  - API routes are defined under `/api/v1`, with additional token-authentication lab routes under `/api`.

## B. Public Marketplace API

### GET /api/v1/categories
- Authentication: public
- Success: 200
- Response: `{ "data": [ { "id", "name", "slug", "description", "parent_id", "sort_order", "children": [...] } ] }`
- Hierarchy: active roots and their active direct children only.

### GET /api/v1/listings
- Authentication: public
- Success: 200
- Query parameters:
  - `category`: category slug
  - `q`: keyword search in title and description; optional string, maximum 255 characters
  - `municipality[]`: array of municipality names; optional array of strings, maximum 100 characters each
  - `barangay`: barangay name; optional string, maximum 100 characters
  - `min_price`: minimum fixed price; optional numeric value, minimum 0
  - `max_price`: maximum fixed price; optional numeric value, minimum 0 and greater than or equal to `min_price`
  - `price_type`: `fixed`, `free`, or `contact`
  - `sort`: `newest`, `price_asc`, or `price_desc`
  - `per_page`: 1-50, default 15
  - `page`: page number, minimum 1
- Response: paginated `{ "data": [...], "links": {...}, "meta": {...} }`
- Listing summary fields:
  - `id`
  - `title`
  - `price_type`
  - `price`
  - `currency`
  - `municipality`
  - `province`
  - `status`
  - `primary_image_url`
  - `created_at`
  - `category`: `{ "id", "name", "slug" }` when loaded
- Behavior: only `active` listings are returned.

### GET /api/v1/listings/{listing}
- Authentication: public
- Success: 200
- Returns 404 unless the listing is active.
- Response includes:
  - listing fields
  - `category`
  - `user`: `{ "id", "name" }`
  - `images`: array of `{ "id", "url", "sort_order", "is_primary" }`

## C. Search, filtering, sorting, and pagination

- `category`: active root includes the root and its active direct children; active child returns only that child; nonexistent slugs return HTTP 422; inactive or unselectable slugs return HTTP 200 with an empty result.
- `q`: case-insensitive `LIKE` search in `title` and `description`.
- `municipality[]`: exact municipality match, array input.
- `barangay`: exact barangay match.
- `min_price` and `max_price`: apply only to `fixed` price listings.
- `price_type`: exact match.
- `sort=newest`: `created_at` descending, then `id` ascending.
- `sort=price_asc`: non-null prices ascending, null prices last, then `id` ascending.
- `sort=price_desc`: non-null prices descending, null prices last, then `id` ascending.
- Pagination uses Laravel pagination metadata and links.

## D. Authentication

### Cookie-based Sanctum
Endpoints:
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/user`

Behavior:
- Registration validates name, email, and confirmed password.
- Login uses the `web` guard and session regeneration.
- Logout flushes and regenerates the session.
- Protected endpoints use `auth:sanctum`.
- CSRF cookie flow is required for browser clients.
- Responses use `UserResource`.

Cookie-auth response details:
- Register: `201` with `data` containing `id`, `name`, `email`, `created_at`, and `updated_at`.
- Login: `200` with `data` containing `id`, `name`, `email`, `created_at`, and `updated_at`.
- Current user: `200` with `data` containing `id`, `name`, `email`, `created_at`, and `updated_at`.
- Logout: `200` with `{ "message": "Logged out successfully." }`.

### Cookie-auth browser flow
1. Call `GET /sanctum/csrf-cookie` with credentials.
2. Read the `XSRF-TOKEN` cookie and send its URL-decoded value as `X-XSRF-TOKEN` on state-changing requests.
3. Send browser requests with credentials included.
4. Call `POST /api/v1/auth/register` or `POST /api/v1/auth/login`.
5. Reuse the session cookie for subsequent requests.
6. Call `POST /api/v1/auth/logout` with credentials and CSRF protection.

Example request shape:
```js
fetch(url, {
  credentials: 'include',
  headers: {
    Accept: 'application/json',
    'X-XSRF-TOKEN': csrfToken,
  },
});
```

For `GET` requests, credentials are still required to maintain the session, but the CSRF header is only needed for state-changing requests.

Verified local configuration:
- frontend origin: `http://localhost:3000`
- backend origin: `http://localhost:8000`
- CORS allows `http://localhost:3000` with credentials
- Sanctum stateful domains include `localhost:3000`
- session cookie domain is `localhost`

Production requirements:
- frontend and backend must use compatible same-site/registrable-domain cookie settings over HTTPS
- CORS allowed origins must match the production frontend origin
- Sanctum stateful domains must include the production frontend origin
- session cookie domain and secure-cookie settings must be configured for production

### Token-based lab endpoints
Endpoints:
- `POST /api/register`
- `POST /api/login`
- `GET /api/user`
- `POST /api/logout`

Behavior:
- Registration validates name, email, and confirmed password.
- Registration returns `201` with `message` and `user`.
- Login verifies credentials and returns `access_token` and `token_type: Bearer`.
- Invalid login returns `401`.
- Profile requires a Bearer token.
- Logout revokes the current token.
- Session-authenticated requests to token logout return 409 because no personal access token is present.

Token-auth response details:
- Register: `201` with `message: "Registration successful"` and `user`. The `user` object contains `id`, `name`, `email`, `created_at`, and `updated_at`; password and remember token are hidden.
- Login: `200` with `message`, `user`, `access_token`, and `token_type: "Bearer"`. The access token is a secret and must not be logged or committed.
- Profile: `200` with `{ "user": { "id", "name", "email", "created_at", "updated_at" } }`.
- Logout: `200` with `{ "message": "Logout successful" }`.

The two authentication approaches can coexist. The frontend integration approach must be agreed before production use.

### Rate limiting
- Cookie auth:
  - `POST /api/v1/auth/register`: `throttle:registration`, 3 requests per minute per IP
  - `POST /api/v1/auth/login`: `throttle:login`, 5 requests per minute per IP
  - `POST /api/v1/auth/logout` and `GET /api/v1/auth/user`: no explicit route throttle
- Token lab routes:
  - `POST /api/register`: `throttle:registration`, 3 requests per minute per IP
  - `POST /api/login`: `throttle:login`, 5 requests per minute per IP
  - `GET /api/user` and `POST /api/logout`: no explicit route throttle
- The instructor's guide intentionally omits rate limiting. Add production-appropriate rate limiting before public deployment if required.

## E. Seller Listing API

All seller endpoints require `auth:sanctum`. Ownership requirements:
- `GET /api/v1/seller/listings`: returns only the authenticated seller's own listings.
- `POST /api/v1/seller/listings`: the authenticated user becomes the listing owner.
- `GET`, `PATCH`, and `DELETE /api/v1/seller/listings/{listing}`: require authentication and ownership authorization.
- `POST .../publish` and `POST .../mark-sold`: require authentication and ownership authorization.
- Image management endpoints: require authentication and ownership authorization.
Non-owner access returns `404`.

Endpoints:
- `GET /api/v1/seller/listings`
- `POST /api/v1/seller/listings`
- `GET /api/v1/seller/listings/{listing}`
- `PATCH /api/v1/seller/listings/{listing}`
- `DELETE /api/v1/seller/listings/{listing}`
- `POST /api/v1/seller/listings/{listing}/publish`
- `POST /api/v1/seller/listings/{listing}/mark-sold`

### Seller listing collection
- `GET /api/v1/seller/listings` is paginated.
- Default `per_page`: 15
- Maximum `per_page`: 50
- `page`: page number, minimum 1
- No status filter is supported; all owned listings are returned.
- Response: paginated `{ "data": [...], "links": {...}, "meta": {...} }`

### Create listing
- Request fields:
  - `category_id`: required, integer, selectable active category
  - `title`: required, string, max 120
  - `description`: required, string, max 5000
  - `price_type`: required, `fixed`, `free`, or `contact`
  - `price`: required when `price_type=fixed`; must be null for `free` or `contact`; positive, max 99999999.99, max 2 decimals
  - `municipality`: nullable, string, max 100
  - `barangay`: nullable, string, max 100
- Server-managed fields:
  - `user_id`
  - `status` (default `draft`)
  - `published_at`
  - `currency` (`PHP`)
  - `province` (`Camarines Sur`)
- Success: `201` with `ListingDetailResource`

### Update listing
- `PATCH` only.
- All fields are `sometimes`.
- Protected fields are rejected with `422` if submitted.
- Active listings cannot clear or empty `municipality`.
- Price consistency is validated against the existing listing when `price_type` is omitted.
- Success: `200` with `ListingDetailResource`

### Lifecycle
- `publish`: draft to active only; sets `published_at`; returns `200` or `409`
- `mark-sold`: active to sold only; preserves `published_at`; returns `200` or `409`
- Repeated or invalid transitions return `409`.

### Delete
- Soft delete.
- Returns `204`.
- Repeated delete returns `404`.
- Soft-deleted listings retain their image database records and physical files.
- The current implementation does not provide restore or hard-delete endpoints.

## F. Image Management API

All image endpoints require `auth:sanctum` and owner authorization.

Endpoints:
- `POST /api/v1/seller/listings/{listing}/images`
- `DELETE /api/v1/seller/listings/{listing}/images/{image}`
- `POST /api/v1/seller/listings/{listing}/images/{image}/primary`
- `PATCH /api/v1/seller/listings/{listing}/images/reorder`

### Upload
- Content-Type: `multipart/form-data`
- Field: `images[]`
- Allowed formats: JPEG, JPG, PNG, WebP
- Maximum 5 MB per image
- Maximum 8 images per listing
- First uploaded image becomes primary only if no primary image exists
- Returns `201` with `ListingDetailResource`

Browser upload guidance:
- Use `FormData`.
- Append files under `images[]`.
- Do not manually set `Content-Type`; let the browser generate the multipart boundary.
- Include credentials for cookie-authenticated requests.
- Include the CSRF header for state-changing cookie-authenticated requests.
- Postman users must manually select JPEG, PNG, or WebP files smaller than 5 MB for each blank file field.

### Delete
- Deletes database record, then physical file.
- Returns `204`.
- If deleted image was primary, promotes the remaining image with lowest `sort_order`.
- If no images remain, listing has no primary image.
- Physical-file deletion failure is logged and does not undo the database deletion.

### Set primary
- Clears all primary flags, then sets the selected image.
- Returns `200` with `ListingDetailResource`.

### Reorder
- Request body:
  ```json
  {
    "image_ids": [3, 1, 2]
  }
  ```
- IDs must exactly match the listing's current image IDs.
- Duplicates, missing IDs, and foreign IDs return `422`.
- Updates `sort_order` to `0..n-1`.
- Does not change primary-image state.
- Returns `200` with `ListingDetailResource`.

## G. Standard error responses

- `401`: unauthenticated
  ```json
  {
    "message": "Unauthenticated."
  }
  ```
- `404`: not found or non-owner access
  ```json
  {
    "message": "Not Found"
  }
  ```
- `409`: invalid lifecycle transition
  ```json
  {
    "message": "Conflict"
  }
  ```

Token logout can also return `409` with:
```json
{
  "message": "A personal access token is required."
}
```
- `422`: validation error
  ```json
  {
    "message": "The given data was invalid.",
    "errors": {
      "field": ["Validation message."]
    }
  }
  ```
- `500`: unexpected server error; production responses must not expose stack traces or internal paths.

Local debug mode may include additional diagnostic information. Production must use `APP_DEBUG=false`.

## H. Data structure reference

### Listing summary
```json
{
  "id": 1,
  "title": "Used Bike",
  "price_type": "fixed",
  "price": "100.00",
  "currency": "PHP",
  "municipality": "Naga City",
  "province": "Camarines Sur",
  "status": "active",
  "primary_image_url": "http://localhost:8000/storage/listings/1/primary.jpg",
  "created_at": "2026-...",
  "category": {
    "id": 1,
    "name": "Vehicles",
    "slug": "vehicles"
  }
}
```

### Listing detail
```json
{
  "id": 1,
  "title": "Used Bike",
  "description": "Good condition.",
  "price_type": "fixed",
  "price": "100.00",
  "currency": "PHP",
  "municipality": "Naga City",
  "barangay": "Concepcion Grande",
  "province": "Camarines Sur",
  "status": "active",
  "published_at": "...",
  "created_at": "...",
  "updated_at": "...",
  "category": {
    "id": 1,
    "name": "Vehicles",
    "slug": "vehicles"
  },
  "user": {
    "id": 1,
    "name": "Test User"
  },
  "images": [
    {
      "id": 1,
      "url": "http://localhost:8000/storage/listings/1/primary.jpg",
      "sort_order": 0,
      "is_primary": true
    }
  ]
}
```

### Category
```json
{
  "id": 1,
  "name": "Vehicles",
  "slug": "vehicles",
  "description": null,
  "parent_id": null,
  "sort_order": 0,
  "children": [
    {
      "id": 2,
      "name": "Cars",
      "slug": "cars",
      "description": null,
      "parent_id": 1,
      "sort_order": 0,
      "children": []
    }
  ]
}
```

### Listing image
```json
{
  "id": 1,
  "url": "http://localhost:8000/storage/listings/1/primary.jpg",
  "sort_order": 0,
  "is_primary": true
}
```

### Pagination metadata
Laravel pagination includes `data`, `links`, and `meta`.

## I. Production configuration checklist

Required production settings:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` set to the production backend URL
- CORS allowed origins set to the production frontend origin
- Sanctum stateful domains set to the production frontend domain
- Session cookie domain configured for the production domain
- Secure cookies enabled when using HTTPS
- `php artisan storage:link` executed
- Uploaded files stored on the public disk
- Database backups configured
- HTTPS enabled for frontend and backend

Unresolved deployment values:
- production domain
- hosting provider
- frontend origin
- cookie domain
- storage backup strategy

## J. Frontend integration notes for Gian

Recommended integration sequence:

1. Configure `NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1`.
2. Fetch public categories.
3. Fetch public listings.
4. Implement search, filters, sorting, and pagination.
5. Implement listing details and image gallery.
6. Integrate authentication.
7. Implement seller dashboard.
8. Implement listing creation and editing.
9. Implement publish and mark-sold actions.
10. Implement image upload, deletion, primary selection, and reordering.

Authentication decision required:
- Existing frontend-compatible cookie-based Sanctum auth is available under `/api/v1/auth`.
- Token-based lab endpoints are available under `/api`.
- Choose one approach before production integration.

Image integration:
- Use `primary_image_url` for cards.
- Use `images[].url` for galleries.
- No client-side URL normalization is required.
- Handle null `primary_image_url`.

## Manual API testing references

- Sanctum lab collection: `backend/postman/Craigslist Upgrade - Sanctum Lab.postman_collection.json`
- Seller API collection: `backend/postman/Craigslist Upgrade - Seller API.postman_collection.json`
- Sanctum documentation: `backend/postman/README.md`
- Seller documentation: `backend/postman/Seller API README.md`
- The seller collection requires manually selecting valid JPEG, PNG, or WebP files smaller than 5 MB for each blank `images[]` field.

## K. Location API

Public endpoint:
- GET /api/v1/locations
- Authentication: public
- Success: 200
- Response: { "data": [ { "municipality", "official_name", "psgc_code", "barangays": [] } ] }

Rules:
- Province is server-managed as Camarines Sur.
- Approved municipalities are the seven Phase 1 LGUs.
- Naga City is the API-facing municipality label; its official_name is City of Naga.
- Draft municipality and barangay are optional.
- Publishing requires a non-empty approved municipality.
- Barangay is optional, but when supplied it must belong to the selected municipality.
- Barangay names may repeat across LGUs and are validated within their municipality.
- The endpoint does not return addresses or coordinates.

## Remaining risks or unresolved questions

1. Publishing requires an approved municipality from the seven-LGU, 173-barangay Phase 1 allow-list; a supplied barangay must belong to that municipality.
2. Sold listings remain editable and image-manageable by design.
3. Soft-deleted listings retain image database records and physical files. The current implementation does not provide restore or hard-delete endpoints.
4. Hard-delete file cleanup is deferred because no hard-delete workflow exists.
5. Concurrency safeguards are best-effort without migrations. SQLite serializes writes, while MySQL/PostgreSQL may allow concurrent writes; unique constraints for primary image or sort order are not currently enforced.
6. Postman collections are manually executable; no Postman installation is assumed.
7. Frontend authentication uses cookie-based Sanctum under `/api/v1/auth/*`; unversioned `/api/*` token endpoints are a separate instructor lab.
8. Production domain and deployment configuration are not finalized.

## Final implementation acceptance criteria

Phase 1C.10 is acceptable when:
- `backend/docs/api-contract.md` exists.
- All documented routes match `routes/api.php`.
- All documented request fields match validation classes.
- All documented response examples match API resources and controllers.
- Authentication statements match middleware and Sanctum configuration.
- Image examples match actual validation and controller behavior.
- Production requirements are documented without invented domains or credentials.
