# Patient Referral System API

A backend API service for patient referral intake and triage, built with Laravel 11. The API allows internal systems to submit referrals, retrieve them, list with filters, and cancel with an asynchronous triage workflow powered by Laravel queues.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Referral Lifecycle](#referral-lifecycle)
- [Prerequisites](#prerequisites)
- [Local Setup](#local-setup)
- [Running the Application](#running-the-application)
- [Running Tests](#running-tests)

## Architecture Overview

```
Request → AuthenticateServiceToken → IdempotencyMiddleware → RateLimiter
    → Controller → FormRequest (validation) → Service → Model
    → Event (ReferralCreated) → Queued Listener (TriageReferralListener)
    → Spatie Activity Log (audit trail)
    → API Resource (response)
```

## Referral Lifecycle

```
received → triaging → accepted
                    → rejected
received → cancelled
triaging → cancelled
```

| Status | Description |
|--------|-------------|
| `received` | Initial state. Referral submitted and queued for triage. |
| `triaging` | Triage process is actively evaluating the referral. |
| `accepted` | Referral accepted after triage evaluation. |
| `rejected` | Referral rejected after triage evaluation. |
| `cancelled` | Referral cancelled by the requesting system. Only allowed from `received` or `triaging`. |

## Prerequisites

- **PHP 8.4** / **Laravel 11**
- **MySQL 8.0** — primary database
- **Laravel Queues** (database driver) — async triage processing
- **Spatie Activity Log** — audit trail
- **Docker** — containerized development environment
- **PHPUnit** — testing

## Local Setup

### For Windows (WSL)

```bash
sudo apt install curl
curl -fsSL https://get.docker.com -o get-docker.sh
chmod +x get-docker.sh
sudo ./get-docker.sh
```

### 1. Clone and configure

```bash
git clone git@github.com:Jeyhun023/referral-service.git
cd referral-service
```

### 2. Build and start containers

```bash
make init
```

This builds and starts all containers: PHP-FPM, Nginx, MySQL, and the Queue Worker.

Set a service token in `.env`:

```
API_SERVICE_TOKEN=your-secret-token-here
```

### 3. Run migrations

```bash
make artisan migrate
```

The application is now available at **http://localhost:8000**.

## Running the Application

### Container management

```bash
make start       # Start all containers
make stop        # Stop all containers
make restart     # Restart all containers
make fresh       # Destroy and recreate all containers
make ps          # Show container status
make exec        # Enter the PHP container shell
```

### Artisan & Composer

```bash
make artisan <command>    # e.g., make artisan migrate
make composer <command>   # e.g., make composer require package/name
```

### Queue Worker

The queue worker runs automatically via the `referral-queue` Docker container. It processes triage jobs with:

- **3 max attempts** per job
- **Exponential backoff**: 10s, 60s, 300s
- **Auto-restart** every hour (`--max-time=3600`)

To monitor the queue manually:

```bash
make artisan pail            # Tail application logs
docker logs referral-queue   # Queue worker logs
```

## Running Tests

```bash
make test
```

### Endpoints

#### 1. Create Referral

```
POST /api/v1/referrals
```

**Request:**

```bash
curl -X POST http://localhost:8000/api/v1/referrals \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-secret-token-here" \
  -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000" \
  -d '{
    "patient_first_name": "John",
    "patient_last_name": "Doe",
    "patient_date_of_birth": "1990-05-15",
    "patient_phone": "+1234567890",
    "patient_email": "john.doe@example.com",
    "reason": "Chronic back pain requiring specialist evaluation",
    "priority": "high",
    "source_system": "EMR-System",
    "referring_provider": "Dr. Jane Smith",
    "notes": "Patient has history of lumbar issues"
  }'
```

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `patient_first_name` | string | yes | Max 255 characters |
| `patient_last_name` | string | yes | Max 255 characters |
| `patient_date_of_birth` | date | yes | Must be before today (YYYY-MM-DD) |
| `patient_phone` | string | no | Max 20 characters |
| `patient_email` | string | no | Valid email, max 255 characters |
| `reason` | string | yes | Max 2000 characters |
| `priority` | enum | no | `urgent`, `high`, `normal` (default), `low` |
| `source_system` | string | yes | Identifier of the calling system, max 255 |
| `referring_provider` | string | no | Name of the referring provider, max 255 |
| `notes` | string | no | Max 5000 characters |


---

#### 2. Get Referral

```
GET /api/v1/referrals/{id}
```

**Request:**

```bash
curl http://localhost:8000/api/v1/referrals/9e3a1b4c-5d6e-7f8a-9b0c-1d2e3f4a5b6c \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-secret-token-here"
```

#### 3. List Referrals

```
GET /api/v1/referrals
```

**Query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | enum | Filter by status: `received`, `triaging`, `accepted`, `rejected`, `cancelled` |
| `priority` | enum | Filter by priority: `urgent`, `high`, `normal`, `low` |
| `search` | string | Search patient name or source system |
| `date_from` | date | Filter referrals created on or after this date |
| `date_to` | date | Filter referrals created on or before this date |
| `sort_by` | string | Sort field: `created_at` (default), `priority`, `status`, `patient_last_name` |
| `sort_direction` | string | `asc` or `desc` (default) |
| `per_page` | integer | Items per page: 1-100 (default: 15) |
| `page` | integer | Page number |

**Request:**

```bash
curl "http://localhost:8000/api/v1/referrals?status=received&priority=high&per_page=5&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-secret-token-here"
```

#### 4. Cancel Referral

```
POST /api/v1/referrals/{id}/cancel
```

Only referrals in `received` or `triaging` status can be cancelled. Attempting to cancel a referral in `accepted`, `rejected`, or `cancelled` status returns `422`.

**Request:**

```bash
curl -X POST http://localhost:8000/api/v1/referrals/9e3a1b4c-5d6e-7f8a-9b0c-1d2e3f4a5b6c/cancel \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer your-secret-token-here" \
  -H "Idempotency-Key: cancel-550e8400-e29b-41d4-a716-446655440000" \
  -d '{
    "reason": "Patient requested cancellation"
  }'
```

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reason` | string | no | Cancellation reason, max 2000 characters |
