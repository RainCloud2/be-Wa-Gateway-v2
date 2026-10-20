# WA Chatbot IT Support + Ticketing (CMS)

Laravel 12 + Livewire dashboard for a WhatsApp-based IT support chatbot.
Employees chat to a single WhatsApp number; an AI assistant (Google Gemini)
tries to solve the issue, and after several failed attempts offers to open a
ticket. When the employee agrees, a ticket is created and the conversation is
handed over to the IT team through this dashboard.

The WhatsApp transport lives in a separate Node.js service
(`wagateway-v2`, Baileys). This Laravel app holds all business logic, data,
and the dashboard; the gateway only forwards incoming messages and sends
outgoing ones on request.

## Requirements

- PHP 8.2+ (tested on 8.4), Composer
- Node.js 20+ / npm (for the front-end assets and the gateway)
- SQLite (default) or MySQL

## Setup

```bash
composer install
npm install
cp .env.example .env        # copy .env.example .env   (Windows)
php artisan key:generate
php artisan migrate --seed  # migrations + roles + default settings
npm run build
```

### Seeded accounts (password: `password`)

| Email | Role | Access |
|---|---|---|
| `superadmin@gmail.com` | super-admin | everything |
| `admin@gmail.com` | admin | all WA modules + settings |
| `technician@gmail.com` | technician | tickets + WhatsApp status |
| `user@gmail.com` | user | limited |

## Environment variables

All new variables (see `.env.example` for the full list):

| Variable | Default | Description |
|---|---|---|
| `WA_DRIVER` | `log` | `log` (DB only, no WhatsApp) or `gateway` |
| `GATEWAY_TOKEN` | — | Shared secret; same value as the gateway's `GATEWAY_TOKEN` |
| `GATEWAY_URL` | `http://127.0.0.1:3001` | Base URL of the wa-gateway |
| `GATEWAY_TIMEOUT` | `10` | Timeout (seconds) for gateway requests |
| `WA_DEBOUNCE_MIN_SECONDS` / `WA_DEBOUNCE_MAX_SECONDS` | `3` / `6` | Random wait before the AI replies (merges message bursts, feels less robotic) |
| `WA_HISTORY_LIMIT` | `20` | Max previous messages sent to Gemini |
| `WA_MAX_ATTEMPTS` | `3` | Fallback for the `max_attempts` setting |
| `WA_OUTGOING_TRIES` | `3` | Retries for outgoing messages |
| `GEMINI_API_KEY` | — | Google AI Studio API key |
| `GEMINI_MODEL` | `gemini-3.5-flash-lite` | Never hardcoded; set your model here |
| `GEMINI_BASE_URL` | `.../v1beta` | Gemini REST base URL |
| `GEMINI_TIMEOUT` / `GEMINI_RETRIES` | `25` / `2` | Gemini request timeout / retries |

> The `log` driver stores everything in the database and never contacts
> WhatsApp, so the whole flow can be tested locally. Switch to `gateway` only
> once the gateway service is running.

## Running the app

In development, one command starts the web server, queue worker, log viewer
and Vite together:

```bash
composer dev
```

Or run the pieces individually:

```bash
php artisan serve                                   # web + dashboard
php artisan queue:work                              # process incoming messages, AI, sending
php artisan schedule:work                           # auto-close + unassigned reminders
npm run dev                                          # Vite assets
```

The chatbot pipeline is queue-driven: incoming messages, the Gemini call, and
outgoing delivery all run in jobs. **A queue worker must be running** for real
WhatsApp traffic. The simulator (below) is synchronous and works without one.

### Scheduler

| Command | Frequency | Purpose |
|---|---|---|
| `wa:auto-close-resolved` | hourly | Closes RESOLVED tickets with no reply after `auto_close_resolved_hours` |
| `wa:remind-unassigned` | every 5 min | Reminds the IT team about OPEN tickets unassigned for 15+ minutes |

## Dashboard

Sidebar → **WhatsApp**:

- **Tiket** — filterable ticket list (status/category/priority/assignee), unread
  badges, and a "Ambil Tiket" button.
- **Whitelist** — CRUD for the employee phone whitelist. Deleting a number is a
  soft delete: it disappears from the whitelist but its tickets and chat history
  are kept. Re-adding the same number restores the existing record.
- **Status WhatsApp** — gateway connection status, last QR, and logout/re-link.
- **Simulator** — admin-only chat simulator (see below).
- **Pengaturan** — AI system prompt, max attempts, business hours, IT
  notification numbers, auto-close and session-timeout windows.

### Ticket detail

Chat view with colour-coded bubbles (employee / AI / technician / system),
the AI summary, a reply box (sent to the employee), status buttons, and a
"Gagal" indicator for messages the gateway could not deliver. New messages
appear via lightweight polling.

## Simulator

Because testing with a single WhatsApp number is awkward, the **Simulator**
page provides an admin-only sandbox:

1. pick a simulation persona (or create another one), and
2. type a message as if it came from that persona.

Every persona gets a reserved dummy number starting with `000`
(`000000000001`, `000000000002`, ...), which cannot collide with a real
WhatsApp number or a registered employee. Real employee numbers cannot use
this prefix, and simulated numbers are excluded from the gateway whitelist.

The message is pushed through **exactly the same pipeline** as real traffic
(whitelist → session → AI/ticket logic) but processed synchronously, so the bot
reply appears immediately and no queue worker is needed. Simulated conversations
and tickets are stored in the database so the flow can be inspected, but they
are never delivered to WhatsApp and never trigger IT notifications. Simulated
tickets appear in the ticket dashboard with a **SIMULASI** badge. "Reset Chat"
deletes the persona's messages, sessions and simulated tickets, while the
persona itself remains available for repeat testing. Real employee data is not
touched.

## Connecting to the gateway

1. Start the gateway (`wagateway-v2`) and set its `GATEWAY_TOKEN`, then set the
   same value in this app's `GATEWAY_TOKEN`.
2. Point the gateway's `BACKEND_URL` at this app (default `http://localhost:8000`).
3. In this app's `.env`, set `WA_DRIVER=gateway` and `GATEWAY_URL` to the
   gateway's address.
4. Run `php artisan queue:work` and `php artisan schedule:work`.
5. Check **Status WhatsApp** in the dashboard; scan the QR shown there (or
   printed by the gateway) if it is not yet connected.

**Contract (both directions use the `X-Gateway-Token` header):**

- Gateway → Laravel: `POST /api/wa/incoming` (incoming messages), `POST /api/wa/status` (connection status), `GET /api/wa/whitelist` (active numbers used by the gateway to drop non-whitelisted senders).
- Laravel → Gateway: `POST {GATEWAY_URL}/send`, `GET {GATEWAY_URL}/status`, `POST {GATEWAY_URL}/logout`.

If the gateway is disconnected (HTTP `503`), outgoing messages are retried with
backoff and marked `failed` when exhausted; failures are visible in the ticket
view.

### Local gateway process control (Windows/Herd)

For local Windows development the dashboard can start, stop, and restart the
gateway process. This is intentionally **disabled in production**; production
gateways must be managed by systemd, PM2, or Docker.

Set these values in `.env`:

```env
WA_GATEWAY_PROCESS_ENABLED=true
WA_GATEWAY_PATH=C:/path/to/wagateway-v2
WA_GATEWAY_NODE="C:/Program Files/nodejs/node.exe"
WA_GATEWAY_SCRIPT=src/index.js
WA_GATEWAY_START_TIMEOUT=10
```

The controls appear on **Status WhatsApp** and the WhatsApp card on the
dashboard for users with the `wa.update` permission. The process PID is stored in
`storage/app/wa-gateway.pid`, and output logs go to
`storage/logs/wa-gateway.out.log` / `storage/logs/wa-gateway.err.log`.

The Laravel process must be allowed to start Node (Herd usually runs as the same
Windows user). Gateways started manually with `npm start` are not managed by the
dashboard, and the stop/restart actions will not terminate them.

When process control is enabled, logging out of WhatsApp also stops the local
gateway. Click **Nyalakan** afterwards to start a fresh session and obtain a new
QR code.

Keep `WA_GATEWAY_PROCESS_ENABLED=false` on the VPS. There, let systemd/PM2 manage
the gateway so it restarts automatically and survives reboots.

### Local queue worker control (Windows/Herd)

Incoming WhatsApp messages and outgoing replies are processed by Laravel queue
jobs. Without a queue worker, messages stay in the `jobs` table and the bot does
not reply. For local Windows development the dashboard can start, stop, and
restart the worker from **Status WhatsApp** or the dashboard WhatsApp card.

Set these values in `.env`:

```env
WA_QUEUE_PROCESS_ENABLED=true
WA_QUEUE_TRIES=3
WA_QUEUE_START_TIMEOUT=10
WA_QUEUE_HEARTBEAT_STALE_SECONDS=60
```

The worker runs detached through `scripts/wa-queue-worker-run.ps1` and writes to
`storage/logs/wa-queue-worker.out.log` / `.err.log`. It also writes a heartbeat
through `Queue::looping`, so the dashboard can detect a stopped or stale worker
even when it was started outside the dashboard. Pending and failed job counts are
shown next to the controls.

Keep `WA_QUEUE_PROCESS_ENABLED=false` on the VPS. There, run the queue worker and
scheduler through systemd/Supervisor, for example:

```ini
[Unit]
Description=WA CMS queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/wagateway-v2-cms
ExecStart=/usr/bin/php artisan queue:work --sleep=1 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Use a second unit (or cron) for `php artisan schedule:work` / `schedule:run`.
Without these services, messages will queue and the AI will not reply.

## How the flow works

1. Incoming message → dedupe by `wa_message_id`.
2. Unknown / inactive number → **silently ignored** (no reply, no session). When the gateway is used, these messages are already dropped there via the whitelist endpoint and never reach the API.
3. Known number → get/create session; reset attempts after `session_timeout_hours`.
4. `mode=ai` → debounced AI turn via Gemini (structured JSON). Attempt counting
   is handled in code, not by the AI. After `max_attempts` (or when the
   employee asks for a human) the bot offers a ticket.
5. Employee accepts → ticket created (`TKT-0001`), session switches to
   `mode=human`, employee + IT team are notified.
6. Technician handles the ticket in the dashboard. Marking it **RESOLVED** asks
   the employee to confirm; a "solved" reply closes the ticket and returns the
   session to AI mode, otherwise it reopens.
7. `mode=human` messages are stored and shown as unread; the AI does not reply.

Non-text messages (audio, stickers, etc.) get a short "text only" reply. `image`
messages are treated as their caption text, and when the gateway forwards the
image (`FORWARD_IMAGES=true`) the base64 is sent to Gemini as multimodal input.

## Testing

```bash
composer test          # or: php artisan test
php artisan test tests/Feature/Wa
```

Feature tests (with `Http::fake()` for Gemini and the gateway) cover: bad token
→ 401, dedupe, unregistered numbers, attempt counting up to the ticket offer,
accept/decline, human mode not triggering AI, the RESOLVED confirmation flow,
Gemini failure fallback, and gateway `503` marking a message as failed.

`phpunit.xml` forces `WA_DRIVER=log` so tests never touch WhatsApp. See
`docs/AUDIT.md` for the original codebase/contract audit.
