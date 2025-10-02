---
trigger: model_decision
description: Caching and performance guardrails for WP.
globs: |
  wp-content/**/*.php
---

# Caching & Performance

## Do
- Cache hot reads with object cache (`wp_cache_*`) and transients.
- Invalidate caches on writes; use versioned cache keys.
- Use pagination; keyset pagination for large tables.
- Batch DB calls; avoid N+1 queries.

## Don't
- Don't autoload heavy options; set `autoload => 'no'` for big data.
- Don't use `SQL_CALC_FOUND_ROWS`; avoid heavy queries per request.
- Don't run expensive logic on every `init`.

## Checklist
- [ ] Cache strategy + invalidation
- [ ] No heavy autoloaded options
- [ ] No N+1 / FOUND_ROWS
