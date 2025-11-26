# Google Product Feed Validator — Master Plan

**Owner:** Auke (WP Marketing Robot)\
**Plugin Name (working):** WPMR Product Feed Validator\
**Primary Goal:** Public-facing validator that gives merchants instant, actionable diagnostics on Google Shopping product feeds, captures emails, earns backlinks, and funnels fixes to **Woo Product Feed Manager**.\
**Version Target:** v0.1.0 (MVP) → v0.2–0.3 harden → v1.0 launch

---

## 1) Objectives & Positioning

- **Speed to insight:** Scan a feed (full or sampled) and surface the top issues in < 30s for typical feeds.
- **Actionability:** Clear “what’s wrong, where, how to fix”, mapped to Woo Product Feed Manager (WPFM) docs/flows.
- **Lead-gen:** Require email (configurable) **for anonymous visitors**; for signed-in customers use the account email automatically. Ship reports via email (email-only **or** email+display).
- **Link magnet:** Shareable, public report URL with stable slug and canonical tags.
- **Credibility:** Rule packs versioned to Google requirements; visible rule version in every report.

**Positioning statement:**

> “The fastest way to understand (and fix) why your Google Shopping feed gets limited or disapproved—free validator by the makers of WooCommerce Product Feed Manager.”

**Non-goals (MVP):** On-page deep crawl of product pages; multi-channel (Meta/Pinterest/Bol) full rule coverage (planned next).

---

## 2) Success Metrics (KPIs)

- **Usage:** 1,000 validations/month within 60 days post-launch.
- **Lead capture:** ≥ 35% of validations provide a valid email.
- **Backlinks:** 50 unique domains linking to report pages or landing page within 90 days.
- **Conversion:** ≥ 8% of reports click a “Fix with WPFM” CTA; ≥ 3% install/activate WPFM (attribution via UTM parameters and CRM/n8n-assisted conversion).
- **Accuracy:** < 2% false-positive error rate verified against GMC disapproval logs from pilot merchants.

---

## 3) Target Users & Core Use Cases

- **Primary:** WooCommerce store owners (plugin users), in‑house PPC managers, and eCommerce agencies troubleshooting Google Merchant Center feeds. **Secondary:** general e-commerce merchants using the public validator (no plugin features).
- **Use cases:**
  - Quick pre-flight validation before submitting to GMC.
  - Diagnose “Missing identifiers,” “Price mismatch,” “Image quality,” etc.
  - Export issues to CSV/JSON → bulk fix.

---

## 4) Scope (MVP vs Later)

**In-scope MVP:**

- Public validator UI (shortcode + Gutenberg block).
- REST endpoint `POST /wpmr/v1/validate`.
- Streaming XML parse (XMLReader) with sampling (first 500 items) + optional full scan.
- **Delivery modes:**
  1. Email-only (forces email entry for anonymous visitors; if signed in, uses account email; returns success message only).
  2. Email + on-page display (forces email entry for anonymous visitors; if signed in, uses account email; renders JSON/UI results).
- Rule pack **google-v2025-09**.
- Scoring (0–100) + per-item issues + CSV/JSON export (email attached).
- GDPR consent toggle; opt-in to marketing separate.
- Basic rate limiting + CAPTCHA + SSRF-safe fetcher.

**Later (post-MVP):**

- Deep check: compare feed price/availability to product page structured data (sampled URLs).
- Channel packs: Meta, Pinterest, Bol.com, Microsoft.
- Scheduled re-checks + email alerts.
- Team inbox (assign issues), API key mode, bulk URL validation.

---

## 5) Feature Set (Detailed)

### 5.1 Public UI

- **Shortcode:** `[feed_validator]` with attributes (e.g., `sample="true"`).
- **Block:** “Feed Validator” with settings: sampling toggle, compact/expanded layout.
- **Form fields:** URL (required); Email (required for anonymous; auto-filled/hidden for logged-in); Consent (required if setting on).
- **States:** idle → validating → results/error.
- **Result summary:** score badge, readiness (“Likely acceptable / Needs work”), top 5 issues, totals.
- **Per-item table:** ID, issues count, severities, expandable details.
- **Exports:** Request sends report to email; CSV attached if enabled; optional on-page JSON.

### 5.2 Admin Settings

- **Delivery Mode:** `email_only` | `email_plus_display` (default).
- **Require Consent:** bool.
- **Attach CSV:** bool (default on).
- **Email Subject/Body templates** with placeholders `{url}`, `{score}`, `{items_scanned}`, `{errors}`, `{warnings}`, `{date}`.
- **Rate limits:** requests/IP/day; per-email/day; blocklist.
- **Max file size:** e.g., 100 MB; Timeout: 20 s; Redirect cap: 3.
- **Sampling default:** on; sample size: 500 items (configurable).
- **Shareable reports:** on/off; report TTL (days) or indefinite.
- **Logged-in behavior:** Email field hidden; server uses the current user’s account email; posted `email` is ignored when the request is authenticated.
- **Rule Manager (Admin):** GUI to adjust **per-rule severity** (Error/Warning/Advice), **enable/disable rules**, tweak **weights** (error/warning/advice penalties) and **category caps**. Includes search/filter by code/category, inline help, and **Restore defaults**. Changes apply immediately without code deploy.

### 5.3 Validation Engine

- **Parser:** XMLReader (streaming) + SimpleXMLElement for each `<item>`/`<entry>`.
- **Rule registry:** filters `gpfv_rules` load from `/rules/google-vYYYY-MM.json`.
- **Severities:** `error`, `warning`, `advice`.
- **Scoring:** errors −7 each (cap per category), warnings −3, advice −1; floor 0.
- **Duplicate detection:** repeated `g:id`.
- **Severity resolution:** For each rule, the engine applies **admin override** if present; otherwise uses the rulepack’s default severity. Weight penalties use admin-overridden weights if set; else rulepack defaults. Overrides are logged per report for auditability.

### 5.4 Reports & Storage

- **DB table:** `wp_feed_validator_reports`
  - `id (pk)` | `url` | `email` | `score` | `rule_version` | `totals_json` | `issues_json (LONGTEXT)` | `created_at` | `public_key (nullable)` | `consent (bool)`
- **Public permalink:** `/feed-report/{public_key}` (read-only view).
- **Privacy:** hide email on public page; only owner copy via email.

### 5.5 Lead Gen & CRM

- **Webhook:** optional POST to n8n with `{email,url,score,errors,warnings,link}`.
- **CTA mapping:** common errors → WPFM setup docs or feature page.

### 5.6 Security & Abuse Control

- SSRF-safe URL allowlist rules; block private IP ranges; enforce `http/https` only.
- MIME sniff + XML declaration check.
- reCAPTCHA/Turnstile on the public form (configurable).
- Throttle IP & email.

### 5.7 Performance

- Streaming parse, bounded memory.
- Sampling-first pattern with “Run full scan” option (async via Action Scheduler).
- Gzip handling; short timeouts; backoff on slow servers.

### 5.8 Accessibility & i18n

- WCAG-compliant form, focus states, ARIA live regions for progress.
- i18n-ready strings; base language: en_US; nl_NL added.

---

## 6) System Architecture (High-level)

**Flow:** Browser → WP REST `/validate` → Fetch feed (server-side) → Stream/validate → Build report → Save DB → Email report (+ CSV if enabled) → Respond (per delivery mode) → Optional public report view.

**Components:**

- Shortcode/Block UI
- REST Controller
- Fetcher (HTTP client)
- Parser (XMLReader)
- Rules Engine
- Scoring Service
- Report Persistor
- Mailer (wp_mail + templates)
- Public Report Controller

---

## 7) API & Endpoints

- `POST /wp-json/wpmr/v1/validate`\
  **Body:** `url`, `email (optional when authenticated)`, `consent(0|1)`, `sample(0|1)`

  If the request is authenticated, the server resolves the recipient from the current user’s account email and ignores a posted `email` value.\
  **Response:**

  - `email_only`: `{ delivery_mode: 'email_only', message }`
  - `email_plus_display`: `{ delivery_mode, message, report }`

- `GET /wp-json/wpmr/v1/report/{public_key}` (if enabled) → public JSON (no email).

- `GET /wp-json/wpmr/v1/rules` → returns current rules with **effective severity**, source (`default|override`), and metadata.

- `POST /wp-json/wpmr/v1/rules/overrides` → upsert overrides `{ rule_code, severity, enabled, weight_override }` (capability: `manage_options`).

- `DELETE /wp-json/wpmr/v1/rules/overrides/{rule_code}` → remove override for that rule.

- `POST /wp-json/wpmr/v1/rules/preview` → dry-run validation on a URL with **temporary overrides** (no persistence) to evaluate impact.

- **Webhook (optional):** POST to configured URL `{email,url,score,errors,warnings,report_url}`.

---

## 8) Data Model

```text
Table: wp_feed_validator_reports
- id BIGINT PK AI
- url VARCHAR(2048)
- email VARCHAR(320)
- score TINYINT UNSIGNED
- rule_version VARCHAR(20)  -- e.g., google-v2025-09
- totals_json LONGTEXT      -- {items, errors, warnings, advice}
- issues_json LONGTEXT      -- array of issues
- public_key CHAR(32) NULL  -- for shareable URL
- consent TINYINT(1)
- created_at DATETIME
Indexes: (created_at), (email), (public_key), (rule_version)
```

### Rule Overrides Table

```text
Table: wp_feed_validator_rule_overrides
- id BIGINT PK AI
- rule_version VARCHAR(20)   -- e.g., google-v2025-09
- rule_code VARCHAR(100)     -- unique code from rulepack (e.g., missing_gtin)
- severity_override ENUM('error','warning','advice') NULL
- enabled TINYINT(1) DEFAULT 1
- weight_override TINYINT NULL      -- optional per-rule weight (rare)
- notes VARCHAR(500) NULL
- updated_by BIGINT NULL            -- user id
- updated_at DATETIME
Indexes: (rule_version, rule_code), (updated_at)
```

**Issue object:**

```json
{
  "item_id": "SKU-123",
  "severity": "error|warning|advice",
  "code": "missing_gtin",
  "message": "GTIN is missing; if brand+MPN also missing, set identifier_exists to no.",
  "hint": "Add g:gtin or brand+mpn; otherwise add g:identifier_exists=no.",
  "path": "channel>item>g:gtin"
}
```

---

## 9) Validation Rule Catalog (google-v2025-09)

> Severity in (E)rror, (W)arning, (A)dvice. *MVP rules below; extendable.*

### A. Transport & File Integrity

1. (E) HTTP not 200 or >3 redirects.
2. (E) Content-Type not xml; or body empty.
3. (E) XML not well-formed; invalid characters.
4. (W) Missing XML declaration.
5. (W) Declared encoding != detected encoding.
6. (W) Oversized file (>100MB) or decompression failure.

### B. Structure & Namespace

1. (E) Neither RSS `<item>` nor Atom `<entry>` detected.
2. (E) Missing Google namespace `g:` for expected fields.
3. (E) Duplicate `g:id` values.

### C. Required Attributes (base)

1. (E) Missing `g:id`.
2. (E) Missing `g:title` or empty/whitespace.
3. (E) Missing `g:description`.
4. (E) Missing `g:link` (absolute URL required).
5. (E) Missing `g:image_link`.
6. (E) Missing or invalid `g:availability` (normalize to in stock/out of stock/preorder/backorder).
7. (E) Missing or invalid `g:price` (number + ISO 4217).
8. (W) Missing `g:condition` (assume new; warn if absent).

### D. Identifier Logic

1. (E) Missing all of: `g:gtin`, `g:brand`, `g:mpn`.
2. (W) `g:gtin` present but fails checksum/length.
3. (A) If GTIN missing and brand+MPN missing ⇒ suggest `g:identifier_exists=no`.

### E. Price & Sale Price

1. (E) `g:sale_price` ≥ `g:price`.
2. (W) `g:sale_price_effective_date` invalid ISO range (start\<end required).
3. (W) Currency mismatch across items.

### F. URLs & Images

1. (E) `g:link` not absolute http(s) or obviously broken (basic regex/parse).
2. (W) `g:image_link` not https.
3. (W) Image filename looks placeholder (e.g., `noimage`, small dimensions if detectable from querystring/filename).
4. (A) Recommend at least 800px on the longest side (advice only).

### G. Text Quality

1. (W) Title length > 150 chars or ALL CAPS; contains promo terms (Free shipping, % OFF).
2. (A) Description too short (< 100 chars) or excessively long (> 5000 chars).

### H. Category & Product Type

1. (W) Missing `g:google_product_category`; suggest based on keywords (heuristic).
2. (A) Missing `g:product_type`.

### I. Variants/Apparel (detect by attributes)

1. (E) Variants without `g:item_group_id`.
2. (W) Size/Color inconsistent across group; mixed sizing systems.
3. (A) One image reused across all variants (advice to diversify).

### J. Shipping & Tax

1. (W) `g:shipping` invalid format or currency mismatch.

### K. Policy Adjacent

1. (W) Possible “adult” category terms without `g:adult`.
2. (A) Brand/domain mismatch (heuristic).

### L. Scoring Model

- **Base score:** 100.
- **Penalties:** Error −7, Warning −3, Advice −1.
- **Category caps:** Max −20 per category to avoid runaway penalties.
- **Floor:** 0.

---

## 10) Email Delivery (Admin Options)

- **Modes:**
  - `email_only` → API returns `{message}`; page shows success; full report delivered via email only.
  - `email_plus_display` → API returns `{message, report}`; page renders report; email still sent.
- **Logged-in users:** Reports are sent to the account email on file; the form will not ask for email.
- **Templates:** HTML body with placeholders; include report snapshot + link to public report (if enabled). Include **Rulepack {rule_version}** and **{override_count} admin overrides** in the footer.
- **Attachments:** CSV of issues (configurable).
- **Deliverability:** recommend site SMTP (SPF/DKIM), no-reply@yourdomain; fallback text-only when HTML blocked.

---

## 11) UI/UX Specs

**Form:**

- URL input (validates http/https + .xml hint); Email input (hidden/auto-filled for logged-in users); Consent checkbox (if required); Validate button.

**Results:**

- Score badge (A/B/C/D/F), pass/fail readiness.
- Top issues list with “How to fix” links.
- Items table with filters (Errors only, Warnings only, By code, By item ID).
- Buttons: “Email full report” (resends the report to the recipient; uses account email for logged‑in users; in email‑only mode this acts as **Resend**), “Run full scan”, “Download CSV” (email-gated).
- CTA panel: Fix with WPFM + mapped docs.

**Accessibility:**

- Labels, aria-live for progress, keyboard focus management.

---

## 12) Implementation Plan (Milestones)

**Milestone 1:** Scaffold & Admin

- Plugin skeleton, settings page, delivery mode, consent, email templates.
- REST endpoint stub; Shortcode & minimal block.

**Milestone 2:** Fetcher & Parser

- SSRF-safe fetch; timeouts; redirects; gzip; content-type.
- XMLReader streaming; sample-first; item counter.

**Milestone 3:** Rules & Scoring + Rule Manager

- Rule engine; load rulepack; severities; scoring; duplicates.
- **Admin Rule Manager UI** (list/search rules, per-rule severity toggle E/W/A, enable/disable, global weights, category caps, restore defaults, import/export JSON).
- Unit tests for rules.

**Milestone 4:** Reports & Email

- Persist to DB; public report view; email build with CSV attachment.
- Delivery mode behavior; webhook to n8n; event logging (server logs or n8n).

**Milestone 5:** UI/UX + QA

- Results UI; filters; error states; i18n; accessibility pass.
- Load testing with large feeds; correctness vs GMC sample feeds.

**Milestone 6:** Launch Prep

- Docs, landing page, SEO, sample feeds, outreach list; changelog; version tag.

---

## 13) SEO, Landing & Link Strategy

- **Landing page:** “Free Google Product Feed Validator” with schema (SoftwareApplication), FAQs, screenshots, sample report.
- **Programmatic SEO:** indexable public reports with canonical; robots allow; unique titles: `Feed Report for {domain} — Score {score}`.
- **Outreach:** PPC communities, Woo/Shopify forums, Reddit (r/PPC, r/GoogleAds), agency partners.
- **Content:** “Top 10 Feed Errors in 2025”, “How to fix identifier issues”, “Price mismatch—root causes”.

---

## 14) Legal & Privacy

- **GDPR:** Explicit consent for email delivery (anonymous users); for logged-in customers, report delivery is a service communication to the account email. A separate opt-in is still required for any marketing emails (unchecked by default).
- **Disclaimer:** Severity levels are advisory and maintained by WP Marketing Robot; they may differ from Google’s current enforcement. Each report states the **rulepack version** and whether **admin overrides** were applied.
- **Retention:** Auto-delete PII (email) after 180 days; keep anonymized aggregate stats.
- **DPA:** Note mail provider/SMTP; update privacy policy; add cookie disclosure if analytics used.

---

## 15) Documentation

- Admin guide: settings + delivery modes.
- Public embed guide: shortcode & block.
- Developer: rulepack format, filters/actions, add a custom rule.
- Troubleshooting: common fetch/parse errors.

---

## 16) Risks & Mitigations

- **Abuse/Load:** Add CAPTCHA + rate limits; queue full scans with Action Scheduler.
- **False positives:** Validate rules vs real GMC cases; provide rule notes + references.
- **Email deliverability:** SMTP + domain auth; allow resend.
- **Legal exposure:** Clear disclaimers; advice-level guidance, not official compliance guarantee.

---

## 17) Dependencies

- PHP ≥ 7.4 (target 8.1+), WordPress ≥ 6.3.
- `XMLReader`, `SimpleXML`, `Action Scheduler` (bundled if needed).
- SMTP plugin recommended.

---

## 18) Versioning & Rule Packs

- Rule pack naming: `google-vYYYY-MM.json`.
- **Admin overrides are stored separately** and keyed by `{rule_version, rule_code}` so updates to rulepacks do **not** wipe local decisions. A “Review after update” banner appears when a new rulepack is installed.
- Import/export: allow exporting overrides as JSON; allow importing (with preview + diff) to ease migration across sites/environments.
- Changelog entry lists added/removed/changed rules and weights.
- Back-compat: older reports retain their rule version badge.

---

## 19) Roadmap (Post-MVP)

- v0.2 Deep check: compare feed vs page structured data (sample 20 URLs).
- v0.3 Channel packs (Meta, Microsoft, Pinterest); multi-feed batch.
- v0.4 Scheduled re-checks; alerting; API keys.
- v1.0 Polished UI, extensive docs, partner program for agencies.

---

## 20) Go-Live Checklist

- Verify **logged-in flow** hides the email field and uses the account email; anonymous flow requires email.
- Test both delivery modes: **email_only** and **email_plus_display** with logged-in vs anonymous users.
- Confirm GDPR consent shown only for anonymous users (and logged when provided).
- Ensure API ignores posted `email` when request is authenticated.
- SMTP configured (SPF/DKIM); deliverability OK to Gmail and Outlook.
- **Rule Manager**: Verify severity changes reflect immediately; test enable/disable; test Restore defaults; test import/export JSON; confirm audit log (updated_by, updated_at).

---

## 21) Changelog (working)

- **v0.1.0 (MVP):** Public validator; delivery modes; sampling; rulepack v2025-09; email with CSV; public reports; basic rate limits.

---

## Appendix A: Email Templates (Placeholders)

**Subject:** `Your Product Feed Report — {score}/100`

**HTML Body (example):**

```html
<p>Here is your product feed report for <strong>{url}</strong>.</p>
<ul>
  <li><strong>Score:</strong> {score}/100</li>
  <li><strong>Items scanned:</strong> {items_scanned}</li>
  <li><strong>Errors:</strong> {errors}</li>
  <li><strong>Warnings:</strong> {warnings}</li>
  <li><strong>Date:</strong> {date}</li>
</ul>
<p>We attached a CSV with the per-item issues. For one-click fixes inside WooCommerce, try <a href="https://wpmarketingrobot.com/">Woo Product Feed Manager</a>.</p>
```

---

## Appendix B: Rulepack JSON Skeleton

```json
{
  "id": "google-v2025-09",
  "weights": { "error": 7, "warning": 3, "advice": 1, "cap_per_category": 20 },
  "rules": [
    {
      "code": "http_status",
      "category": "transport",
      "default_severity": "error",
      "when": "http_code!=200",
      "message": "HTTP status not 200",
      "docs_url": "https://support.google.com/merchants/answer/7052112",
      "can_override": true
    },
    {
      "code": "missing_id",
      "category": "required_attributes",
      "default_severity": "error",
      "xpath": "//item|//entry",
      "check": "g:id",
      "message": "Missing g:id",
      "docs_url": "https://support.google.com/merchants/answer/6324492",
      "can_override": true
    }
  ]
}
```

---

## Appendix C: Mapping Issues → WPFM Fix Paths

- `missing_gtin` → Doc: Add GTIN attribute mapping; Suggest `identifier_exists=no` when brand+MPN missing.
- `price_format_invalid` → Doc: Currency formatting & decimals.
- `sale_price_invalid` → Doc: Set sale price below price; set effective date range.
- `image_link_http` → Doc: Enforce HTTPS; regenerate media URLs.
- `variants_missing_group` → Doc: Map item_group_id; variant attributes (size/color).

---

## Notes

- Keep rule messages concise. Always add a concrete “how to fix” link in the UI.
- Show rulepack version prominently in the UI and email.
