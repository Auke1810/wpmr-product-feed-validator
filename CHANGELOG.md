# Changelog - WPMR Product Feed Validator

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-10-02

### Added
#### Core Features
- **Google Merchant Center Validation**: Complete compliance checking against GMC v2025-09 requirements
- **Intelligent Feed Processing**: XMLReader-based streaming parser for memory-efficient large feed handling
- **Sampling Support**: Configurable sampling (default: 500 products) for performance optimization
- **Comprehensive Rule Engine**: 50+ validation rules covering transport, structure, required attributes, and data quality
- **Scoring System**: 0-100 scoring with penalties for errors (E: -7), warnings (W: -3), and advice (A: -1)
- **Report Generation**: Detailed validation reports with issue categorization and documentation links

#### Security & Abuse Prevention
- **Rate Limiting**: IP-based (50/day) and email-based (20/day) request throttling
- **CAPTCHA Integration**: Support for reCAPTCHA v2 and Cloudflare Turnstile
- **Blocklist System**: Configurable IP, email, and domain blocking
- **SSRF Protection**: Safe URL fetching with private IP range blocking
- **Input Validation**: Comprehensive sanitization and validation of all user inputs

#### User Interface
- **Public Validation Form**: Shortcode `[feed_validator]` and Gutenberg block integration
- **Admin Dashboard**: Complete settings panel with rule management interface
- **Results Display**: Real-time validation results with score badges and issue tables
- **Email Delivery**: HTML reports with optional CSV attachments
- **Public Reports**: Shareable validation reports with unique URLs

#### Delivery Modes
- **Email Only**: Send reports via email without on-page display
- **Email Plus Display**: Immediate results + email delivery
- **Logged-in User Support**: Skip email requirement for authenticated users

#### Admin Features
- **Rule Manager**: Enable/disable rules, adjust severities, and set custom weights
- **Settings Panel**: Comprehensive configuration for all aspects
- **Report History**: Admin access to validation history and public report URLs
- **PII Retention**: Automatic cleanup of email addresses after 180 days

#### Developer Features
- **REST API**: Complete API for validation, reports, and testing
- **Hook System**: Extensive filters and actions for customization
- **Custom Rules**: Framework for adding business-specific validation rules
- **Extensible Architecture**: Modular design for easy extension
- **Testing Suite**: Automated tests for performance and correctness

#### Accessibility & Internationalization
- **WCAG AA Compliance**: Full accessibility support with screen reader compatibility
- **Dutch Translation**: Complete `nl_NL` localization
- **Keyboard Navigation**: Full keyboard accessibility
- **ARIA Support**: Proper ARIA labels, live regions, and announcements

#### Performance & Quality
- **Load Testing**: Automated performance validation (<30s for typical feeds)
- **Memory Optimization**: Streaming XML processing for large feeds
- **Automated Testing**: REST API endpoint for running validation tests
- **Error Handling**: Comprehensive error reporting and user guidance

### Technical Implementation
#### Database
- **Reports Table**: Comprehensive report storage with indexing
- **Rule Overrides Table**: Persistent rule customization storage
- **Migration System**: Automatic database schema updates

#### File Structure
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
├── rules/               # Validation rule definitions
└── docs/                # Documentation files
```

#### Dependencies
- **WordPress**: 5.0+ (tested up to latest)
- **PHP**: 7.4+ with XMLReader extension
- **MySQL**: 5.6+ for report storage

### Configuration Options
- **Delivery Mode**: email_only | email_plus_display
- **Rate Limits**: Configurable IP and email limits
- **File Processing**: Size limits, timeouts, redirects
- **Email Templates**: Customizable subject and body
- **Security**: CAPTCHA providers, blocklists
- **Sampling**: Enable/disable and sample sizes

### API Endpoints
```
POST /wp-json/wpmr/v1/validate      # Validate feed
GET  /wp-json/wpmr/v1/tests         # Run tests (admin)
GET  /wp-json/wpmr/v1/reports/{key} # Public reports
```

### Hooks & Filters
- `gpfv_rules`: Modify validation rules
- `gpfv_rule_overrides`: Override rule settings
- `gpfv_validation_result`: Filter results
- `gpfv_email_templates`: Customize emails
- `gpfv_rate_limits`: Adjust rate limiting
- `gpfv_before_validation`: Pre-validation hook
- `gpfv_after_validation`: Post-validation hook

### Validation Rules Implemented
#### Transport Layer
- HTTP status validation
- Content-type checking
- File size limits
- Redirect handling

#### Structure Validation
- XML well-formedness
- Item element detection (RSS/Atom)
- Product ID uniqueness
- Namespace compliance

#### Required Attributes
- Product ID (g:id)
- Title (g:title)
- Description (g:description)
- Link (g:link)
- Image link (g:image_link)
- Availability (g:availability)
- Price (g:price)

#### Data Quality Rules
- URL format validation
- Price format and logic validation
- Category taxonomy compliance
- Identifier validation (GTIN, MPN, brand)
- Shipping information accuracy
- Tax rate validation
- Sale price logic (must be ≤ regular price)

### Security Features
- **Input Sanitization**: All inputs validated and sanitized
- **Nonce Protection**: CSRF prevention on all forms
- **Capability Checks**: Proper WordPress permission verification
- **SSRF Prevention**: Private IP range blocking
- **Rate Limiting**: Configurable request throttling
- **CAPTCHA**: Spam prevention for public forms

### Performance Metrics
- **Typical Feed**: <500 items, <30 seconds processing
- **Large Feed**: 10,000+ items supported via sampling
- **Memory Usage**: Optimized for shared hosting
- **Concurrent Users**: Rate limiting prevents abuse

### Documentation
- **README.md**: Complete admin and developer guide
- **PUBLIC_EMBED_GUIDE.md**: Integration and customization guide
- **DEVELOPER_DOCS.md**: API reference and extension guide
- **TROUBLESHOOTING.md**: Error resolution and debugging guide

### Testing
- **Unit Tests**: Automated rule and performance validation
- **Integration Tests**: End-to-end validation scenarios
- **Performance Tests**: Load testing with various feed sizes
- **Accessibility Tests**: WCAG compliance validation

### Browser Support
- **Modern Browsers**: Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Mobile**: Responsive design for all devices
- **JavaScript**: Progressive enhancement with graceful degradation

### Known Limitations
- Single feed validation per request
- Email delivery dependent on server SMTP configuration
- Large feeds require sampling for performance
- Rule customization requires developer knowledge

### Future Enhancements
- Batch feed validation
- Scheduled validation monitoring
- Advanced analytics dashboard
- Third-party integrations
- Custom rule builder UI

### Credits
- **Google Merchant Center**: Validation rules based on GMC specifications
- **WordPress Community**: Best practices and coding standards
- **Open Source Libraries**: PHP and JavaScript utilities used

### License
GPL-2.0-or-later - See LICENSE file for details

---

## Development Notes

This release represents a complete implementation of the Google Product Feed Validator with enterprise-grade features including security, performance, accessibility, and extensibility. The plugin is production-ready and suitable for high-traffic WordPress installations.

### Installation Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- XMLReader PHP extension
- 128MB+ PHP memory limit recommended

### Post-Installation
1. Configure settings in **WPMR → Product Feed Validator**
2. Set up SMTP for email delivery (recommended)
3. Configure CAPTCHA if using public forms
4. Test with sample feeds before production use

For support and feature requests, please contact the development team.
