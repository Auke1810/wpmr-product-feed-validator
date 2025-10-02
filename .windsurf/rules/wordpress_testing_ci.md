---
trigger: model_decision
description: Testing and continuous integration gates.
globs: |
  **/*
---

# Testing & CI

## Do
- Use `wp-phpunit` for unit/integration tests; add REST and DB migration tests.
- Gate merges with PHPCS (WPCS, PHPCompatibilityWP), PHPUnit, and ESLint.
- Capture debug artifacts (logs) in CI for failures.

## Don't
- Don't merge with failing linters or tests.
- Don't skip migration tests for schema changes.

## Checklist
- [ ] PHPCS + PHPCompatibility gates
- [ ] PHPUnit + REST/migration tests
- [ ] ESLint/Prettier checks
