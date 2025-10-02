---
trigger: model_decision
description: Deactivation/uninstall etiquette and supportability.
globs: |
  wp-content/plugins/**
---

# Deactivation/Uninstall & Supportability

## Do
- Implement `register_deactivation_hook()` and `uninstall.php`.
- Data removal is **opt-in**; provide UI; remove orphans (cron, options, caps).
- Provide a support bundle generator (sanitized env info, versions).

## Don't
- Don't silently delete user content.
- Don't leave cron jobs or scheduled events behind.

## Checklist
- [ ] Clean uninstall flow
- [ ] Orphan cleanup
- [ ] Support bundle command
