---
trigger: model_decision
description: GDPR-friendly data mapping, export/erase, retention.
globs: |
  wp-content/plugins/**
---

# Privacy & Data Lifecycle

## Do
- Map collected data; integrate with exporter/eraser where applicable.
- Provide retention schedules and user controls; anonymize when possible.
- Document telemetry and provide opt-in with clear scopes.

## Don't
- Don't log PII or secrets in plaintext.
- Don't export internal tokens or secrets.

## Checklist
- [ ] Exporter/eraser hooks
- [ ] Retention policy
- [ ] Telemetry opt-in
