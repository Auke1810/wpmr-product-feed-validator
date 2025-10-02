---
trigger: model_decision
description: Hooks and filters API for extensible plugins.
globs: |
  wp-content/plugins/**
---

# Extensibility (Hooks/Filters)

## Do
- Provide stable, documented hooks with clear param order and types.
- Use filters to allow short-circuiting and customization.
- Prefix hook names; avoid collisions.

## Don't
- Don't rename or remove hooks without deprecation.
- Don't perform heavy work inside hooks.

## Checklist
- [ ] Hook reference docs
- [ ] Short-circuit filters where useful
