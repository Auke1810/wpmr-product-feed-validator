---
trigger: model_decision
description: WP-Cron, schedules, idempotent background jobs, and locking.
globs: |
  wp-content/**/*.php
---

# Cron, Schedules & Background Jobs

## Do
- Register events with unique names; document intervals.
- Make handlers idempotent; add a transient-based mutex (with TTL).
- Exponential backoff on repeated failures; log outcomes in debug mode.
- Provide WP-CLI commands to run/inspect jobs.

## Don't
- Don't perform long/expensive work during front-end request lifecycle.
- Don't allow duplicate parallel runs; ensure a lock.
- Don't rely solely on pseudo-cron for mission-critical tasks (offer real cron endpoint).

## Checklist
- [ ] Idempotent handlers + lock
- [ ] Bounded retries/backoff
- [ ] CLI runner present
