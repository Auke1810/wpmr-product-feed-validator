<?php
/**
 * Plugin Name:       WPMR Product Feed Validator
 * Description:       Validate Google Shopping product feeds and email/share reports.
 * Version:           0.3.1
 * Author:            WP Marketing Robot
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmr-product-feed-validator
 * Domain Path:       /languages
 * Update URI:        https://github.com/Auke1810/wpmr-product-feed-validator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Constants - must be defined before any other code
if (!defined('WPMR_PFV_VERSION')) {
    define('WPMR_PFV_VERSION', '0.3.1');
}
if (!defined('WPMR_PFV_PLUGIN_FILE')) {
    define('WPMR_PFV_PLUGIN_FILE', __FILE__);
}
if (!defined('WPMR_PFV_PLUGIN_DIR')) {
    define('WPMR_PFV_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WPMR_PFV_PLUGIN_URL')) {
    define('WPMR_PFV_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Load textdomain
add_action('plugins_loaded', function() {
    load_plugin_textdomain('wpmr-product-feed-validator', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Default options
function wpmr_pfv_default_options() {
    return [
        'delivery_mode'             => 'email_plus_display',
        'require_consent'           => 1,
        'attach_csv'                => 1,
        'rate_limit_ip_per_day'     => 50,
        'rate_limit_email_per_day'  => 20,
        'blocklist'                 => [],
        'sampling_default'          => 1,
        'sample_size'               => 500,
        'max_file_mb'               => 100,
        'timeout_seconds'           => 20,
        'redirect_cap'              => 3,
        'shareable_reports'         => 1,
        'report_ttl_days'           => 0,
        'captcha_provider'          => 'none',
        'captcha_site_key'          => '',
        'captcha_secret_key'        => '',
        'webhook_url'               => '',
        'docs_url'                  => 'https://www.wpmarketingrobot.com/help-center/',
        'email_subject_template'    => __('Your Product Feed Report — {score}/100', 'wpmr-product-feed-validator'),
        'email_body_template'       => __('Here is your product feed report for {url}. Items scanned: {items_scanned}. Errors: {errors}. Warnings: {warnings}. Date: {date}.', 'wpmr-product-feed-validator'),
    ];
}

// Activation hook
register_activation_hook(__FILE__, function() {
    if (false === get_option('wpmr_pfv_options', false)) {
        add_option('wpmr_pfv_options', wpmr_pfv_default_options());
    }
    
    // Create/upgrade DB schema
    require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Reports.php';
    WPMR\PFV\Services\Reports::install_schema();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up any scheduled events if needed
});

// Includes
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Admin/Settings.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Validate_Controller.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Tests_Controller.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Public/Shortcode.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Abuse.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Captcha.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Email.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Fetcher.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Parser.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Reports.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Rules.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/RulesEngine.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Scoring.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Webhook.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/GitHub_Updater.php';

// Initialize GitHub Auto-Updater
new WPMR\PFV\GitHub_Updater(
    WPMR_PFV_PLUGIN_FILE,
    'Auke1810/wpmr-product-feed-validator'
);

// Init Admin
add_action('admin_menu', ['WPMR\PFV\Admin\Settings', 'register_menu']);
add_action('admin_init', ['WPMR\PFV\Admin\Settings', 'register_settings']);

// Init REST API
add_action('rest_api_init', function() {
    $controller = new WPMR\PFV\REST\Validate_Controller();
    $controller->register_routes();
    
    $tests_controller = new WPMR\PFV\REST\Tests_Controller();
    $tests_controller->register_routes();
});

// Init Shortcode
add_action('init', function() {
    WPMR\PFV\PublicUI\Shortcode::register();
});