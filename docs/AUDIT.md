# Phase 0 Audit — WA Chatbot IT Support

Audit of the existing Laravel template (`wagateway-v2-cms`) and the WhatsApp
gateway (`wagateway-v2`) before building the chatbot + ticketing system.

## 1. Laravel template inventory

| Item | Finding |
|---|---|
| Framework | Laravel 12 (`laravel/framework ^12.0`) |
| PHP | `^8.2` required; runtime in this environment is **PHP 8.4.24** |
| Vendor | **Not installed** at audit time — `composer install` required |
| Frontend | Livewire 3 + Livewire Volt 1.7, Blade. UI theme is **Bootstrap 5 "Kaiadmin"** served from `public/assets` |
| Other packages | `maatwebsite/excel`, `mallardduck/blade-lucide-icons`, `spatie/laravel-permission ^8.3` |
| Dev packages | Breeze 2.4, Pint, Pail, PHPUnit 11, Collision |
| Build | Vite 7 + Tailwind 3 (`npm run dev` / `npm run build`) |

### Routing
- `routes/web.php`: full-page Livewire components. No `routes/api.php` exists and API
  routing is **not registered** in `bootstrap/app.php` — must be added.
- `routes/auth.php`: auth screens via Volt.
- `bootstrap/app.php` registers only the `permission` middleware alias; no custom
  middleware or middleware groups.
- Authorization used in routes: `middleware('permission:module.action')`.

### Structure & conventions
- Controllers: minimal (`App\Http\Controllers\Controller` + `Auth\VerifyEmailController`).
  Almost all logic lives in `App\Livewire\*`.
- Livewire CRUD pattern (see `app/Livewire/Master/Bank.php`):
  - `#[Layout('layouts.admin')]` on the component; view in `resources/views/livewire/**`.
  - `AuthorizesRequests` + `$this->authorize('module.action')`.
  - Bootstrap modals triggered via `$this->dispatch('show-modal')` and wired in
    `@push('scripts')` with `bootstrap.Modal.getOrCreateInstance`.
  - Toast notifications via `$this->dispatch('notify', type:, message:)` handled in
    `layouts/admin.blade.php` through `$.notify`.
  - Pagination via `App\Livewire\Traits\WithPerPagePagination` + `<x-pagination>`.
  - Icons via `mallardduck/blade-lucide-icons` (`<x-lucide-* />`).
- Models: `User` (uses spatie `HasRoles`), `Worker`, `Bank`.
- Migrations: standard Laravel users/cache/jobs, spatie permission tables, banks, workers.
- Seeders: `DatabaseSeeder` (truncates role/permission pivots then seeds roles + 3 users),
  `PermissionSeeder`, `BankSeeder`, `WorkerSeeder`.

### Auth / roles
- `spatie/laravel-permission`.
- Roles: `super-admin`, `admin`, `user`. Permissions named `module.action` with modules
  `permission`, `role`, `user`, `bank`, `worker` and actions
  `create|read|update|delete|import|export`.
- `AppServiceProvider::boot` grants `Gate::before` bypass for `super-admin`.
- `User::hasGroupPermission($group)` drives sidebar visibility.
- Seeded accounts: `superadmin@gmail.com`, `admin@gmail.com`, `user@gmail.com`
  (password `password`).

### Settings system
- **None exists.** A new `settings` table + model must be created.

### Testing
- PHPUnit 11, `phpunit.xml` uses sqlite `:memory:`, `QUEUE_CONNECTION=sync`,
  `CACHE_STORE=array`, `SESSION_DRIVER=array`.
- Tests live in `tests/Feature` and use `RefreshDatabase` + `PermissionSeeder`.
- `composer test` clears config then runs `artisan test`.
- `composer dev` already launches `php artisan serve`, **`queue:listen`**, `pail`, and Vite.

## 2. Existing WhatsApp / gateway feature

**None.** A repo-wide search for `whatsapp|gateway|wa_message|X-Gateway|baileys`
matched only font glyph names in `public/assets`. There are no WA tables, models,
controllers, pages, or settings. Everything must be built new.

### Reuse (not rebuild)
- Livewire + `layouts.admin` + Kaiadmin CSS/JS.
- spatie roles/permissions (extend, don't replace).
- Modal / toast / pagination / Excel conventions.
- `WithPerPagePagination`, `<x-pagination>`, `<x-lucide-*>`.
- `Gate::before` super-admin bypass and `hasGroupPermission` sidebar gating.
- `composer dev` queue worker.

### New
- Migrations: `employees`, `settings`, `chat_sessions`, `tickets`, `messages`,
  `sequences` (race-safe ticket codes).
- Models, WA driver abstraction (`log`/`gateway`), `GeminiClient`, middleware,
  jobs, services, API routes, Livewire dashboard pages, scheduler tasks.

## 3. Gateway contract verification

Source of truth read: `wagateway-v2/README.md`, `src/server.js`, `src/backendClient.js`,
`src/config.js`, `.env.example`. The gateway **exists** and the contract **matches the
task specification**. Confirmed details:

- **Auth**: header `X-Gateway-Token`; wrong token → `401`. Gateway compares with a
  constant-time hash (`safeEqual`). Shared secret env var is `GATEWAY_TOKEN`.
- **A. Incoming** `POST {BACKEND_URL}/api/wa/incoming` with JSON
  `{wa_message_id, from, push_name, type, text, timestamp, image_base64, image_mime}`.
  `type` ∈ `text|image|other`. Backend may reply any `2xx`; body is ignored.
  **The gateway retries up to 5× with exponential backoff + jitter, then drops the
  message** → server-side dedupe on `wa_message_id` is mandatory.
- **B. Outgoing** `POST /send` body `{to, text}`:
  - valid → `202 {"status":"queued","queue_size":N}`
  - invalid (`to` must be 8–15 digits, `text` non-empty) → `422`
  - disconnected → `503 {"error":"wa_disconnected"}`
- **C. Status** `POST /api/wa/status` body `{status, qr, phone}` where
  `status` ∈ `open|connecting|close|logged_out|qr`. Best-effort; failures are warnings.
- **D. Extra** (all token-protected unless noted):
  - `GET /health` — **no token** — `{ok, wa_status, queue_size, uptime}`
  - `GET /status` — `{wa_status, phone, qr, connected, queue_size, uptime}`
  - `POST /logout` — logs out and clears `auth_state/`.

### Differences vs. the task contract
- The task's incoming response `200 {"status":"queued"}` is compatible; the gateway
  never reads the response body.
- No other divergence found. The gateway `/send` path is relative to `GATEWAY_URL`
  (default `http://127.0.0.1:3001`); status/health/logout hang off the same base.
- Gateway test-only mode `ALLOW_SELF_CHAT=true` treats the "Message yourself" chat as
  incoming — useful given the single-number constraint; the Laravel simulator covers
  the same need without touching the gateway.

## 4. Gemini API verification

- Target model **Gemini 3.5 Flash-Lite**; verified endpoint string is
  **`gemini-3.5-flash-lite`** (model name must come from `GEMINI_MODEL`, never hardcoded).
- Structured output confirmed for this model via
  `generationConfig.responseMimeType: "application/json"` + `generationConfig.responseSchema`.
- REST: `POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`,
  auth header **`x-goog-api-key`**. `systemInstruction` supported. Multimodal input via
  `contents[].parts[].inlineData{mimeType, data}` (base64).
- `responseSchema` must be a supported JSON-Schema subset (types string/number/integer/
  boolean/object/array; enums via `enum`/`format`).

## 5. Assumptions & defaults chosen

1. `WA_DRIVER` defaults to **`log`** in `.env.example` so the whole flow runs without
   WhatsApp; switch to `gateway` for real integration.
2. Roles: reuse spatie. Keep `super-admin`/`admin`, add **`technician`**. Extend
   `PermissionSeeder` with modules `ticket|employee|setting|wa`.
3. Ticket codes are generated through a dedicated `sequences` table with a locked
   transaction to be race-safe.
4. `tickets` gets an extra nullable `reminder_sent_at` column to send the 15-minute
   unassigned reminder exactly once.
5. New Livewire pages are grouped under a "WhatsApp" sidebar section, gated by
   permissions, following the existing Kaiadmin/Livewire conventions.
6. Outgoing bot/tech messages flow through the driver abstraction and are persisted with
   `delivery_status` so failures are retryable and visible; internal notes (`is_internal`)
   are never sent.
