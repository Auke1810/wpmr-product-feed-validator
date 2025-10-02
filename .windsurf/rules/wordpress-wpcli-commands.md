---
trigger: model_decision
description: WP-CLI command design and safety.
globs: |
  wp-content/plugins/**/cli/**/*.php
---

# WP-CLI Commands

## Do
- Provide `--dry-run` and confirmation prompts for destructive ops.
- Structured output (JSON/CSV) or quiet by default; verbose with `--debug`.
- Check capabilities/environment before irreversible actions.

## Don't
- Don't mix JSON with human-readable noise.
- Don't run destructive operations without flags or prompts.

## Checklist
- [ ] Dry-run support
- [ ] Safe confirmations
- [ ] Structured output
