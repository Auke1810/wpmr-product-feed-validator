---
trigger: model_decision
description: Internationalization and localization practices for PHP and JS.
globs: |
  wp-content/**/*.{php,js}
---

# i18n & l10n

## Do
- Wrap all user-facing strings with `__`, `_x`, `_n` (PHP) or `@wordpress/i18n` (JS).
- Load text domain and provide `.po/.mo` files; `wp_set_script_translations` for JS.
- Use placeholders; avoid concatenation.

## Don't
- Don't leave strings unwrapped; don't build translatable strings by concatenation.
- Don't ignore plural forms and translator comments for dynamic strings.

## Checklist
- [ ] All strings wrapped
- [ ] JS translations wired
- [ ] Pluralization correct
