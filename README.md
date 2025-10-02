# WPMR Product Feed Validator

A WordPress plugin that validates product feeds against Google's specifications (v2025-09).

## Features

- Validates product feeds against Google's latest specifications
- Provides detailed error reports
- Includes admin interface for managing validation rules
- Offers REST API endpoints for automated validation
- Supports custom validation rules

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wpmr-product-feed-validator` directory
2. Activate the plugin through the WordPress admin panel
3. Configure validation rules in the plugin settings

## Usage

### Shortcode
Use `[wpmr_feed_validator]` to display the validator form on any page or post.

### REST API
The plugin provides these endpoints:
- `/wp-json/wpmr/v1/validate` - Validate a single product
- `/wp-json/wpmr/v1/full-scan` - Validate entire feed
- `/wp-json/wpmr/v1/rules` - Manage validation rules

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on contributing to this project.

## License

This plugin is licensed under GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
