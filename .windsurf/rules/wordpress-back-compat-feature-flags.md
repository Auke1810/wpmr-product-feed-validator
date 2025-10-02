---
trigger: model_decision
description: Backward compatibility and feature flagging.
globs: |
  wp-content/plugins/**/inc/**/*.php
---

# Backward Compatibility & Feature Flags

## Do
- Guard new APIs with `function_exists` / `method_exists` / `version_compare`.
- Provide feature flags; document defaults and deprecation plans.
- Use `_deprecated_function()` / `_deprecated_argument()` with version and message.

## Don't
- Don't call new WP/PHP APIs unguarded.
- Don't remove filters/actions without a deprecation window.

## Checklist
- [ ] Guards around new APIs
- [ ] Feature flags documented
- [ ] Deprecation notices where needed
