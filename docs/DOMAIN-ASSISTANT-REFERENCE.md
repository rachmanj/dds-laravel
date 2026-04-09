# Domain Assistant — implementation reference (portable)

This document describes how the **Domain Assistant** feature is built in the DDS Laravel application. Use it as a **blueprint** when implementing a similar “scoped AI chat + tools + audit” feature in **other Laravel (11+) projects**.

---

## 1. Goals


| Goal                          | Approach                                                                                                                                           |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Accurate domain answers**   | The LLM must **not** invent SQL or raw tables; it calls **registered tools** that run **your** Eloquent queries with **your** authorization rules. |
| **Same visibility as the UI** | Tool implementations reuse (or mirror) list filters: **roles**, **locations**, **“show all records”** toggles, etc.                                |
| **Auditability**              | Persist **per-request logs** (success/error, duration, tools invoked, optional IP/UA).                                                             |
| **Multi-session UX**          | Support **multiple conversation threads** per user (not one implicit thread).                                                                      |
| **Ops / governance**          | Admin-only **report** over request logs with filters.                                                                                              |
| **Optional Telegram DM**      | Same service stack via **webhook** + job; list scope centralized in `**DomainAssistantListScope`** (see §14).                                      |


---

## 2. Architecture overview

```
┌─────────────┐     POST /assistant/chat      ┌──────────────────────────┐
│   Browser   │ ────────────────────────────► │ DomainAssistantController │
└─────────────┘     JSON or SSE (stream)      └─────────────┬────────────┘
                                                            │
                    ┌───────────────────────────────────────┼───────────────────────┐
                    ▼                                       ▼                       ▼
         AssistantConversationManager              DomainAssistantService     AssistantRequestLog
         (session + DB threads)                    (OpenRouter + tool loop)   (audit row)

                    ▼
         DomainAssistantDataService
         (permission-scoped Eloquent; tool implementations)
```

- **OpenRouter** (or any OpenAI-compatible HTTP API) runs **chat completions** with `**tools`** (function calling).
- Each tool maps to a **method** on a dedicated **data service** class that returns **arrays** (serialized to JSON for the model).
- **Streaming**: optional SSE when tools are **off** and config allows streaming (project-specific safety choice).

**Telegram (optional):** `TelegramWebhookController` receives Bot API updates, validates the path secret, then runs `**ProcessTelegramDomainAssistantMessage`** (sync or queued per config) → same `**DomainAssistantService`** as `**POST /assistant/chat`**.

---

## 3. Permissions and routes

- **Permission** (Spatie example): `access-domain-assistant` — assign via roles.
- **Route group**: `auth`, `active.user` (or your equivalents), `can:access-domain-assistant`.
- **Throttle** chat POST if needed (e.g. `throttle:30,1`).

**Typical routes**


| Method | Path                                               | Purpose                                                 |
| ------ | -------------------------------------------------- | ------------------------------------------------------- |
| GET    | `/assistant`                                       | Chat UI                                                 |
| POST   | `/assistant/chat`                                  | Send message (JSON or SSE)                              |
| POST   | `/assistant/clear`                                 | Delete current conversation (project-defined behaviour) |
| GET    | `/assistant/conversations`                         | List threads + active id                                |
| POST   | `/assistant/conversations`                         | New thread                                              |
| GET    | `/assistant/conversations/{conversation}/messages` | Load messages                                           |
| PATCH  | `/assistant/conversations/{conversation}/select`   | Set active thread in session                            |
| DELETE | `/assistant/conversations/{conversation}`          | Delete thread                                           |


**Scoped route model binding** (recommended): register a `Route::bind` for `{conversation}` so the resolved model is always `where('user_id', auth()->id())` → wrong id yields **404**, not 403 leakage.

---

## 4. Configuration (env + config)

Use a dedicated config block, e.g. `config/services.php` → `domain_assistant`:

- `enabled` — master switch.
- `openrouter` / provider: **API key**, **base URL**, **chat model**, **timeout**.
- `tools_enabled` — if `false`, you may allow **streaming** without tool loops (simpler, but no live DB tools).
- `streaming_enabled` — SSE path when tools off.
- `daily_user_message_limit` — optional `0` = unlimited.

**Never** commit API keys; document in `.env.example`.

---

## 5. Database

**Conversations**

- `assistant_conversations`: `user_id`, optional `title`, timestamps.
- `assistant_messages`: `assistant_conversation_id`, `role` (`user`/`assistant`), `content`, timestamps.

**Session key** for “active” conversation id, e.g. `domain_assistant.conversation_id`.

**Request logs**

- `assistant_request_logs`: `user_id`, `assistant_conversation_id`, `status` (`success`/`error`), `tools_invoked` (JSON array), `show_all_records` (bool), `user_message_length`, `**user_message`** (full question text, max 10k chars; older rows may be null), `duration_ms`, `error_summary`, `ip_address`, `user_agent`, optional `telegram_chat_id`, timestamps.

Indexes: e.g. `(user_id, created_at)` on conversations; `(user_id, created_at)` or `(status, created_at)` on logs for admin reports.

---

## 6. Core services

### 6.1 Conversation manager

- **Resolve** active conversation: session id → load owned row; else create new and store in session.
- **Optional override**: `conversation_id` in chat request must be validated with `exists:assistant_conversations,id` **scoped to current user**.
- **Append exchange**: save user + assistant messages; set **title** on first user message (e.g. `Str::limit(trim($text), 80)`).
- **Clear / delete**: delete rows or only messages — project choice; keep session consistent.

### 6.2 Domain assistant service (LLM + tools)

- Build **messages**: system prompt (scope + rules) + history + new user message.
- Call provider API in a **loop** until no `tool_calls` or max iterations.
- **Execute tool** by name: map to data service methods; record tool names in `lastToolsInvoked` for logging.
- **System prompt** should explicitly say:
  - When the user names a **supplier/vendor**, pass the right tool arguments (e.g. `supplier_query` for invoices).
  - Do not invent IDs or rows.

### 6.3 Data service (tools)

Implement **one method per tool**, returning **arrays** (or `['error' => '...']`).

**DDS-specific patterns**

- **Invoice list scope**: reuse the same **location / role** rules as invoice index (`invoicesVisibleQuery`).
- `**search_invoices`**: parameters such as `status`, `limit` (cap e.g. 20), `date_from` / `date_to` (max window, e.g. 90 days), and `**supplier_query`** — filter with `whereHas('supplier', …)` on **name** and **vendor code**, with **LIKE** wildcards escaped for user input where appropriate.

**Other tools** (examples): `get_domain_summary`, `search_additional_documents`, `search_distributions`, `search_reconcile_records`, `**search_suppliers`** — substring match on name/SAP code with `**LIKE` metacharacters escaped**; **multi-word queries** require **each** word to match (narrows “Mitra Inti …” style questions); single tokens that look like **SAP codes** also match `**sap_code` exactly** (case-insensitive) and rank first.

---

## 7. Tool definitions (OpenAPI-style for the provider)

Each tool needs:

- `name` (snake_case),
- `description` (what/when to use — **critical** for correct model behaviour),
- `parameters.properties` with types and short descriptions.

Register them in the chat completions payload in the format your provider expects (OpenAI-compatible: `tools: [{ type: 'function', function: { name, description, parameters } }]`).

---

## 8. Controller responsibilities

- Gate feature: `config('services.domain_assistant.enabled')` and API key present → else redirect or 503.
- **Daily limit**: count user messages since **start of day** across conversations → 429 if exceeded.
- `**show_all_records`**: only honour if user **can** `see-all-record-switch` (boolean).
- **Chat**: validate `message`, optional `conversation_id`, `stream`, `show_all_records`; resolve conversation; call service; persist messages; write `AssistantRequestLog`. `**show_all_records`** is resolved via `**App\Support\DomainAssistantListScope::fromWebRequest`** (must match `see-all-record-switch`).
- **Stream**: same validation; stream tokens; on completion append exchange and log (same as non-stream).

---

## 9. UI (minimal expectations)

- Chat area + input; optional **terminal** styling.
- **Thread list**: load conversations on init; if empty, `POST` create one; switching thread = `PATCH` select + reload messages.
- Send `conversation_id` with each message.
- **CSRF** + `Accept: application/json` / SSE headers for fetch.

---

## 10. Admin report

- **Route**: e.g. `/admin/assistant-report`, middleware `**role:superadmin|admin`** (or your admin role names).
- **Query**: `AssistantRequestLog::query()->with(['user', 'conversation'])` + filters (user id, status, date range) + pagination (**per page** 10 / 25 / 50 / 100; query string preserved).
- **View**: time (UTC), user, **question** (`user_message` — full text stored for new requests; older rows may be empty), status, duration, tools, error snippet, IP, `**telegram_chat_id`** when the request came from Telegram.

---

## 11. Testing (suggested)

- Guest → redirect/login; user without permission → 403.
- Chat with **HTTP fake** on provider API → assert assistant message + DB messages + log row.
- **Threads**: create conversation, list, select; **other user** cannot access messages (404).
- **Admin report**: admin 200; non-admin 403.
- **Data service**: `search_invoices` with `supplier_query` returns only matching supplier’s invoices (feature test with seeded suppliers + invoices).
- **Telegram webhook**: wrong secret → 404; linked user + private text → job dispatched (see `TelegramDomainAssistantWebhookTest`).

---

## 12. Porting checklist (another Laravel project)

1. [ ] Add permission + assign to roles.
2. [ ] Migrations: conversations, messages, request logs.
3. [ ] Config + `.env.example` entries.
4. [ ] `DomainAssistantService` + provider client (HTTP) + tool loop.
5. [ ] `DomainAssistantDataService` (or split by domain) — **every** query scoped to **auth rules**.
6. [ ] Controller + routes + optional `Route::bind` for conversation.
7. [ ] Blade UI + JS (threads + chat + optional stream).
8. [ ] `AssistantRequestLog` on success/failure.
9. [ ] Admin report controller + view + menu item.
10. [ ] Feature tests (auth, chat, threads, admin, supplier filter if applicable).
11. [ ] Document tool parameters in **decision log** and **architecture** doc.
12. [ ] (Optional) Telegram webhook + job + `**DomainAssistantListScope`** for parity with web list scope.

---

## 13. DDS file map (this repository)


| Area         | Path                                                                                                                                                            |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Controller   | `app/Http/Controllers/DomainAssistantController.php`                                                                                                            |
| Services     | `app/Services/DomainAssistantService.php`, `DomainAssistantDataService.php`, `AssistantConversationManager.php`                                                 |
| Models       | `app/Models/AssistantConversation.php`, `AssistantMessage.php`, `AssistantRequestLog.php`                                                                       |
| Request      | `app/Http/Requests/AssistantChatRequest.php`                                                                                                                    |
| Route bind   | `app/Providers/AppServiceProvider.php` (`conversation`)                                                                                                         |
| Web routes   | `routes/web.php` (`assistant` prefix)                                                                                                                           |
| Admin report | `app/Http/Controllers/Admin/AssistantReportController.php`, `routes/admin.php`                                                                                  |
| Views        | `resources/views/assistant/index.blade.php`, `resources/views/admin/assistant-report/index.blade.php`                                                           |
| Lang         | `lang/en/assistant.php`                                                                                                                                         |
| Telegram     | `app/Http/Controllers/TelegramWebhookController.php`, `TelegramBotService`, `ProcessTelegramDomainAssistantMessage` job, `App\Support\DomainAssistantListScope` |
| Webhook CLI  | `app/Console/Commands/TelegramSetWebhookCommand.php` — `php artisan telegram:set-webhook`                                                                       |


---

## 14. Telegram integration (DM)

**Purpose:** Same `**DomainAssistantService`** stack as the web assistant: one model, same tools, same permission checks (`access-domain-assistant`). Entry point is a **Telegram Bot API webhook** instead of `POST /assistant/chat`.

### 14.1 End-to-end flow

```
Telegram servers ──HTTPS POST──► /telegram/webhook/{secret}
       │                              │
       │                              ├► secret matches TELEGRAM_WEBHOOK_SECRET
       │                              ├► TELEGRAM_ASSISTANT_ENABLED + DOMAIN_ASSISTANT enabled
       │                              ├► private chat, text message
       │                              ├► User linked: users.telegram_user_id = from.id
       │                              └► ProcessTelegramDomainAssistantMessage
       │                                     └► DomainAssistantListScope::forTelegram($user)
       │                                     └► appendUserMessageAndComplete → Telegram sendMessage
```

### 14.2 Configuration (`config/services.php` → `telegram`)


| Env / key                                 | Role                                                                                                                                                                                                                                                                                                                                  |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `TELEGRAM_BOT_TOKEN`                      | Bot token from [@BotFather](https://t.me/BotFather).                                                                                                                                                                                                                                                                                  |
| `TELEGRAM_WEBHOOK_SECRET`                 | Long random string; embedded in the webhook **path** (`/telegram/webhook/{secret}`) so URLs are unguessable.                                                                                                                                                                                                                          |
| `TELEGRAM_ASSISTANT_ENABLED`              | Master switch: accept webhooks and run the assistant job.                                                                                                                                                                                                                                                                             |
| `TELEGRAM_ASSISTANT_DISPATCH_SYNC`        | Default `**true`**: run `**ProcessTelegramDomainAssistantMessage`** with `**dispatchSync**` inside the webhook request so replies work **without** a queue worker. Set `**false`** and run `**php artisan queue:work`** if you use `database`/`redis` queue and want async processing.                                                |
| `TELEGRAM_ASSISTANT_EXPAND_ALL_LOCATIONS` | Default `**false**`: list scope matches the web assistant with **“Show all records” unchecked**. Set `**true`** so users with `**see-all-record-switch`** get expanded invoice/additional-document scope on Telegram (same idea as checking **Show all records** on web). Implemented by `**DomainAssistantListScope::forTelegram`**. |


**Never** commit tokens; document keys in `.env.example`.

### 14.3 Webhook URL (HTTPS required)

Telegram **rejects** non-HTTPS webhook URLs. `**http://localhost/...` cannot be registered.**

- **Production:** set `**APP_URL=https://your-domain`**, then run `**php artisan telegram:set-webhook`** (uses `APP_URL` + `TELEGRAM_WEBHOOK_SECRET`).
- **Local / dev:** expose the app with an HTTPS tunnel (e.g. ngrok), then:
`php artisan telegram:set-webhook --url="https://xxxx.ngrok-free.app"`
The `--url` flag overrides `**APP_URL`** for that run (useful when the tunnel URL changes).

The Artisan command calls Telegram’s `**setWebhook`** API; on failure it prints the API error (e.g. HTTPS required).

### 14.4 User linking

**Admin → Users → Edit** — **“Telegram — Domain Assistant”**: enter **numeric user ID** (from [@userinfobot](https://t.me/userinfobot) / [@getidsbot](https://t.me/getidsbot)) or **@username**. The bot resolves `**getChat`** via **POST** (form body) for reliable resolution; the user should **Start** the bot at least once. Stored on `**users.telegram_user_id`** (and optional `**telegram_username`**); webhook matches `**message.from.id`**.

### 14.5 Conversations and threads

- Telegram chats use `**AssistantConversationManager::getOrCreateTelegramConversation**`, keyed by `**assistant_conversations.telegram_chat_id**` (one thread per Telegram chat).
- The **web** thread list only shows browser sessions: conversations `**whereNull('telegram_chat_id`)** so DM threads do not clutter the UI.

### 14.6 Alignment with the web assistant (list scope)


| Channel      | How “Show all records” is determined                                                                                                                                                                             |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Web**      | `**DomainAssistantListScope::fromWebRequest`**: JSON `**show_all_records`** must be true and the user `**can('see-all-record-switch')**`.                                                                        |
| **Telegram** | `**DomainAssistantListScope::forTelegram`**: expanded scope only if `**TELEGRAM_ASSISTANT_EXPAND_ALL_LOCATIONS=true`** and `**see-all-record-switch**`. Otherwise same default as web with the checkbox **off**. |


This keeps invoice/additional-document **visibility rules** consistent unless operators explicitly enable expanded Telegram scope via env.

### 14.7 Logging and admin report

- `**AssistantRequestLogger`** records each Telegram turn with `**telegram_chat_id`** and the same `**user_message`** / `**show_all_records**` fields as web.
- `**/admin/assistant-report**` includes a **TG chat** column and the **Question** text when stored.

### 14.8 Routes and implementation files

- **Webhook:** `POST /telegram/webhook/{secret}` — **CSRF-excluded** in `bootstrap/app.php`, throttled.
- **Controller:** `TelegramWebhookController@webhook`.
- **Job:** `ProcessTelegramDomainAssistantMessage` (implements `**ShouldQueue`**; often executed synchronously via `**dispatchSync`** when `dispatch_sync` is true).
- **Outbound API:** `TelegramBotService` — `sendMessage`, `**getChat`** for admin linking.

---

## 15. Related internal docs

- `[docs/architecture.md](architecture.md)` — Domain Assistant section (diagram + tables).
- `[docs/decisions.md](decisions.md)` — 2026-04-02 decision record.
- `[docs/todo.md](todo.md)` — Recently completed entry.
- `[docs/MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md](MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID.md)` — **Panduan (Bahasa Indonesia)** — pengguna + administrator.
- `[docs/MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID-PENGGUNA-AKHIR.md](MANUAL-DOMAIN-ASSISTANT-DAN-INVOICE-IMPORT-ID-PENGGUNA-AKHIR.md)` — **Panduan pengguna akhir saja** (tanpa konfigurasi server).

---

*Last updated: 2026-04-09 (DDS Laravel — Telegram parity, webhook CLI, list scope, admin report fields).*