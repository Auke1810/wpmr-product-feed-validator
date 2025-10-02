# Developer Documentation - WPMR Product Feed Validator

This document provides technical details for developers working with or extending the WPMR Product Feed Validator plugin.

## Architecture Overview

### Core Components

```
wpmr-product-feed-validator/
├── includes/
│   ├── Admin/           # Admin interface and settings
│   ├── Public/          # Frontend shortcode and forms
│   ├── REST/            # API endpoints and controllers
│   └── Services/        # Business logic and utilities
├── assets/              # CSS, JS, and other assets
├── blocks/              # Gutenberg blocks
├── languages/           # Translation files
└── rules/               # Validation rule definitions
```

### Service Layer

- **Fetcher**: Handles HTTP requests with SSRF protection
- **Parser**: XML streaming parser with sampling support
- **Rules**: Rule loading and management system
- **RulesEngine**: Validation execution engine
- **Scoring**: Result calculation and aggregation
- **Abuse**: Security controls and rate limiting
- **Email**: Email delivery with templates
- **Reports**: Report storage and public URLs

## API Reference

### REST API Endpoints

#### Validate Feed
```
POST /wp-json/wpmr/v1/validate
```

**Parameters:**
```json
{
  "url": "https://example.com/feed.xml",
  "email": "user@example.com",
  "consent": true,
  "sample": true,
  "captcha_token": "captcha_response"
}
```

**Response:**
```json
{
  "delivery_mode": "email_plus_display",
  "message": "Validation request accepted...",
  "report": {
    "rule_version": "google-v2025-09",
    "items_scanned": 500,
    "score": 85,
    "issues": [...],
    "transport": {...}
  },
  "report_id": 123,
  "public_key": "abc123",
  "public_endpoint": "https://site.com/feed-report/abc123"
}
```

#### Run Tests
```
GET /wp-json/wpmr/v1/tests
```

Returns automated test results for validation rules and performance.

#### Public Reports
```
GET /wp-json/wpmr/v1/reports/{public_key}
```

Retrieves shareable validation reports.

### PHP Classes

#### Core Classes

```php
use WPMR\PFV\Services\Parser;
use WPMR\PFV\Services\Rules;
use WPMR\PFV\Services\RulesEngine;
use WPMR\PFV\Services\Scoring;
```

#### Parser Usage

```php
// Parse a feed with sampling
$result = Parser::parse_sample($xml_content, [
    'sample' => true,
    'sample_size' => 500
]);

if (is_wp_error($result)) {
    // Handle error
} else {
    $items = $result['items'];
    $diagnostics = $result['diagnostics'];
}
```

#### Rules Engine Usage

```php
// Load rulepack
$pack = Rules::load_rulepack('google-v2025-09');

// Get effective rules (with overrides)
$overrides = Rules::get_overrides('google-v2025-09');
$effective = Rules::effective_rules($pack, $overrides);

// Validate items
$issues = RulesEngine::evaluate($items, $transport_diag, $pack, $effective);
```

#### Scoring Usage

```php
// Calculate score
$score_data = Scoring::compute($issues, $pack);

// Results
$score = $score_data['score'];        // 0-100
$totals = $score_data['totals'];      // errors, warnings, advice counts
```

## Rule System

### Rulepack Format

Rulepacks are JSON files defining validation rules:

```json
{
  "version": "google-v2025-09",
  "rules": {
    "rule_id": {
      "name": "Rule Display Name",
      "description": "Detailed rule description",
      "severity": "error|warning|advice",
      "category": "structure|required|identifiers|...",
      "validate": "callback_function_name",
      "weight": 7
    }
  },
  "weights": {
    "error": 7,
    "warning": 3,
    "advice": 1,
    "cap_per_category": 20
  },
  "categories": {
    "structure": "XML and feed structure",
    "required": "Required product attributes",
    "identifiers": "Product identification"
  }
}
```

### Creating Custom Rules

#### Method 1: Filter Hook

```php
add_filter('gpfv_rules', function($rules) {
    $rules['custom_price_format'] = [
        'name' => 'Custom Price Format',
        'description' => 'Validate custom price formatting requirements',
        'severity' => 'warning',
        'category' => 'pricing',
        'validate' => function($item, $context) {
            $price = $item['price'] ?? '';
            // Custom validation logic
            if (!preg_match('/^\d+\.\d{2} [A-Z]{3}$/', $price)) {
                return new WP_Error('invalid_price_format', 'Price must be in format: 123.45 EUR');
            }
            return true;
        },
        'weight' => 3
    ];
    return $rules;
});
```

#### Method 2: Custom Rulepack

Create `/rules/custom-v1.0.json`:

```json
{
  "version": "custom-v1.0",
  "rules": {
    "custom_validation": {
      "name": "Custom Business Rule",
      "description": "Validates business-specific requirements",
      "severity": "error",
      "category": "business",
      "validate": "custom_validate_function",
      "weight": 10
    }
  }
}
```

Then load it programmatically:

```php
$custom_pack = Rules::load_rulepack('custom-v1.0');
```

### Rule Validation Functions

Validation functions receive:

```php
function custom_validate_function($item, $context) {
    /*
    $item: Array of product data from parser
    $context: Array with additional context:
        - 'rule_version': Current rule version
        - 'transport': Transport diagnostics
        - 'all_items': Array of all parsed items (for cross-item validation)
    */

    // Return true for valid, WP_Error for invalid
    if ($item['custom_field'] !== 'expected_value') {
        return new WP_Error('custom_error', 'Custom validation failed');
    }

    return true;
}
```

## Hooks and Filters

### Core Filters

#### `gpfv_rules`
Modify the rules array before validation.

```php
add_filter('gpfv_rules', function($rules, $version) {
    // Add, modify, or remove rules
    unset($rules['unwanted_rule']);
    return $rules;
}, 10, 2);
```

#### `gpfv_rule_overrides`
Apply rule severity and weight overrides.

```php
add_filter('gpfv_rule_overrides', function($overrides, $version) {
    // Override rule settings
    $overrides['price_format']['severity'] = 'warning';
    $overrides['price_format']['weight'] = 5;
    return $overrides;
}, 10, 2);
```

#### `gpfv_validation_result`
Filter final validation results.

```php
add_filter('gpfv_validation_result', function($result, $items, $issues) {
    // Modify result before returning to user
    $result['custom_score'] = calculate_custom_score($issues);
    return $result;
}, 10, 3);
```

### Email Filters

#### `gpfv_email_templates`
Customize email subject and body templates.

```php
add_filter('gpfv_email_templates', function($templates) {
    $templates['subject'] = 'Your Custom Subject: {score}/100';
    $templates['body'] = 'Custom body with {url} and {errors} errors.';
    return $templates;
});
```

#### `gpfv_email_data`
Modify email data before sending.

```php
add_filter('gpfv_email_data', function($data) {
    $data['attachments'][] = '/path/to/additional/file.pdf';
    return $data;
});
```

### Security Filters

#### `gpfv_rate_limits`
Modify rate limiting settings.

```php
add_filter('gpfv_rate_limits', function($limits) {
    $limits['ip_per_day'] = 100;      // Increase IP limit
    $limits['email_per_day'] = 50;    // Increase email limit
    return $limits;
});
```

#### `gpfv_blocklist`
Add custom blocking rules.

```php
add_filter('gpfv_blocklist', function($blocklist) {
    $blocklist[] = 'bad-domain.com';
    $blocklist[] = '192.168.1.100';
    return $blocklist;
});
```

## Actions

### Validation Actions

#### `gpfv_before_validation`
Fired before validation begins.

```php
add_action('gpfv_before_validation', function($url, $options) {
    // Log validation attempt
    error_log("Validating feed: $url");
});
```

#### `gpfv_after_validation`
Fired after validation completes.

```php
add_action('gpfv_after_validation', function($result, $url, $issues) {
    // Custom post-validation logic
    if ($result['score'] < 70) {
        notify_admin_about_poor_feed($url, $result['score']);
    }
});
```

### Report Actions

#### `gpfv_report_created`
Fired when a report is saved to database.

```php
add_action('gpfv_report_created', function($report_id, $report_data) {
    // Custom report processing
    update_external_system($report_id, $report_data);
});
```

#### `gpfv_email_sent`
Fired after email is sent.

```php
add_action('gpfv_email_sent', function($recipient, $subject, $report_data) {
    // Track email delivery
    log_email_sent($recipient, $report_data['score']);
});
```

## Database Schema

### Reports Table
```sql
CREATE TABLE wp_feed_validator_reports (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    url varchar(2000) NOT NULL,
    email varchar(100) NOT NULL,
    rule_version varchar(50) NOT NULL,
    items_scanned int unsigned NOT NULL,
    score tinyint unsigned NOT NULL,
    totals_json text NOT NULL,
    issues_json longtext NOT NULL,
    transport_json text NOT NULL,
    format varchar(10) NOT NULL,
    missing_id_count int unsigned NOT NULL DEFAULT 0,
    duplicates_json text NOT NULL,
    override_count smallint unsigned NOT NULL DEFAULT 0,
    public_key varchar(64) DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY url (url(191)),
    KEY email (email),
    KEY public_key (public_key),
    KEY created_at (created_at)
) ENGINE=InnoDB;
```

### Rule Overrides Table
```sql
CREATE TABLE wp_feed_validator_rule_overrides (
    rule_version varchar(50) NOT NULL,
    rule_id varchar(100) NOT NULL,
    severity varchar(10) DEFAULT NULL,
    weight smallint unsigned DEFAULT NULL,
    enabled tinyint(1) DEFAULT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (rule_version, rule_id),
    KEY updated_at (updated_at)
) ENGINE=InnoDB;
```

## Extending Admin Interface

### Adding Settings Sections

```php
add_action('admin_init', function() {
    add_settings_section(
        'custom_section',
        'Custom Settings',
        function() { echo '<p>Custom validation settings</p>'; },
        'wpmr-pfv-settings'
    );

    add_settings_field(
        'custom_field',
        'Custom Field',
        function() {
            $value = get_option('wpmr_pfv_custom_field');
            echo '<input type="text" name="wpmr_pfv_custom_field" value="' . esc_attr($value) . '">';
        },
        'wpmr-pfv-settings',
        'custom_section'
    );
});
```

### Custom Admin Pages

```php
add_action('admin_menu', function() {
    add_submenu_page(
        'wpmr-pfv-main',
        'Custom Page',
        'Custom Page',
        'manage_options',
        'wpmr-pfv-custom',
        function() {
            // Custom admin page content
            echo '<div class="wrap"><h1>Custom Admin Page</h1></div>';
        }
    );
});
```

## Testing

### Running Unit Tests

```bash
# Via REST API (admin only)
GET /wp-json/wpmr/v1/tests
```

### Writing Custom Tests

```php
// Add to Tests_Controller
protected function test_custom_functionality() : array {
    // Test logic here
    $passed = your_test_function();

    return [
        'name' => 'Custom functionality test',
        'passed' => $passed,
        'details' => ['additional_info' => 'value']
    ];
}
```

## Performance Optimization

### Memory Management

For large feeds, the parser uses streaming XML processing:

```php
// Force garbage collection after large operations
if (function_exists('gc_collect_cycles')) {
    gc_collect_cycles();
}
```

### Caching Strategies

```php
// Cache rulepack loading
$cache_key = 'gpfv_rules_' . $version;
$rules = wp_cache_get($cache_key);
if (!$rules) {
    $rules = load_rules_from_file($version);
    wp_cache_set($cache_key, $rules, 'gpfv', HOUR_IN_SECONDS);
}
```

## Security Considerations

### Input Validation

All user inputs are validated:

```php
$url = esc_url_raw(wp_unslash($_POST['url']));
$email = sanitize_email(wp_unslash($_POST['email']));
```

### Nonce Verification

All forms include nonce protection:

```php
wp_nonce_field('wpmr_pfv_validate', 'wpmr_pfv_nonce');
```

### SSRF Protection

The fetcher includes SSRF protection:

```php
// Private IP ranges are blocked
$private_ranges = [
    '10.0.0.0/8',
    '172.16.0.0/12',
    '192.168.0.0/16',
    '127.0.0.0/8'
];
```

## Error Handling

### Custom Error Codes

```php
return new WP_Error(
    'custom_error_code',
    __('Custom error message', 'wpmr-product-feed-validator'),
    ['status' => 400]
);
```

### Logging

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('GFV Debug: ' . $message);
}
```

## Migration Guide

### Upgrading from Previous Versions

1. **Database Migration**: Run activation hook to create new tables
2. **Settings Migration**: Old settings are automatically migrated
3. **Rule Updates**: New rule versions are applied automatically

### Backward Compatibility

- REST API responses maintain backward compatibility
- Shortcode parameters are backward compatible
- Old filter hooks continue to work

This documentation covers the core extension points and integration methods for the WPMR Product Feed Validator plugin.
