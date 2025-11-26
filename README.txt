=== WPMR Product Feed Validator ===
Contributors: Auke1810
Tags: woocommerce, product feed, validation, google shopping
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Validate WooCommerce product feeds against Google's specifications with detailed error reporting.

== Description ==

A robust solution for validating WooCommerce product feeds against Google's Shopping API specifications (v2025-09). Key features:

* Comprehensive validation against Google's requirements
* Detailed error reports with actionable suggestions
* REST API endpoints for automated validation workflows
* Custom rule management for store-specific requirements
* Support for multiple feed formats
* Integration with WooCommerce product data

Perfect for merchants who need to ensure their product feeds meet Google Shopping requirements.

== Installation ==

1. Upload the plugin files to the [/wp-content/plugins/wpmr-product-feed-validator/](cci:7://file:///Users/auke/Local%20Sites/wpmarketingrobotenfold/app/public/wp-content/plugins/wpmr-product-feed-validator:0:0-0:0) directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'WPMR Feed Validator' in your admin menu to configure settings

== Frequently Asked Questions ==

= What feed formats are supported? =
Currently supports Google Shopping Feed format (v2025-09). JSON and XML formats coming in v1.1.

= How often are the validation rules updated? =
Rules are updated quarterly to match Google's specification updates.

= Can I extend the validation rules? =
Yes, custom rules can be added via the Rules Manager interface or filters.

== Screenshots ==

1. Feed validation results dashboard - Shows pass/fail status with detailed errors
2. Rule management interface - Configure which rules to enforce
3. API documentation panel - Endpoint reference with examples

== Changelog ==

= 0.3.0 - 2025-11-26 =
* Added: GitHub auto-updates - WordPress now detects updates from GitHub releases
* Added: Update URI header to prevent WordPress.org update checks
* Added: GitHub_Updater class for automatic version checking
* Added: GitHub Actions workflow for automated release creation
* Added: One-click plugin updates directly from WordPress admin
* Improved: Release process now fully automated via GitHub Actions
* Improved: Users receive update notifications in standard WordPress UI

= 0.2.0 - 2025-11-26 =
* Added: Comprehensive frontend validation results display with detailed diagnostics
* Added: XML declaration validation (version and encoding checks)
* Added: File encoding validation with BOM detection
* Added: XML structure validation (root element and namespace checks)
* Added: Color-coded status banners (green/yellow/red) for validation results
* Added: Statistics dashboard showing total products, errors, warnings, and valid products
* Added: Grouped error and warning sections with "how to fix" guidance
* Added: Improvement tips section with 5 optimization suggestions
* Changed: Simplified export section to clear email CSV notice
* Improved: User experience with actionable error messages and guidance
* Improved: Performance with optimized rendering (82% code reduction in export section)
* Fixed: Early detection of malformed feeds before expensive parsing

= 0.1.0 - 2025-10-02 =
* Initial release with core validation functionality
* REST API endpoints for integration
* Admin interface for rule management
* Support for Google Shopping Feed v2025-09

== Upgrade Notice ==

= 0.3.0 =
Automatic updates from GitHub! WordPress will now automatically detect and install plugin updates. One-click updates directly from your admin panel. Recommended for all users.

= 0.2.0 =
Major feature update: Enhanced validation results display with detailed diagnostics, XML validation improvements, and better user guidance. Recommended for all users.

= 0.1.0 =
Initial release. No upgrade required.

== Usage ==

### Shortcode
`[feed_validator]` - Displays the validation form in any post/page

### REST API Endpoints
- `POST /wp-json/wpmr/v1/validate` - Validate single product
- `GET /wp-json/wpmr/v1/full-scan` - Full feed validation
- `GET|POST /wp-json/wpmr/v1/rules` - List or modify validation rules