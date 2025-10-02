---
trigger: model_decision
description: Security hardening rules across all WordPress code paths.
globs: |
  **/*.php
---

# Security Hardening — Global Rules

## Do
- Gate every write path with `current_user_can()` using least privilege.
- Require nonces on all state changes; verify with `check_admin_referer()` / `wp_verify_nonce()`.
- Sanitize input (`sanitize_text_field`, `absint`, `sanitize_email`); escape output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`).
- Use `$wpdb->prepare()` for **every** dynamic SQL; escape LIKE via `$wpdb->esc_like()`.
- Block direct access with `defined( 'ABSPATH' ) || exit;` in plugin/theme PHP entry files.
- Validate file uploads (mime, size) and use WP_Filesystem API.
- Store secrets outside repo; fetch via env; mask in logs.
- Use `permission_callback` on REST routes; validate/sanitize params.

## Don't
- Never trust `$_REQUEST`; avoid echoing unescaped user content.
- Don't build SQL via concatenation; don't use `eval()` or arbitrary `include` paths.
- Don't expose stack traces or raw SQL errors to end users.
- Don't store API keys in options without protection; never commit secrets.
- Don't allow unbounded queries from user input (e.g., raw `orderby`, `per_page`).
 
## Checklist
- [ ] Cap checks + nonces on all mutations
- [ ] All DB access via `$wpdb->prepare()`
- [ ] Escaping on output everywhere
- [ ] REST `permission_callback` + validation
- [ ] Upload validation and WP_Filesystem
