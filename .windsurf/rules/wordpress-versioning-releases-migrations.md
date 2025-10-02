---
trigger: model_decision
description: Versioning, release discipline, DB migrations.
globs: |
  wp-content/plugins/**
---

# Versioning, Releases & Migrations

## Do
- Use SemVer; set `Requires PHP` and `Requires at least` headers.
- Maintain DB schema version option; run idempotent migrations on update.
- Provide downgrade-safe migrations or clear rollback guidance.

## Don't
- Don't ship breaking changes in patch releases.
- Don't run destructive migrations silently.

## Checklist
- [ ] SemVer followed
- [ ] Schema versioning present
- [ ] Safe migrations/rollbacks
