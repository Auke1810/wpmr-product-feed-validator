---
trigger: model_decision
description: HTTP client patterns, retries, timeouts, credential hygiene.
globs: |
  wp-content/plugins/**
---

# Third-Party API Usage

## Do
- Use `wp_remote_*` or a thin wrapper; set timeouts and sane retries with jitter.
- Exponential backoff and circuit breaker on repeated failures.
- Store tokens securely; mask in logs; rotate regularly.

## Don't
- Don't block front-end requests with slow APIs; queue or cache results.
- Don't log full request/response bodies with secrets.

## Checklist
- [ ] Timeouts + retries
- [ ] Token storage + masking
- [ ] Background fetches where possible
