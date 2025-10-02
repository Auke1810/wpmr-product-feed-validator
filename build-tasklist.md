# WPMR Product Feed Validator — Build Tasklist

## Milestone 1 — Scaffold & Admin
- [x] Create plugin skeleton and file structure
  - [x] `wpmr-product-feed-validator.php` (headers, bootstrap, constants, autoloader/textdomain)
  - [x] `includes/` (`Admin/Settings.php`, `REST/Validate_Controller.php`, `Services/Email.php`)
  - [x] `assets/` (admin CSS/JS; block build scaffolding)
  - [ ] `languages/` (textdomain, POT)
- [x] Admin settings page (capability: `manage_options`)
  - [x] Delivery mode: `email_only` | `email_plus_display`
  - [x] Require consent (bool)
  - [x] Attach CSV (bool, default on)
  - [x] Rate limits (per IP/day, per email/day, blocklist)
  - [x] Sampling defaults (bool, sample size)
  - [x] Max file size, timeout, redirects cap
  - [x] Shareable reports (on/off), report TTL
  - [x] CAPTCHA keys (reCAPTCHA/Turnstile)
  - [x] Webhook URL (n8n)
  - [x] Email templates (subject/body with placeholders)
- [x] Register/sanitize options; add settings sections/fields; nonces
- [x] REST API stub: `POST /wp-json/wpmr/v1/validate` (validate inputs; returns dummy payload per delivery mode)
- [x] Shortcode `[feed_validator]` (basic form: URL, Email, Consent)
- [x] Minimal Gutenberg block “Feed Validator” with the same fields
- [x] i18n setup (load textdomain, generate `.pot`, base `en_US`, add `nl_NL`)
- [x] Acceptance criteria
  - [x] Settings save and persist; defaults apply
  - [x] REST stub reachable and permissioned
  - [x] Shortcode and block render the basic form and POST correctly

## Milestone 2 — Fetcher & Parser
- [x] SSRF-safe fetcher (WP HTTP API)
  - [x] Validate URL (http/https only)
  - [x] Resolve DNS/remote IP; block private/reserved ranges
  - [x] Set timeouts, redirect cap (<=3), gzip support
  - [x] MIME sniff + XML declaration check
  - [x] Return structured transport diagnostics (http_code, content_type, size, redirects)
- [x] XMLReader streaming parse
  - [x] Detect `<item>` or `<entry>`; fail if neither
  - [x] Sample-first mode (default N=500; configurable)
  - [x] Per-item lightweight extraction via SimpleXMLElement
  - [x] Duplicate `g:id` tracking
- [ ] Acceptance criteria
  - [ ] Typical feeds sample in <30s
  - [ ] Oversized/invalid feeds surface transport/file errors

## Milestone 3 — Rules, Scoring, Rule Manager
- [x] Rulepack loader
  - [x] Load `/rules/google-v2025-09.json`
  - [x] Filter hook `gpfv_rules` for extendability
- [x] Rule engine (severities: `error|warning|advice`)
  - [x] All MVP rules implemented: Transport; Structure (items/duplicates); Required attributes; Identifiers; Price; URLs; Text; Category/Product type; Variants/Apparel; Shipping/Tax; Policy-adjacent
  - [x] Severity resolution with admin overrides (fallback to pack default)
- [x] Scoring service
  - [x] Base 100; penalties: E −7, W −3, A −1; category caps; floor 0
- [ ] Rule Manager (Admin)
  - [x] UI: list/search rules, enable/disable, set severity (per-rule)
  - [x] UI: weight overrides, category caps, import/export JSON, global Restore defaults
  - [x] REST: GET rules (effective), POST overrides upsert, DELETE override, POST preview dry-run
- [x] Unit tests for key rules and scoring
- [x] Acceptance criteria
  - [x] Effective severities reflect overrides immediately
  - [ ] Preview endpoint shows delta impact without persistence

## Milestone 4 — Reports, Storage, Email
- [x] DB migrations (on activation; `dbDelta`)
  - [x] `wp_feed_validator_reports` with indexes
  - [x] `wp_feed_validator_rule_overrides` with indexes
- [x] Report persistence
  - [x] Save metrics, totals JSON, issues JSON, rule_version
  - [x] Optional `public_key` for shareable permalink `/feed-report/{public_key}`
- [x] Email delivery (HTML + CSV)
  - [x] Build from templates with placeholders `{url}`, `{score}`, `{items_scanned}`, `{errors}`, `{warnings}`, `{date}`, `{rule_version}`, `{override_count}`
  - [x] Attach CSV (configurable)
  - [x] Graceful fallback (text-only); log failures; recommend SMTP
- [x] Webhook (optional): POST to n8n `{email,url,score,errors,warnings,report_url}`
- [x] PII retention cron
  - [x] Auto-delete emails >180 days; keep anonymized aggregates
- [x] Acceptance criteria
  - [x] Both delivery modes work for anonymous and logged-in flows
  - [x] Public report hides PII; email is never exposed on public endpoint

## Milestone 5 — UI/UX, Security, Accessibility
- [ ] Results UI (for `email_plus_display`)
  - [x] Score badge + readiness label
  - [x] Top 5 issues; totals
  - [x] Items table with filters (Errors, Warnings, By code, By item ID)
  - [x] Buttons: Email full report (resend), Run full scan (async via Action Scheduler), Download CSV (email-gated)
  - [x] CTA panel mapping to WPFM docs
- [x] Security/Abuse controls
  - [x] CAPTCHA on public form (configurable)
  - [x] Rate limit by IP and email; blocklist support
  - [x] Nonce and capability checks for admin endpoints
- [x] Accessibility & i18n
  - [x] Labels, focus management, ARIA live for progress
  - [x] Translate base strings to `nl_NL`
- [x] Acceptance criteria
  - [x] WCAG-friendly form interactions
  - [x] Throttling prevents spam without blocking normal use

## Milestone 6 — QA, Docs, Launch
- [x] QA
  - [x] Load testing with large feeds
  - [x] Correctness against sample GMC cases
- [x] Documentation
  - [x] Admin guide (settings + delivery modes)
  - [x] Public embed guide (shortcode + block)
  - [x] Developer docs (rulepack format, hooks, add a custom rule)
  - [x] Troubleshooting (fetch/parse errors)
- [ ] Launch prep
  - [ ] Changelog, version tag v0.1.0
  - [ ] Landing page with schema, sample reports
  - [ ] Programmatic SEO for public reports (canonical, titles)
  - [ ] Outreach checklist (communities, partners)
- [ ] Acceptance criteria
  - [ ] Go-Live checklist in plan passes (logged-in flow, consent, SMTP, Rule Manager behaviors)

## Cross-Cutting Tasks
- [ ] Decide bundling of Action Scheduler for full scans
- [ ] Telemetry/event logging to aid debugging (optional; respect privacy)
- [ ] CI: PHPCS/WPCS, basic linting, unit tests runner

## Next Step
- [x] Confirm this tasklist. If approved, start Milestone 1 by scaffolding the plugin and admin settings page.
