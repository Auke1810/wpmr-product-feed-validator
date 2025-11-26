# Troubleshooting Guide - WPMR Product Feed Validator

This guide helps diagnose and resolve common issues with the Product Feed Validator plugin.

## Common Error Messages

### Form Validation Errors

#### "Feed URL is required"
- **Cause**: No URL entered in the form
- **Solution**: Enter a valid HTTP or HTTPS URL for your product feed

#### "Please enter a valid URL starting with http:// or https://"
- **Cause**: Invalid URL format
- **Solution**: Ensure URL starts with `http://` or `https://`

#### "Email address is required"
- **Cause**: Email field is empty (when user not logged in)
- **Solution**: Enter a valid email address

#### "Please enter a valid email address"
- **Cause**: Invalid email format
- **Solution**: Check email format (user@domain.com)

#### "You must consent to receive the validation report via email"
- **Cause**: Consent checkbox not checked
- **Solution**: Check the consent box to agree to email delivery

### Rate Limiting Errors

#### "Rate limit exceeded for this IP address. Please try again later."
- **Cause**: Too many requests from the same IP
- **Default limit**: 50 requests per IP per day
- **Solution**:
  - Wait until the daily limit resets (midnight)
  - Contact administrator to increase limits
  - Use a different IP address

#### "Rate limit exceeded for this email address. Please try again later."
- **Cause**: Too many requests from the same email
- **Default limit**: 20 requests per email per day
- **Solution**:
  - Wait until the daily limit resets
  - Use a different email address
  - Contact administrator for higher limits

#### "Requests from this email, domain, or IP are blocked."
- **Cause**: IP, email, or domain is in the blocklist
- **Solution**: Contact administrator to remove from blocklist

### CAPTCHA Errors

#### "CAPTCHA verification failed"
- **Cause**: CAPTCHA not completed or invalid
- **Solution**:
  - Complete the CAPTCHA challenge
  - Refresh the page and try again
  - Check CAPTCHA service status (reCAPTCHA/Turnstile)

### Network and Server Errors

#### "Network error occurred. Please try again."
- **Cause**: Connection issues or server problems
- **Solutions**:
  - Check internet connection
  - Try again in a few minutes
  - Contact administrator if persistent

#### "There was an error. Please try again."
- **Cause**: Generic server error
- **Solutions**:
  - Check browser console for JavaScript errors
  - Clear browser cache and cookies
  - Try a different browser
  - Contact administrator

## Feed-Specific Errors

### Transport Layer Issues

#### "XML is not well-formed or could not be read."
- **Cause**: Invalid XML structure
- **Solutions**:
  - Validate XML with an XML validator
  - Check for unescaped special characters (`&`, `<`, `>`)
  - Ensure proper XML declaration: `<?xml version="1.0" encoding="UTF-8"?>`
  - Fix any malformed tags or attributes

#### "Neither RSS <item> nor Atom <entry> elements were found."
- **Cause**: Feed doesn't contain required item elements
- **Solutions**:
  - Ensure feed uses RSS `<item>` or Atom `<entry>` elements
  - Check feed format matches Google Merchant Center specifications
  - Verify feed is not empty

#### "HTTP 404 - Not Found"
- **Cause**: Feed URL returns 404 error
- **Solutions**:
  - Verify URL is correct and accessible
  - Check for typos in the URL
  - Ensure feed file exists on server
  - Test URL directly in browser

#### "HTTP 403 - Forbidden"
- **Cause**: Server blocks access to feed
- **Solutions**:
  - Check server permissions for feed file
  - Verify no IP restrictions
  - Add user-agent allowance if needed
  - Check for authentication requirements

#### "HTTP 500 - Internal Server Error"
- **Cause**: Server error when accessing feed
- **Solutions**:
  - Check server error logs
  - Verify feed generation script works
  - Contact hosting provider
  - Test feed URL directly

#### "Timeout Error"
- **Cause**: Feed takes too long to load
- **Default timeout**: 20 seconds
- **Solutions**:
  - Increase timeout in admin settings
  - Optimize feed generation
  - Check server response time
  - Consider feed size limits

#### "File too large"
- **Cause**: Feed exceeds size limits
- **Default limit**: 100MB
- **Solutions**:
  - Reduce feed size by limiting products
  - Increase limit in admin settings (if server allows)
  - Use feed pagination
  - Enable sampling (default: first 500 products)

### Parsing Errors

#### "Item XML not well-formed"
- **Cause**: Individual product XML is malformed
- **Solutions**:
  - Validate specific product entries
  - Check for special characters in product data
  - Ensure proper XML escaping
  - Fix CDATA sections if used

#### "Required Google namespace (g:) not found"
- **Cause**: Missing Google product namespace
- **Solutions**:
  - Add namespace declaration: `xmlns:g="http://base.google.com/ns/1.0"`
  - Ensure all Google attributes use `g:` prefix
  - Follow Google Merchant Center XML format

### Validation Rule Errors

#### "Product ID (g:id) is required"
- **Cause**: Missing product identifier
- **Solutions**:
  - Add unique `g:id` for each product
  - Use SKU, product ID, or other unique identifier
  - Ensure no duplicate IDs across feed

#### "Title (g:title) is required"
- **Cause**: Missing product title
- **Solutions**:
  - Add descriptive `g:title` for each product
  - Ensure titles are not empty
  - Check for encoding issues

#### "Price format invalid"
- **Cause**: Price doesn't match required format
- **Required format**: `123.45 EUR` (number with 2 decimals + currency)
- **Solutions**:
  - Use format: `[number].[cents] [CURRENCY]`
  - Valid currencies: EUR, USD, GBP, etc.
  - Ensure prices are numeric

#### "URL must be absolute"
- **Cause**: Relative URLs in product links
- **Solutions**:
  - Use full URLs: `https://example.com/product/123`
  - Avoid relative URLs: `/product/123` or `product/123`
  - Check both `g:link` and `g:image_link`

#### "Duplicate product IDs found"
- **Cause**: Same `g:id` used for multiple products
- **Solutions**:
  - Ensure each product has unique ID
  - Check for data export issues
  - Use compound IDs if needed: `SKU-VARIANT`

## Admin Configuration Issues

### Email Not Sending

#### "SMTP Error"
- **Cause**: Email configuration issues
- **Solutions**:
  - Configure SMTP in WordPress (WP Mail SMTP plugin recommended)
  - Check server email settings
  - Verify sender email address
  - Check spam/junk folders

#### Emails Going to Spam
- **Cause**: Email deliverability issues
- **Solutions**:
  - Use reputable SMTP service
  - Set proper SPF/DKIM records
  - Avoid spam trigger words in subject/body
  - Use consistent sending domain

### Settings Not Saving

- **Cause**: Nonce verification or permissions
- **Solutions**:
  - Ensure user has `manage_options` capability
  - Check for JavaScript errors
  - Clear browser cache
  - Verify database permissions

### Rule Manager Not Working

- **Cause**: AJAX or permissions issues
- **Solutions**:
  - Check browser console for errors
  - Verify REST API is accessible
  - Confirm user permissions
  - Check for plugin conflicts

## Performance Issues

### Slow Validation

#### Large Feed Performance
- **Cause**: Processing many products
- **Solutions**:
  - Enable sampling (default: 500 products)
  - Reduce sample size in settings
  - Optimize feed generation
  - Consider feed splitting

#### Memory Issues
- **Cause**: PHP memory limits exceeded
- **Solutions**:
  - Increase PHP memory limit
  - Enable sampling for large feeds
  - Reduce batch processing size
  - Check server resources

### High Server Load

- **Cause**: Too many concurrent validations
- **Solutions**:
  - Adjust rate limiting settings
  - Implement queuing for large feeds
  - Add server-side caching
  - Monitor server resources

## JavaScript and Frontend Issues

### Form Not Submitting

- **Cause**: JavaScript errors or conflicts
- **Solutions**:
  - Check browser console for errors
  - Disable other plugins temporarily
  - Clear browser cache
  - Test with different browser

### Results Not Displaying

- **Cause**: AJAX response issues
- **Solutions**:
  - Check network tab for failed requests
  - Verify REST API endpoints
  - Check for CORS issues
  - Test with simple feed first

## Advanced Troubleshooting

### Debug Logging

Enable WordPress debug logging to capture detailed errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log`

### REST API Testing

Test API endpoints directly:

```bash
# Test validation endpoint
curl -X POST https://yoursite.com/wp-json/wpmr/v1/validate \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/feed.xml","email":"test@example.com"}'

# Test tests endpoint (admin only)
curl https://yoursite.com/wp-json/wpmr/v1/tests \
  -H "X-WP-Nonce: your_nonce_here"
```

### Database Issues

Check database tables exist and are accessible:

```sql
SHOW TABLES LIKE 'wp_feed_validator%';
DESCRIBE wp_feed_validator_reports;
```

### Plugin Conflicts

Test for conflicts by:
1. Deactivate other plugins
2. Switch to default theme
3. Test validator functionality
4. Re-enable plugins/themes one by one

### Server Configuration

Verify server requirements:
- PHP 7.4+
- XMLReader extension
- WordPress 5.0+
- MySQL 5.6+
- Allow_url_fopen enabled
- Sufficient memory (128MB+)

## Getting Help

If issues persist:

1. **Check this guide** for your specific error
2. **Enable debug logging** and review logs
3. **Test with simple feed** to isolate issues
4. **Contact support** with:
   - Error messages
   - Debug logs
   - Feed URL (if safe to share)
   - WordPress/server configuration
   - Steps to reproduce

## Prevention

### Best Practices

- **Regular Testing**: Test feeds before major changes
- **Monitor Performance**: Watch for slow validations
- **Update Regularly**: Keep plugin and WordPress updated
- **Backup Settings**: Export rule configurations
- **Monitor Logs**: Check for recurring errors

### Maintenance

- **Clean Old Reports**: Set up automatic cleanup for old validation reports
- **Update Rules**: Keep rulepacks current with Google specifications
- **Monitor Usage**: Track validation patterns and adjust limits
- **Performance Tuning**: Optimize based on usage patterns

This guide covers the most common issues and their solutions. For complex or persistent problems, please contact technical support with detailed information about your setup and the issue encountered.
