---
trigger: model_decision
description: REST API and admin-ajax contracts, validation, and security.
globs: |
  wp-content/**/*.{php,js}
---

# REST & AJAX Contracts

## Do
- Register routes with namespace/version; add `permission_callback`.
- Validate/sanitize all params via args schema; return `WP_Error` with HTTP status.
- Use nonces (`wp_rest` or `admin-ajax`), and `@wordpress/api-fetch` nonce middleware.
- Standard error shape: `{ code, message, data: { status, trace_id? } }`.
- Paginate; whitelist `orderby`, `order`, `per_page` values.

## Don't
- Don't return HTML from REST; use JSON only.
- Don't accept raw column names or SQL fragments.
- Don't leak stack traces or secrets in error messages.

## Checklist
- [ ] Route schema + validation present
- [ ] Nonce + capability checks
- [ ] Whitelisted pagination/sorting
- [ ] JSON errors with status codes
