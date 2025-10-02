# WPMR Product Feed Validator

A comprehensive WordPress plugin for validating Google Shopping product feeds against the latest Google Merchant Center requirements (v2025-09).

## Features

- **Real-time Validation**: Validate product feeds instantly with detailed error reports
- **Google Merchant Center Compliance**: Tests against the latest GMC validation rules
- **Intelligent Sampling**: Handles large feeds efficiently by sampling first 500 products
- **Security Controls**: Rate limiting, CAPTCHA support, and abuse prevention
- **Multiple Delivery Modes**: Email-only or email-plus-display options
- **Public Reports**: Shareable validation reports with unique URLs
- **Admin Rule Management**: Customize validation rules and severity levels
- **Accessibility Compliant**: WCAG AA compliant interface with screen reader support

## Installation

1. Upload the `wpmr-product-feed-validator` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure settings under **WPMR → Product Feed Validator**

## Admin Settings

### General Settings

- **Delivery Mode**: Choose between "Email Only" or "Email Plus Display"
  - Email Only: User receives report via email
  - Email Plus Display: Email + immediate results shown on page

- **Require Consent**: Enable to require explicit user consent before validation

- **Attach CSV**: Include detailed CSV report with email

### Rate Limiting

- **IP Limit per Day**: Maximum validations per IP address (default: 50)
- **Email Limit per Day**: Maximum validations per email address (default: 20)

Configure these limits to prevent abuse while allowing legitimate usage.

### Security Settings

- **CAPTCHA Provider**: Choose between reCAPTCHA v2 or Cloudflare Turnstile
- **CAPTCHA Site Key & Secret**: Enter your CAPTCHA credentials

### File Processing

- **Max File Size**: Maximum feed size in MB (default: 100MB)
- **Timeout**: Request timeout in seconds (default: 20)
- **Redirect Cap**: Maximum redirects to follow (default: 3)
- **Sample Size**: Number of products to sample (default: 500)

### Email Templates

Customize email subject and body templates using placeholders:

- `{url}` - The feed URL that was validated
- `{score}` - Validation score (0-100)
- `{items_scanned}` - Number of products scanned
- `{errors}` - Total error count
- `{warnings}` - Total warning count
- `{date}` - Current date/time
- `{rule_version}` - GMC rule version used
- `{override_count}` - Number of rule overrides applied

### Webhook Integration

- **Webhook URL**: Optional n8n or Zapier webhook endpoint
- **Data Format**: JSON payload with validation results

## Usage

### For Site Visitors

1. Visit any page with the `[feed_validator]` shortcode or Feed Validator block
2. Enter your product feed URL
3. (If required) Provide email address and consent
4. (If enabled) Complete CAPTCHA verification
5. Submit for validation

### For Administrators

#### Rule Manager

Access the Rule Manager under **WPMR → Product Feed Validator → Rules** to:

- View all validation rules with descriptions
- Enable/disable individual rules
- Adjust severity levels (Error, Warning, Advice)
- Modify rule weights and category caps
- Import/export rule configurations

#### Report Management

- View validation history in the admin
- Access public report URLs for sharing
- Monitor usage statistics

## Validation Rules

The plugin validates against comprehensive Google Merchant Center requirements including:

### Transport Layer
- HTTP status codes
- Content-type validation
- File size limits
- Redirect handling

### Structure Validation
- XML well-formedness
- Required item elements (`<item>` or `<entry>`)
- Product ID uniqueness
- Namespace compliance

### Required Attributes
- Product ID (`g:id`)
- Title (`g:title`)
- Description (`g:description`)
- Link (`g:link`)
- Image link (`g:image_link`)
- Availability (`g:availability`)
- Price (`g:price`)

### Data Quality Rules
- URL format validation
- Price format and logic
- Category taxonomy compliance
- Identifier validation (GTIN, MPN, brand)
- Shipping information accuracy
- Tax rate validation

## Scoring System

- **Base Score**: 100 points
- **Error Penalty**: -7 points each
- **Warning Penalty**: -3 points each
- **Advice Penalty**: -1 point each
- **Category Caps**: Maximum penalties per error category
- **Floor Score**: 0 (minimum)

## Public API

### REST Endpoints

```
POST /wp-json/wpmr/v1/validate
GET /wp-json/wpmr/v1/tests
GET /wp-json/wpmr/v1/reports/{public_key}
```

### Shortcode Usage

```php
[feed_validator sample="true"]
```

Parameters:
- `sample`: Whether to sample large feeds (default: true)

### Gutenberg Block

Use the "Feed Validator" block in the block editor for easy placement.

## Security Features

- **Rate Limiting**: Prevents abuse with configurable IP/email limits
- **CAPTCHA Integration**: Supports reCAPTCHA and Cloudflare Turnstile
- **Input Sanitization**: All user inputs are validated and sanitized
- **Nonces**: CSRF protection on all forms
- **Blocklist**: IP, email domain, and email address blocking

## Performance

- **Streaming Parser**: Uses XMLReader for memory-efficient parsing
- **Sampling**: Only processes first 500 items by default
- **Caching**: Transient-based rate limiting
- **Timeouts**: Configurable request timeouts

## Troubleshooting

### Common Issues

**Validation Timeout**
- Increase timeout setting in admin
- Check feed server response time
- Consider feed size limits

**Memory Issues**
- Reduce sample size in settings
- Increase PHP memory limit
- Use smaller test feeds first

**Email Not Sending**
- Configure SMTP in WordPress
- Check spam folders
- Verify email templates

**CAPTCHA Errors**
- Verify site keys and secrets
- Check CAPTCHA service status
- Clear browser cache

### Error Codes

- `wpmr_pfv_missing_url`: No URL provided
- `wpmr_pfv_invalid_email`: Invalid email format
- `wpmr_pfv_missing_consent`: Consent required but not given
- `wpmr_pfv_rate_limited_ip`: IP rate limit exceeded
- `wpmr_pfv_rate_limited_email`: Email rate limit exceeded
- `wpmr_pfv_blocked`: Request blocked by security rules
- `wpmr_pfv_xml_invalid`: Feed is not valid XML

## Development

### Adding Custom Rules

```php
add_filter('gpfv_rules', function($rules) {
    $rules['custom_rule'] = [
        'name' => 'Custom Validation Rule',
        'description' => 'Description of custom rule',
        'severity' => 'warning',
        'category' => 'custom',
        'validate' => function($item, $context) {
            // Validation logic here
            return true; // or WP_Error
        }
    ];
    return $rules;
});
```

### Hooks and Filters

- `gpfv_rules`: Modify validation rules
- `gpfv_rule_overrides`: Override rule settings
- `gpfv_email_templates`: Customize email content
- `gpfv_validation_result`: Filter validation results

## Support

For support and feature requests, please contact the plugin author or check the documentation at [WPMR Website].

## Changelog

### v0.1.0
- Initial release
- Google Merchant Center v2025-09 compliance
- Admin settings and rule management
- Public validation interface
- Email and webhook delivery
- Security and rate limiting features

## License

GPL-2.0-or-later
