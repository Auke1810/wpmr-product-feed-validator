---
trigger: model_decision
description: Enforce PHP coding standards for WordPress projects using WPCS and PHPCompatibilityWP.
globs: |
  **/*.php
---
# PHP Coding Standards for WordPress

## Context
This rule complements plugin/theme rules by defining PHP style and safety requirements compatible with WordPress core and modern PHP (7.4-8.3).

## Standards & Linters (Required)
- PHPCS standards: WordPress, WordPress-Extra, WordPress-Docs
- Compatibility: PHPCompatibilityWP (target 7.4-8.3)
- Enforce via CI on every commit/PR.

## Style Essentials
- Indentation: 4 spaces; LF line endings; UTF-8.
- Namespacing recommended in plugins; prefix functions/constants when not using namespaces.
- Strict comparisons (===, !==). Cast explicitly ((int), (string)).
- Yoda conditions if your standard requires; otherwise be consistent project-wide.
- Short arrays []; trailing commas in multi-line arrays.
- Early returns; small single-purpose functions; limit cyclomatic complexity.
- DocBlocks for public symbols; describe filters/actions thoroughly (@since, @param, @return).

## Security Essentials
- Sanitize input, escape output, validate capability on actions.
- Always wp_unslash() superglobals before sanitizing.
- $wpdb->prepare() for dynamic SQL; never build raw SQL with untrusted input.
- Avoid eval(), create_function(), direct include of user-controlled paths.

## Performance & Memory
- Avoid heavy work on init/plugins_loaded; lazy-load services.
- Cache with transients/object cache where applicable.
- Do not autoload big options; batch DB calls; prefer generators/iterators for large sets.

## Error Handling
- Never silence errors with @. Use WP_DEBUG_LOG in dev environments.
- Throw exceptions in libraries; convert to admin notices/API errors at boundaries.
- Internationalize error messages presented to users.

## Examples

### Bad
```php
$value = $_GET['id'];
update_option( 'k', $value ); // unvalidated
```

### Good
```php
$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
if ( $id > 0 && current_user_can( 'manage_options' ) ) {
    update_option( 'k', $id );
}
```

## PHPCS Template (phpcs.xml.dist)
```xml
<?xml version="1.0"?>
<ruleset name="WP">
  <file>./</file>
  <rule ref="WordPress"/>
  <rule ref="WordPress-Docs"/>
  <rule ref="PHPCompatibilityWP"/>
  <config name="testVersion" value="7.4-8.3"/>
  <arg name="extensions" value="php"/>
  <exclude-pattern>vendor/*</exclude-pattern>
</ruleset>
```

## Mandatory Checklist
- [ ] PHPCS (WordPress + Docs) passes
- [ ] PHPCompatibilityWP passes
- [ ] No unescaped output; all input sanitized
- [ ] No direct SQL without $wpdb->prepare()
- [ ] CI enforces standards on PRs
