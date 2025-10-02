---
trigger: model_decision
description: Enforce WordPress debugging best practices across environments (local, development, staging, production).
globs: |
  wp-config.php
  wp-content/**/*.php
  wp-content/**/*.js
---
# WordPress Debugging — Rules, Patterns, and Guardrails

## Goals
- Fast, repeatable issue isolation without leaking sensitive data.
- Clear separation between local/dev/staging/production behavior.
- Zero debug noise in production; actionable logs in non-prod.

## Environment and Core Flags
- Use `WP_ENVIRONMENT_TYPE` with values: `local`, `development`, `staging`, `production`.
- Control debug flags in `wp-config.php`, **before** `require_once ABSPATH . 'wp-settings.php';`:
  ```php
  // Example baseline (adjust per env):
  define( 'WP_ENVIRONMENT_TYPE', getenv( 'WP_ENVIRONMENT_TYPE' ) ?: 'production' );

  if ( in_array( WP_ENVIRONMENT_TYPE, [ 'local', 'development' ], true ) ) {
      define( 'WP_DEBUG', true );
      define( 'WP_DEBUG_LOG', true );          // Writes to wp-content/debug.log
      define( 'WP_DEBUG_DISPLAY', false );     // Prevent HTML output of notices
      define( 'SCRIPT_DEBUG', true );          // Use unminified core assets
      define( 'SAVEQUERIES', true );           // Local only; query performance
    } else {
      define( 'WP_DEBUG', false );
      define( 'WP_DEBUG_LOG', true );          // Keep logs on staging/prod
      define( 'WP_DEBUG_DISPLAY', false );
      define( 'SCRIPT_DEBUG', false );
      define( 'SAVEQUERIES', false );          // Never on prod
  }
  ```
- Do not commit environment-specific values; drive via env vars or `.env`.
- Lock file permissions on `wp-content/debug.log` (e.g., `0640`) and prevent web access via server rules.

## Logging Guidelines (PHP)
- Prefer `error_log( '[your-plugin] message...' );` with structured context (json-encoded arrays).
- Never `var_dump()`/`print_r()` to output for production; gate with `WP_DEBUG` checks.
- For plugins, consider a thin logger wrapper that gates by `WP_DEBUG` and adds prefixes and timestamps.
- Do not log secrets (API keys, tokens, PII). Mask or hash identifiers.
- Rotate logs and size-limit in ops; avoid massive single-file logs.

## JS Debugging in WP
- Enqueue unminified assets in dev (`SCRIPT_DEBUG` true). Provide source maps in builds.
- Use `wp.i18n` messages for user surface; `console.debug`/`console.warn` only in dev builds.
- Feature-flag verbose logging via a global like `window.__DEV__` or localized data gated by `WP_DEBUG`.

## Core Tools and Plugins
- **Query Monitor**: preferred for queries, hooks, HTTP calls, REST calls, enqueues, and capability checks.
- **Debug Bar** (alternative) + add-ons if Query Monitor not allowed.
- Use **Health Check & Troubleshooting** on staging to bisect plugin/theme conflicts without impacting visitors.
- Use **WP-CLI** for inspecting options, transients, cron, users, and running repair steps:
  ```bash
  wp option get home
  wp cron event list
  wp transient list
  wp cache flush
  ```

## Database Debugging
- Enable `SAVEQUERIES` **only on local/dev**. Inspect with Query Monitor or by dumping `$wpdb->queries` in a gated context.
- Use `$wpdb->print_error()` in guarded dev-only code paths. Never expose SQL errors to users.
- For slow queries, add temporary indexes on staging; verify with `EXPLAIN`. Remove after fix/merge.

## REST/AJAX Debugging
- Always return structured JSON errors with status codes.
- Include a stable `code` and human `message`; add a `trace_id` or `request_id` for cross-log correlation.
- Verify nonces and caps; log **why** a request failed, not the user’s secret payload.

## Deprecation and Doing-It-Wrong Notices
- In dev, surface deprecated usage:
  ```php
  add_filter( 'deprecated_function_trigger_error', '__return_true' );
  add_filter( 'deprecated_argument_trigger_error', '__return_true' );
  add_filter( 'deprecated_file_trigger_error', '__return_true' );
  add_filter( 'doing_it_wrong_trigger_error', '__return_true' );
  ```
- Fix upstream quickly; add shims where needed with clear `@deprecated` tags and removal plans.

## Error Handling Patterns
- Convert internal exceptions to WP_Error or WP REST responses at boundaries.
- Admin screens: show dismissible notices, not raw stack traces.
- CLI: allow full stack traces with `--debug` or environment flag.

## Performance Profiling
- Use Query Monitor timings, core hooks timeline, and HTTP calls panel.
- For deeper PHP profiling, use Xdebug or XHProf/ Tideways on local; never on prod traffic.
- Measure before/after; commit changes with brief PERF notes.

## Production Safeguards
- `WP_DEBUG_DISPLAY` must be `false` on staging/prod. Add CI/health check to verify.
- `SAVEQUERIES` must be `false` on prod.
- No `die()`/`exit` debugging remnants in committed code.
- No accidental `define( 'SCRIPT_DEBUG', true )` in production config.

## Unit/Integration Testing Tie-in
- Use `WP_UnitTestCase` for functional tests. Reproduce bugs with tests first when possible.
- Snapshot failing payloads in fixtures with secrets removed/masked.
- Gate noisy test logging to CI artifacts, not test output.

## Practical Snippets

### Guarded debug helper
```php
function wpmr_debug( $label, $data = null ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $msg = is_null( $data ) ? $label : $label . ' ' . wp_json_encode( $data );
        error_log( '[wpmr] ' . $msg );
    }
}
```

### REST error with trace id
```php
$trace_id = wp_generate_uuid4();
error_log( sprintf( '[wpmr][%s] auth_failed user=%d', $trace_id, get_current_user_id() ) );
return new WP_Error( 'wpmr_auth_failed', __( 'Authentication failed', 'your-td' ), [ 'status' => 403, 'trace_id' => $trace_id ] );
```

### wp-config quick toggle (local only)
```php
if ( getenv( 'WPMR_DEBUG_LOCAL' ) ) {
    define( 'WP_DEBUG', true );
    define( 'WP_DEBUG_LOG', true );
    define( 'WP_DEBUG_DISPLAY', false );
    define( 'SCRIPT_DEBUG', true );
    define( 'SAVEQUERIES', true );
}
```

## Checklist
- [ ] Correct `WP_ENVIRONMENT_TYPE` set for each environment
- [ ] `WP_DEBUG_DISPLAY` off on staging/prod
- [ ] Logs enabled and rotated; no secrets written
- [ ] Query Monitor available on non-prod
- [ ] REST/AJAX return structured errors with trace ids
- [ ] No `var_dump/print_r/die/exit` in committed code paths
- [ ] `SAVEQUERIES` only on local/dev
- [ ] Source maps for JS in dev; unminified assets via `SCRIPT_DEBUG`
