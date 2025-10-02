---
trigger: model_decision
description: Admin settings pages and UI patterns using the Settings API.
globs: |
  wp-content/plugins/**/admin/**/*.php
---

# Settings & Admin UI

## Do
- Use Settings API for registration, sections, fields.
- Enqueue assets only on your admin screens using `get_current_screen()`.
- Use capability checks (`manage_options` or lower if appropriate).
- Add Help Tabs and Screen Options for lists; follow A11y guidelines.

## Don't
- Don't process `$_POST` directly without nonces and sanitization.
- Don't enqueue admin assets globally on all screens.
- Don't store large settings arrays as autoloaded options.

## Checklist
- [ ] Settings API + capability gates
- [ ] Contextual enqueue
- [ ] A11y-compliant controls/labels
- [ ] Nonces on saves
