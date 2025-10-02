---
trigger: model_decision
description: Enforce WordPress database best practices for plugins and themes (schema, queries, performance, security, data lifecycle).
globs: |
  wp-content/plugins/**/*.php
  wp-content/themes/**/*.php
---
# WordPress Database Best Practices — Do's and Don'ts

## Core Principles
- Prefer core APIs first: `get_option()`, `update_option()`, `WP_Query`, metadata APIs, REST storage helpers.
- Use direct SQL only when core APIs cannot do the job **performantly** or safely.
- Always assume untrusted input. Sanitize, validate, and strictly type everything before it reaches SQL.

## Security (Non‑negotiable)
- Use `$wpdb->prepare( $query, ...$args )` for **every** dynamic query. No string concatenation.
- Escape LIKE patterns with `$wpdb->esc_like()` and then wrap with `%`.
- Whitelist sortable columns and directions. Never inject raw `ORDER BY`, `LIMIT`, `OFFSET`, or column names.
- Cast numerics with `absint()`, `(int)`, `(float)` before binding. Validate enums with whitelists.
- Block direct file access; check caps for any write path. Never expose raw SQL errors to users.

## Character Set & Collation
- Always use the site’s charset and collation from `$wpdb->charset` and `$wpdb->collate`.
- Ensure tables are `utf8mb4` ready. Avoid legacy `utf8`. Store emoji safely.

## Custom Tables (When and How)
- Create custom tables **only** when core tables/meta will not scale (large row counts, strict schema, heavy filtering).
- Table names must be prefixed: `$wpdb->prefix . 'your_table'` (multisite: `get_blog_prefix()` if per‑site).
- Primary key: `BIGINT(20) UNSIGNED AUTO_INCREMENT` or UUID/ULID (store as `BINARY(16)` when needed).
- Use appropriate types: numeric for money in **minor units** (cents) as `BIGINT` or `DECIMAL(20, 6)`. No floats for money.
- Timestamps in UTC (`DATETIME`) or integer epoch; be consistent.
- Keep rows narrow; avoid huge TEXT/BLOB columns. Offload large payloads to files or separate table.

## Schema Management
- Version your schema (e.g., `your_plugin_db_version` option). Migrate idempotently on upgrade.
- Use `dbDelta()` for create/alter, with a precise `CREATE TABLE` statement.
- Add necessary indexes: primary, unique, and **covering indexes** for frequent filters/sorts.
- Avoid over‑indexing. Each index costs writes and memory. Re‑evaluate with real query plans.
- Engine: InnoDB. Foreign keys optional; if used, ensure engine compatibility and careful cascading.

## Query Practices
- Use `SELECT columns` (no `SELECT *`). Return only what you need.
- Avoid `SQL_CALC_FOUND_ROWS` (deprecated/performance). Use a separate `COUNT(*)` or **keyset/seek** pagination.
- Prefer **keyset pagination** (WHERE id > last_id ORDER BY id ASC LIMIT N) for large data.
- Avoid leading‑wildcard searches (`%term`). Consider FULLTEXT or a search index.
- Chunk large `IN()` lists (e.g., batches of 500–1000) or use temp tables.
- Never run heavy queries on every page load. Defer to cron or admin‑only screens.

## Performance & Caching
- Batch reads/writes. Avoid per‑row queries inside loops.
- Use object cache APIs (`wp_cache_get/set/delete`) for expensive reads and invalidate on writes.
- Use transients for short‑lived caches; use **site transients** for multisite‑wide data.
- Do not autoload heavy options. Set `autoload => 'no'` for large settings payloads.
- Measure with Query Monitor and real data. Add indices only when a measured query is slow.

## Transactions & Concurrency
- InnoDB supports transactions. Wrap related writes:
  ```php
  $wpdb->query('START TRANSACTION');
  // ... do prepared writes ...
  $ok = /* your checks */;
  $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' );
  ```
- Keep transactions **short**. Avoid user think time in a transaction.
- For counters/state, consider `INSERT ... ON DUPLICATE KEY UPDATE` or atomic updates.
- Handle deadlocks by retrying the block once.

## Options, Meta, and Data Placement
- Options: key/value, small payloads. Do **not** store large arrays/objects; avoid autoload for big entries.
- Post/User/Term meta: flexible but can be slow at scale for filtered queries. Add custom tables if you filter/sort by those fields often.
- Logs/analytics/events: write to a dedicated table or external datastore, not to options/meta.

## Data Lifecycle & Privacy
- Define retention. Use scheduled cleanups for logs and temp data.
- On uninstall, remove plugin‑owned data **only if the user opts in**. Provide a UI.
- Anonymize PII when possible; encrypt secrets at rest if stored (consider salts/keys).

## Do and Do Not

### Do
- `prepare()` every query, whitelist columns, cast all numbers.
- Use `dbDelta()` for schema changes; keep a schema version.
- Add the minimal necessary indexes; test with EXPLAIN.
- Use transients/object cache for expensive aggregate queries.
- Use keyset pagination for big tables.
- Use `$wpdb->query( 'SET SESSION sql_big_selects=1' )` only as a last resort during admin tasks.

### Do Not
- Do not concatenate SQL with user input.
- Do not run heavy reports on front‑end requests.
- Do not store megabyte‑sized blobs in options/meta.
- Do not rely on `FOUND_ROWS()` or `SQL_CALC_FOUND_ROWS`.
- Do not use `TEXT` columns as join keys; normalize and index properly.

## Examples

### Safe LIKE search
```php
$term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$like = '%' . $wpdb->esc_like( $term ) . '%';
$sql  = $wpdb->prepare(
    "SELECT id, name FROM {$table} WHERE name LIKE %s ORDER BY name ASC LIMIT %d",
    $like,
    50
);
$rows = $wpdb->get_results( $sql, ARRAY_A );
```

### Keyset pagination
```php
$last_id = isset( $_GET['after'] ) ? absint( $_GET['after'] ) : 0;
$sql = $wpdb->prepare(
    "SELECT id, created_at FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT %d",
    $last_id,
    100
);
```

### Idempotent schema upgrade
```php
$current = get_option( 'my_plugin_db_version', '0' );
$target  = '1.3.0';

if ( version_compare( $current, $target, '<' ) ) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'my_items';
    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        total_cents BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY user_created (user_id, created_at)
    ) {$charset_collate};";
    dbDelta( $sql );
    update_option( 'my_plugin_db_version', $target );
}
```

## Checklist
- [ ] Every dynamic query uses $wpdb->prepare()
- [ ] Charset/collation set from $wpdb; utf8mb4 ready
- [ ] Schema versioned; dbDelta used for create/alter
- [ ] Indexes reviewed with EXPLAIN; no over‑indexing
- [ ] No heavy queries on front‑end; caches in place
- [ ] Keyset pagination for large result sets
- [ ] Options not autoloaded if big; data retention policy set
- [ ] Uninstall cleanup opt‑in implemented
