## AI Automation Rollout Gates

### Stage 0: Sandbox
- `AI_ENABLED=true`, `QUEUE_CONNECTION=database`, queue workers running.
- Validate `billing:enforce-dunning --dry-run` and `billing:expire-trials --dry-run` outputs.

### Stage 1: Low-risk tenants
- Enable AI announcement + ops summary features for internal tenants.
- Monitor logs:
  - `ai.provider_failed`
  - `automation.metrics.*`
  - `Outbound webhook delivery failed.`

### Stage 2: Broader rollout
- Validate webhook duplicate protections using repeated Paystack payload tests.
- Verify auto-disable behavior for unstable webhook endpoints.

### Stage 3: Full rollout
- KPI checks:
  - AI fallback success rate > 99%
  - webhook success rate > 99%
  - zero duplicate billing side effects from webhook retries
