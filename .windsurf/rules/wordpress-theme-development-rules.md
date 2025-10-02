---
trigger: model_decision
description: Enforce best practices for WordPress theme development (performance, security, templates, block theme).
globs: |
  wp-content/themes/**/*.php
  wp-content/themes/**/*.html
---
# WordPress Theme Development — Mandatory Rules

## Context
Themes control presentation. They must be fast, accessible, translatable, block-editor friendly, and avoid business logic. Security and performance apply equally.

## Tooling
- PHPCS with WordPress-Core/Extra/Docs
- Theme Check (CLI or plugin)
- Performance: use Query Monitor, Lighthouse/Pagespeed
- Build: @wordpress/scripts for blocks/patterns; CSS via PostCSS

## Structure
- For classic themes: functions.php, templates (index.php, single.php, etc.), template-parts/, assets/, languages/.
- For block themes: theme.json, templates/*.html, parts/*.html, patterns/, styles/*.json.
- Keep no business logic in theme. Use plugins for features/data storage.

## Performance
- Avoid N+1 queries in loops; prefer pre_get_posts over heavy queries in templates.
- Use wp_enqueue_* with versions; no inline massive CSS/JS.
- Defer noncritical assets; only load what is used on the current template.
- Use responsive images: the_post_thumbnail() sizes, srcset, sizes attributes.

## Security & Data Handling
- Escape everything in templates: esc_html(), esc_attr(), esc_url(), wp_kses_post().
- Never trust get_query_var()/$_GET directly; sanitize before use.
- Do not process form submissions in templates; offload to plugin or a handler hooked in.

## Accessibility (A11y)
- Proper heading order; labeled controls; focus styles; color contrast AA.
- Keyboard navigation for menus; aria-* attributes as needed.
- Use screen-reader-text class where appropriate.

## Internationalization
- Load text domain in functions.php via load_theme_textdomain( 'td', get_template_directory() . '/languages' );
- Wrap all strings. Avoid concatenation when placeholders are better.

## Templates & Loop
- Use get_template_part() / block patterns for reuse.
- Never call query_posts() (use WP_Query or pre_get_posts).
- Pagination via the_posts_pagination(); escape URLs.

## Block Themes
- Drive design via theme.json (colors, spacing, typography).
- Provide patterns; avoid custom code for what blocks solve.
- Use wp_set_script_translations for JS i18n.
- Keep template parts small and composable.

## Examples

### Bad (logic in template)
```php
<?php
if ( isset( $_POST['email'] ) ) {
  // saving to options
  update_option( 'newsletter', $_POST['email'] );
}
?>
```

### Good
- Handle form in a plugin or init/admin_post_* with nonces/cap checks.
- Templates only render sanitized, escaped data.

## Required Checks
- Theme Check passes with no errors
- PHPCS (WordPress rules) clean
- Lighthouse: CLS/LCP/INP within green thresholds on sample pages

## Checklist
- [ ] Proper structure (classic or block) and theme.json where applicable
- [ ] All output escaped; no option writes in templates
- [ ] i18n for all visible strings
- [ ] Responsive images and pagination
- [ ] Avoid query_posts(); performant queries
- [ ] Patterns/parts for reuse; no duplicated markup
