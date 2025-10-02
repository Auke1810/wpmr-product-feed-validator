---
trigger: model_decision
description: Licensing, attribution, telemetry, and privacy notices.
globs: |
  wp-content/plugins/**
---

# Licensing, Notices & Compliance

## Do
- Include license headers; ensure GPL-compatible dependencies.
- Attribute third-party assets; keep NOTICE file.
- Provide privacy notice for telemetry; make it opt-in; document data flows.

## Don't
- Don't phone-home without explicit consent.
- Don't embed licensed assets without attribution or license compliance.

## Checklist
- [ ] License headers + NOTICE
- [ ] Telemetry opt-in + docs
