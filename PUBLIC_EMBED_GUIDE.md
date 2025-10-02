# Public Embed Guide - WPMR Product Feed Validator

This guide explains how to embed the Product Feed Validator on your website for public use.

## Shortcode Usage

The simplest way to add the validator to any page or post is using the shortcode:

```php
[feed_validator]
```

### Shortcode Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `sample` | `true` | Whether to sample large feeds (recommended) |

### Examples

**Basic usage:**
```php
[feed_validator]
```

**Disable sampling (process all items):**
```php
[feed_validator sample="false"]
```

## Gutenberg Block Usage

For sites using the Gutenberg editor:

1. Add a new block to your page/post
2. Search for "Feed Validator"
3. Insert the Feed Validator block
4. Configure options in the block settings

### Block Settings

- **Sample Large Feeds**: Enable to only validate first 500 products (recommended for performance)

## Page Template Integration

For direct integration into custom page templates:

```php
<?php
// In your page template
echo do_shortcode('[feed_validator]');
?>
```

## Styling Customization

The validator comes with default styling, but you can customize the appearance:

### CSS Classes

```css
/* Main form container */
.wpmr-pfv-form { }

/* Field groups */
.wpmr-pfv-field-group { }

/* Labels */
.wpmr-pfv-label { }

/* Required field indicators */
.wpmr-pfv-label .required { }

/* Submit button */
.wpmr-pfv-submit-button { }

/* Result display area */
.wpmr-pfv-result { }

/* Error messages */
.wpmr-pfv-error { }

/* Success messages */
.wpmr-pfv-success { }
```

### Custom CSS Example

```css
/* Custom button styling */
.wpmr-pfv-submit-button {
    background: linear-gradient(45deg, #24c261, #1ea956);
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Custom form styling */
.wpmr-pfv-form {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}
```

## JavaScript Customization

The validator includes JavaScript for form handling and results display. You can extend this functionality:

```javascript
// Custom form submission handler
document.addEventListener('wpmr-pfv-submit', function(event) {
    // Your custom logic here
    console.log('Form submitted with URL:', event.detail.url);
});

// Custom result display handler
document.addEventListener('wpmr-pfv-result', function(event) {
    // Your custom result handling
    console.log('Validation completed:', event.detail);
});
```

## Configuration Options

### Admin Settings Affecting Public Display

**Delivery Mode:**
- **Email Only**: Users receive results via email
- **Email Plus Display**: Email + immediate on-page results

**Consent Requirements:**
- Enable to require explicit consent before validation

**CAPTCHA:**
- Configure reCAPTCHA or Cloudflare Turnstile for spam prevention

**Rate Limiting:**
- IP and email-based rate limits to prevent abuse

## Form Fields

The validator form includes the following fields (depending on configuration):

### Always Present
- **Feed URL**: Required URL field for the product feed

### Conditional Fields
- **Email Address**: Required when not logged in (unless consent disabled)
- **Consent Checkbox**: Required when consent is enabled
- **CAPTCHA**: Shown when CAPTCHA is configured

### Field Validation

- **URL**: Must be valid HTTP/HTTPS URL
- **Email**: Valid email format (when required)
- **Consent**: Must be checked (when required)
- **CAPTCHA**: Must be completed (when enabled)

## Error Handling

The form provides user-friendly error messages:

- **Network errors**: "Network error occurred. Please try again."
- **Rate limiting**: "You have exceeded the daily request limit..."
- **Validation errors**: Specific messages for URL, email, consent issues
- **Server errors**: "There was an error. Please try again."

## Accessibility Features

The validator is fully WCAG AA compliant:

- Proper ARIA labels and descriptions
- Keyboard navigation support
- Screen reader announcements
- Focus management
- Error announcements

## Performance Considerations

- **Sampling**: Large feeds are sampled to first 500 products by default
- **Caching**: Rate limiting uses WordPress transients
- **Async Processing**: Validation happens server-side for better performance

## Integration Examples

### WooCommerce Integration

Add to product feed documentation page:

```php
<?php
/**
 * Template Name: Product Feed Validator
 */

get_header();
?>

<div class="validator-page">
    <h1>Validate Your Product Feed</h1>
    <p>Ensure your Google Shopping feed meets all requirements before submission.</p>

    <?php echo do_shortcode('[feed_validator]'); ?>

    <div class="feed-help">
        <h2>Need Help with Your Feed?</h2>
        <p>Our comprehensive guide covers:</p>
        <ul>
            <li>Google Merchant Center requirements</li>
            <li>Common feed errors and fixes</li>
            <li>Best practices for product data</li>
        </ul>
    </div>
</div>

<?php get_footer(); ?>
```

### Elementor Integration

1. Create a new page in Elementor
2. Add a "Shortcode" widget
3. Paste: `[feed_validator]`
4. Style as needed

### Custom Plugin Integration

```php
<?php
/**
 * Plugin Name: Custom Feed Validator Integration
 */

add_shortcode('custom_feed_validator', function($atts) {
    // Custom logic before validator
    do_action('before_feed_validator');

    // Output validator
    $output = do_shortcode('[feed_validator]');

    // Custom logic after validator
    do_action('after_feed_validator');

    return $output;
});
```

## Troubleshooting

### Form Not Appearing

1. Ensure plugin is activated
2. Check shortcode syntax: `[feed_validator]`
3. Verify user capabilities for admin endpoints

### Validation Errors

1. Check browser console for JavaScript errors
2. Verify REST API endpoints are accessible
3. Confirm admin settings are properly configured

### Styling Issues

1. Check for CSS conflicts with theme styles
2. Use browser developer tools to inspect elements
3. Add custom CSS with higher specificity

### Performance Issues

1. Enable sampling for large feeds
2. Check server PHP memory limits
3. Review timeout settings in admin

## Support

For additional help or custom integration requests, please contact support.
