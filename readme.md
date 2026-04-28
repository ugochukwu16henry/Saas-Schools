# Saas-Schools

Multi-tenant school management platform built with Laravel.  
This project includes school operations, billing/dunning, platform administration, affiliate flows, and AI-assisted workflows.

## Contents

- Quick start
- Core architecture
- Auth roles and guards
- Key product modules
- AI automation
- Billing and dunning
- Platform webhooks and notifications
- Environment setup
- Deployment (Railway/containers)
- API and route map
- Common incidents and fixes
- Local Docker development
- Operations runbook
- Testing
- Security

## Quick Start

1. Install dependencies  
   `composer install`
2. Create env file  
   Copy `.env.example` to `.env`
3. Generate application key  
   `php artisan key:generate`
4. Configure database in `.env`
5. Run migrations  
   `php artisan migrate`
6. Optional seed data  
   `php artisan db:seed`
7. Start app  
   `php artisan serve`

## Core Architecture

- **App type:** Multi-tenant (school-scoped + platform-scoped surfaces)
- **Tenant context:** Set by middleware (`SetTenant`) using authenticated user `school_id`
- **Main UI surfaces:**
  - School dashboard (`/dashboard`)
  - Platform admin dashboard (`/platform/dashboard`)
  - Affiliate dashboard (`/affiliate`)
- **Background automation:**
  - Billing dunning and trial expiry commands
  - Platform digest scheduler
  - Queue-based webhook/notification jobs

## Auth Roles and Guards

- **School users** (web guard): super admin, admin, teacher, student, parent, accountant, librarian
- **Platform users** (`platform` guard): platform operators and global management
- **Affiliate users** (`affiliate` guard): affiliate onboarding and payout tracking

Guard-specific routes are intentionally separated to prevent role-context leakage.

## Key Product Modules

- Student lifecycle management
- Class/section/subject/exam/marks workflows
- Payments and billing status UI
- School registration and onboarding
- Affiliate referral and payout workflows
- Platform analytics, notifications, and webhooks

## AI Automation

AI features are config-driven (`config/ai.php`) and currently include:

- `announcement_draft`
- `ops_summary`

### AI routes

- `GET /ai/announcement-draft`
- `POST /ai/announcement-draft`
- `POST /ai/ops-summary`

### AI config highlights

- Default + fallback providers
- Provider chains per feature
- Per-feature temperature / max tokens
- Structured output mode (`json_object`)
- Safety guards and output caps

Required env examples:

- `AI_ENABLED=true`
- `AI_DEFAULT_PROVIDER=openai`
- `AI_FALLBACK_PROVIDER=oss`
- `OPENAI_API_KEY=...`
- `OSS_AI_BASE_URL=...`

## Billing and Dunning

Paystack integration and dunning policy are configured in `config/paystack.php`.

### Billing routes

- `GET /billing/prompt`
- `GET /billing/status`
- `GET /billing/initialize`
- `GET /billing/callback`
- `POST /paystack/webhook`

### Dunning behavior

- Failure threshold (`PAYSTACK_PAYMENT_FAILURE_THRESHOLD`)
- Grace period in days (`PAYSTACK_PAYMENT_FAILURE_GRACE_DAYS`)
- Scheduled enforcement and trial expiry commands

## Platform Webhooks and Notifications

Outbound webhooks dispatch from platform notifications and run via queue jobs.

Config:

- `PLATFORM_WEBHOOK_DISABLE_AFTER_FAILURES` (auto-disable unstable endpoints)

## Environment Setup

### Required production settings

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` must be set (base64 string)

If you see `MissingAppKeyException`:

1. Generate once: `php artisan key:generate --show`
2. Set output as `APP_KEY` in hosting environment
3. Redeploy and clear cache: `php artisan optimize:clear`

### Session/auth notes

- `SESSION_DOMAIN` should be empty or host-only (no scheme/path)
- Local HTTP: `SESSION_SECURE_COOKIE=false`
- HTTPS production: `SESSION_SECURE_COOKIE=true`

## Deployment (Railway/Containers)

### Minimal checklist

1. Set runtime env vars (`APP_KEY`, DB, queue, mail, Paystack, AI keys)
2. Run `php artisan migrate --force`
3. Start queue worker:
   - `php artisan queue:work --queue=webhooks,notifications,default --tries=3`
4. Start scheduler:
   - cron: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

If using a process manager, run web, queue worker, and scheduler as separate processes.

## API and Route Map

| Area | Method | Path | Purpose |
| --- | --- | --- | --- |
| Auth | `GET` | `/login` | School user login page |
| School Registration | `GET` | `/register/school` | Public school signup page |
| School Registration | `POST` | `/register/school` | Create school + owner account |
| Billing | `GET` | `/billing/prompt` | Billing required view |
| Billing | `GET` | `/billing/status` | Billing self-service status |
| Billing | `GET` | `/billing/initialize` | Initialize Paystack payment |
| Billing | `GET` | `/billing/callback` | Payment callback handler |
| Billing Webhook | `POST` | `/paystack/webhook` | Paystack inbound events |
| AI | `GET` | `/ai/announcement-draft` | AI announcement page |
| AI | `POST` | `/ai/announcement-draft` | Generate announcement draft |
| AI | `POST` | `/ai/ops-summary` | Generate operations summary |
| Platform | `GET` | `/platform/dashboard` | Platform dashboard |
| Platform | `GET` | `/platform/notifications` | Platform notifications list |
| Platform | `GET` | `/platform/webhooks` | Platform webhook admin |
| Affiliate | `GET` | `/affiliate` | Affiliate dashboard |
| Affiliate | `GET` | `/affiliates/request` | Affiliate application form |

Use `php artisan route:list` to inspect the full route catalog.

## Common Incidents and Fixes

### 1) `MissingAppKeyException`
- Ensure `APP_KEY` is set in runtime env.
- Generate once with `php artisan key:generate --show`.
- Run `php artisan optimize:clear`.

### 2) 419 Page Expired on login/forms
- Set `SESSION_DOMAIN` to empty or host-only value.
- Set `SESSION_SECURE_COOKIE=false` for local HTTP, `true` for HTTPS production.
- Clear caches: `php artisan optimize:clear`.

### 3) Uploaded images/logos not showing
- Ensure files are saved on `public` disk.
- Verify `/storage` serving is available (symlink or route fallback).
- Confirm file exists under `storage/app/public/...`.

### 4) Queue jobs not processing
- Verify `QUEUE_CONNECTION` is not `sync` in production.
- Start worker: `php artisan queue:work --queue=webhooks,notifications,default`.
- Check failed jobs and retry: `php artisan queue:retry all`.

### 5) Scheduler automations not running
- Configure cron for `php artisan schedule:run` every minute.
- Optionally use `php artisan schedule:work` in managed process environments.

### 6) Wrong guard / unexpected 403
- Confirm route belongs to the expected guard context (`web`, `platform`, `affiliate`).
- Sign out of other dashboards if sessions overlap.

## Local Docker Development

No first-party `docker-compose.yml` or project `Dockerfile` is currently committed in this repository.

If you want Dockerized local development, recommended baseline:

1. Add services for:
   - `app` (PHP-FPM)
   - `web` (Nginx/Caddy)
   - `db` (MySQL/MariaDB)
   - `redis` (optional, for cache/queue)
2. Mount project directory into `app` container.
3. Set container env vars (`APP_KEY`, DB, queue, session settings).
4. Run:
   - `php artisan migrate`
   - `php artisan queue:work`
   - scheduler via cron or `schedule:work`.

If you want, I can generate a complete `Dockerfile` + `docker-compose.yml` tailored for this project in one pass.

## Operations Runbook

### Useful commands

- Clear caches: `php artisan optimize:clear`
- Rebuild config cache: `php artisan config:cache`
- Check route list: `php artisan route:list`
- Dry-run dunning: `php artisan billing:enforce-dunning --dry-run`
- Dry-run trial expiry: `php artisan billing:expire-trials --dry-run`

### Queue

- Start worker: `php artisan queue:work`
- Retry failed jobs: `php artisan queue:retry all`

## Testing

- Run all tests: `php vendor/bin/phpunit`
- Run a specific suite/file: `php vendor/bin/phpunit tests/Unit/...`

## Security

- Keep secrets out of git (`.env` only)
- Do not expose backend secrets to frontend builds
- Report vulnerabilities privately to project maintainers
