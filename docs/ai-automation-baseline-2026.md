## AI + Automation Baseline (2026)

### Current capabilities

-   AI: announcement drafting with provider routing (`openai` -> `oss`) and usage audit logs.
-   Automation: billing dunning, trial expiry, platform digest scheduler, inbound Paystack webhook, outbound platform webhooks.

### KPI baseline targets

-   AI generation p95 latency: `< 2500ms`
-   AI fallback success rate: `> 99%` for retryable provider errors
-   Unsafe output escape rate: `< 0.5%`
-   Outbound webhook delivery success rate (24h): `> 99%`
-   Webhook retry recovery rate (24h): `> 90%`
-   Scheduler missed-run incidents per month: `0`

### Rollout gates

-   Gate 1: queue worker + retry configured in staging.
-   Gate 2: idempotency checks prevent duplicate webhook side effects.
-   Gate 3: observability logs include trace IDs, attempts, latency, and failure reason classes.
-   Gate 4: feature flags allow per-feature AI disable/fallback.
