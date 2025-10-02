---
trigger: model_decision
description: Accessibility rules for admin and front-end.
globs: |
  wp-content/**/*.{php,html,js}
---

# Accessibility (A11y)

## Do
- Proper heading hierarchy; label all inputs; ensure focus states.
- Keyboard access for menus and dialogs; ARIA attributes as needed.
- Meet WCAG AA color contrast; use `.screen-reader-text` when appropriate.

## Don't
- Don't rely on color alone; don't attach click handlers without keyboard equivalents.
- Don't remove outlines without alternative focus indication.

## Checklist
- [ ] Keyboard navigable UI
- [ ] Labeled controls + ARIA
- [ ] Contrast AA verified
