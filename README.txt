=== WPMR Product Feed Validator ===
Contributors: Auke1810
Tags: woocommerce, product feed, validation, google shopping
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 1.0.0 =
* Initial release with core validation functionality
* REST API endpoints for integration
* Admin interface for rule management
* Support for Google Shopping Feed v2025-09

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.

== Usage ==

### Shortcode
`[wpmr_feed_validator]` - Displays the validation form in any post/page

### REST API Endpoints
- `POST /wp-json/wpmr/v1/validate` - Validate single product
- `GET /wp-json/wpmr/v1/full-scan` - Full feed validation
- `GET|POST /wp-json/wpmr/v1/rules` - List or modify validation rules

== Additional Notes ==

For developers: See included `CONTRIBUTING.md` for coding standards and pull request guidelines.