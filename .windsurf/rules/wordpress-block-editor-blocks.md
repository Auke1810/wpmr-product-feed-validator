---
trigger: model_decision
description: Block editor rules: block.json, SSR safety, @wordpress packages.
globs: |
  wp-content/plugins/**/blocks/**
---

# Block Editor / Blocks

## Do
- Use `block.json`; register via metadata; split editor/view scripts.
- Escape SSR output; validate attributes server-side.
- Use `@wordpress/*` packages and `wp_set_script_translations`.

## Don't
- Don't perform global side effects on registration.
- Don't fetch unauthenticated data without nonces when needed.

## Checklist
- [ ] block.json present
- [ ] Escaped SSR output
- [ ] Editor assets localized/translatable
