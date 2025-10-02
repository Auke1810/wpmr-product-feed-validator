---
trigger: model_decision
description: Safe filesystem, uploads, and media handling.
globs: |
  wp-content/**/*.php
---

# Filesystem & Uploads

## Do
- Use WP_Filesystem for writes; store uploads under `wp-content/uploads/{plugin}`.
- Validate mime/size; use `wp_check_filetype_and_ext()`.
- Generate random filenames; do not trust client names.
- Protect upload directories with server rules (deny PHP execution).

## Don't
- Don't write to plugin/theme directories at runtime.
- Don't serve user uploads without validation; no open redirects on download endpoints.

## Checklist
- [ ] MIME/size validation
- [ ] Randomized filenames
- [ ] No runtime writes to code dirs
