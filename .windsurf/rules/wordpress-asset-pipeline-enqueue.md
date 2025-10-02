---
trigger: model_decision
description: Asset versioning, context-aware enqueue, and modules.
globs: |
  wp-content/**/*.{php,js,css}
---

# Asset Pipeline & Enqueue

## Do
- Version every asset; enqueue only where needed.
- Use `wp_set_script_translations` and `wp_localize_script` for config and i18n.
- Consider `wp_enqueue_script_module()` for ESM where supported.

## Don't
- Don't inline large JS/CSS; don't enqueue admin assets on front-end.
- Don't leak globals; keep modules scoped.

## Checklist
- [ ] Versioned assets
- [ ] Contextual enqueues
- [ ] No global pollution
