---
trigger: model_decision
description: Multisite-safe coding patterns and data placement.
globs: |
  wp-content/plugins/**
---

# Multisite Support

## Do
- Use `get_blog_prefix()` for per-site tables; site vs. network options correctly.
- Use site transients vs. network transients appropriately.
- Respect network activation paths; network admin caps for network settings.

## Don't
- Don't assume single site contexts.
- Don't write network settings as per-site (and vice versa).

## Checklist
- [ ] Correct per-site vs. network storage
- [ ] Network admin capability checks
