---
trigger: model_decision
description: Enforce best practices for WordPress plugin development (security, i18n, performance, structure).
globs: |
  wp-content/plugins/**/*.php
  wp-content/mu-plugins/**/*.php
---
# WordPress Plugin Development — Mandatory Rules

## Context
These rules standardize plugin development for reliability, security, performance, and maintainability. They align with: WordPress Coding Standards (WPCS), PHPCompatibilityWP, and modern WP APIs (REST, Blocks, Script Modules). Apply in combination with PHPCS and automated checks.

## Tooling (Required)
- PHPCS with WordPress, WordPress-Core, WordPress-Extra, WordPress-Docs, PHPCompatibilityWP
- Composer dev deps:
  ```bash
  composer require --dev wp-coding-standards/wpcs phpcompatibility/phpcompatibility-wp dealerdirect/phpcodesniffer-composer-installer squizlabs/php_codesniffer
  ```
- Run checks:
  ```bash
  vendor/bin/phpcs --standard=WordPress --extensions=php --report=full wp-content/plugins/
  vendor/bin/phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 7.4-8.3 wp-content/plugins/
  ```

## Project Structure (Must)
- Each plugin has one main file with header (Name, URI, Description, Version, Author, Text Domain, Requires at least, Requires PHP).
- Autoload classes via Composer PSR-4 (no global function clutter).
- Prefix all namespaces, classes, functions, constants, and options e.g. WPMR\Feed\..., wpmr_.
- Separate layers: inc/ (core), admin/, public/, assets/, languages/, templates/.
- Do not execute logic at load. Bootstrap via hooks only.

## Security (Non-negotiable)
- Capabilities: Gate admin actions with current_user_can( 'manage_options' ) or the least power needed.
- Nonces: For all state-changing requests. Verify with check_admin_referer()/wp_verify_nonce().
- Escaping: Escape on output using the right function: esc_html(), esc_attr(), esc_url(), wp_kses().
- Sanitization: Sanitize on input: sanitize_text_field(), sanitize_key(), absint(), sanitize_email(), etc.
- Prepared statements for $wpdb via $wpdb->prepare(); never inject vars into SQL.
- File ops: Use WP Filesystem API. Block direct access with defined( 'ABSPATH' ) || exit;.
- REST: Use permission_callback. Validate and sanitize schema/params.
- CSRF/XSS: No inline unescaped user content. Escape everything before rendering.
- Unserialized input: Never trust maybe_unserialize on user data.

## Internationalization (Must)
- Load text domain: load_plugin_textdomain( 'your-text-domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
- Wrap strings: __( 'Text', 'your-text-domain' ), esc_html__(), _x(), _n().
- Text domain equals plugin slug.
- No string concatenation for translatable pieces when placeholders could be used: sprintf( __( 'Found %d items', 'td' ), $count ).

## Performance
- No heavy work on every request: defer to cron/actions; cache with transients or object cache.
- Use non-blocking admin pages; paginate lists; lazy-load data.
- Enqueue assets only on needed screens; avoid global admin enqueue.
- Do not autoload large options (autoload => 'no' for heavy settings).

## Assets & Enqueue
- Register & enqueue with dependencies and versions (for cache busting):
  ```php
  wp_register_script( 'wpmr-admin', plugins_url( 'assets/admin.js', __FILE__ ), [ 'jquery' ], WPMR_VERSION, true );
  wp_enqueue_script( 'wpmr-admin' );
  wp_register_style( 'wpmr-admin', plugins_url( 'assets/admin.css', __FILE__ ), [], WPMR_VERSION );
  wp_enqueue_style( 'wpmr-admin' );
  ```
- Localize data and nonces with wp_localize_script() or wp_add_inline_script() (JSON-encoded).
- For modern builds, prefer wp_enqueue_script_module() for ESM where supported.

## Activation/Deactivation/Uninstall
- Use register_activation_hook(), register_deactivation_hook(); uninstall.php for clean removal.
- On uninstall: remove only plugin-owned data if the user opts in; never delete user content silently.
- DB schema: version your schema; run upgrades idempotently.

## Admin UI/UX
- Use Settings API. Group settings pages under a clear top-level or Tools/Settings.
- Use List Table pattern for data lists; Screen Options & Help Tabs where helpful.
- Follow WordPress accessibility (labels, ARIA, color contrast).

## Blocks / Editor Integration
- Use block.json. Build with @wordpress/scripts or WP-supported build.
- i18n JS via wp_set_script_translations().
- Keep block server-side rendering secure with escaping.

## Coding Standards
- Follow WPCS. Prefer Yoda conditions if your project standard requires them; be consistent.
- Strict comparisons where possible (===, !==). Avoid loose truthiness for user input.
- Short array syntax; early returns; small pure functions; dependency injection for services.

## Examples

### Bad (unsecured admin post)
```php
if ( isset( $_POST['action'] ) ) {
  update_option( 'wpmr_key', $_POST['key'] );
}
```

### Good
```php
if ( isset( $_POST['wpmr_save'] ) && check_admin_referer( 'wpmr_save_settings' ) ) {
  $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
  if ( current_user_can( 'manage_options' ) ) {
    update_option( 'wpmr_key', $key, false );
  }
}
```

## PHPCS Config (template)
```xml
<?xml version="1.0"?>
<ruleset name="Plugin">
  <arg name="basepath" value="."/>
  <file>./wp-content/plugins/</file>
  <rule ref="WordPress"/>
  <rule ref="WordPress-Docs"/>
  <rule ref="PHPCompatibilityWP"/>
  <config name="testVersion" value="7.4-8.3"/>
</ruleset>
```

## Mandatory Checklist
- [ ] Proper headers, text domain, namespacing/prefixing
- [ ] Nonces + capability checks on all writes
- [ ] Sanitize on input; escape on output
- [ ] REST endpoints: validate + permission_callback
- [ ] Enqueue only where needed; version assets
- [ ] i18n for all user-facing strings
- [ ] PHPCS + PHPCompatibilityWP pass with 0 errors
- [ ] Clean uninstall path (opt-in)
