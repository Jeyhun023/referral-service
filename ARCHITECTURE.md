# Architecture

## Project Structure

```
app/
├── Enums/                  # ReferralStatus, ReferralPriority
├── Events/                 # ReferralCreated
├── Http/
│   ├── Controllers/Api/V1/ # ReferralController
│   ├── Middleware/          # AuthenticateServiceToken, IdempotencyMiddleware
│   ├── Requests/Referral/  # StoreReferralRequest, ListReferralsRequest, CancelReferralRequest
│   └── Resources/          # ReferralResource
├── Listeners/              # TriageReferralListener (queued)
├── Models/                 # Referral, IdempotencyKey
└── Services/               # ReferralService
```

## Key Design Decisions

### Layered Architecture

```
HTTP → Middleware → Controller → FormRequest → Service → Model → Database
                                                    ↓
                                              Event → Listener (Queue)
```

## Schema

### `referrals` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID (PK) | Prevents ID enumeration, supports distributed generation |
| `patient_*` | string/date | Denormalized patient fields — no separate patients table |
| `reason` | text | |
| `priority` | string | Enum-backed: urgent, high, normal, low |
| `status` | string | Enum-backed: received, triaging, accepted, rejected, cancelled |
| `source_system` | string | Identifies the calling service |
| `referring_provider` | string (nullable) | |
| `notes` | text (nullable) | |
| `triaged_at` | timestamp (nullable) | Set when triage completes |
| `cancelled_at` | timestamp (nullable) | Set on cancellation |
| `cancellation_reason` | text (nullable) | |

**Indexes:** `status`, `priority`, `(patient_last_name, patient_first_name)`, `created_at`: chosen based on the filtering and sorting the list endpoint supports.

### `idempotency_keys` table

Stores response cache for idempotent POST requests. Keys expire after 24 hours. Uses Laravel's `Prunable` trait for automatic cleanup via `model:prune`.

### `activity_log` table (Spatie)

Default Spatie schema with `nullableUuidMorphs` instead of integer morphs to support UUID primary keys. Logs old/new attribute values on every status change.

## Queue & Triage Design

### Event/Listener over Direct Job Dispatch

```
ReferralService::create()
    → Referral::create()          # persists
    → ReferralCreated::dispatch() # fires event
        → TriageReferralListener  # queued, async
```

Using an event rather than dispatching a job directly decouples creation from triage. Additional listeners (notifications, analytics, webhooks) can subscribe to `ReferralCreated` without touching the service.

**Retry strategy:** 3 attempts with exponential backoff (10s, 60s, 300s). On final failure, the `failed()` method resets status to `received` and logs to the audit trail — the referral remains retriable rather than stuck in `triaging`.

### Queue Driver

Database driver was chosen for simplicity. For production scale, Redis or SQS would be more appropriate.

## Authentication

Static bearer token via `Authorization: Bearer <token>` header, validated in `AuthenticateServiceToken` middleware using `hash_equals()` (timing-safe comparison).

**Why not Sanctum/Passport?** This is a service-to-service API. There are no user sessions, no OAuth flows, no token refresh.

**Why middleware, not a guard?** A custom guard would integrate with Laravel's `Auth` facade but adds ceremony (user provider, guard config) for a case where we just need to verify a static token. The middleware is 15 lines and does exactly one thing.

## Idempotency

Follows the Stripe pattern: client sends `Idempotency-Key` header, server caches the response and replays it on duplicate requests.

- Only applies to POST requests
- Stores status code, response body, and content-type headers
- Returns `X-Idempotency-Replayed: true` on cache hit
- 24-hour TTL with automatic pruning

Implemented as middleware so it wraps the entire request lifecycle — the cached response is returned before the controller is even invoked.
