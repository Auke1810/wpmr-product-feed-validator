<?php
/**
 * Plugin Name:       WPMR Product Feed Validator
 * Description:       Validate Google Shopping product feeds and email/share reports.
 * Version:           0.1.0
 * Author:            WP Marketing Robot
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmr-product-feed-validator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Constants
if ( ! defined( 'WPMR_PFV_VERSION' ) ) {
    define( 'WPMR_PFV_VERSION', '0.1.0' );
}
if ( ! defined( 'WPMR_PFV_PLUGIN_FILE' ) ) {
    define( 'WPMR_PFV_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WPMR_PFV_PLUGIN_DIR' ) ) {
    define( 'WPMR_PFV_PLUGIN_DIR' , plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPMR_PFV_PLUGIN_URL' ) ) {
    define( 'WPMR_PFV_PLUGIN_URL' , plugin_dir_url( __FILE__ ) );
}

// Load textdomain
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'wpmr-product-feed-validator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Cron task: purge PII older than 180 days
add_action( 'wpmr_pfv_purge_pii_daily', function() {
    \WPMR\PFV\Services\Reports::purge_pii( 180 );
} );

// Default options
function wpmr_pfv_default_options() {
    return [
        'delivery_mode'             => 'email_plus_display', // email_only | email_plus_display
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
        'captcha_provider'          => 'none', // none|recaptcha|turnstile
        'captcha_site_key'          => '',
        'captcha_secret_key'        => '',
        'webhook_url'               => '',
        'docs_url'                  => 'https://www.wpmarketingrobot.com/help-center/',
        'email_subject_template'    => __( 'Your Product Feed Report — {score}/100', 'wpmr-product-feed-validator' ),
        'email_body_template'       => __( 'Here is your product feed report for {url}. Items scanned: {items_scanned}. Errors: {errors}. Warnings: {warnings}. Date: {date}.', 'wpmr-product-feed-validator' ),
    ];
}

// Activation: ensure options exist
register_activation_hook( __FILE__, function() {
    if ( false === get_option( 'wpmr_pfv_options', false ) ) {
        add_option( 'wpmr_pfv_options', wpmr_pfv_default_options() );
    }
    // Create/upgrade DB schema for reports and overrides
    require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Reports.php';
    \WPMR\PFV\Services\Reports::install_schema();
    // Schedule daily PII purge if not already scheduled
    if ( ! wp_next_scheduled( 'wpmr_pfv_purge_pii_daily' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpmr_pfv_purge_pii_daily' );
    }
} );

// Deactivation: clear cron
register_deactivation_hook( __FILE__, function() {
    $timestamp = wp_next_scheduled( 'wpmr_pfv_purge_pii_daily' );
    if ( $timestamp ) { wp_unschedule_event( $timestamp, 'wpmr_pfv_purge_pii_daily' ); }
} );

// Includes
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Admin/Settings.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Validate_Controller.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Rules_Controller.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Tests_Controller.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Public/Shortcode.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Fetcher.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Parser.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Rules.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/RulesEngine.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Scoring.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Reports.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Email.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Webhook.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Abuse.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/Captcha.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Services/FullScan.php';
require_once WPMR_PFV_PLUGIN_DIR . 'includes/Admin/RuleManager.php';

// Init Admin
add_action( 'admin_menu', [ '\\WPMR\\PFV\\Admin\\Settings', 'register_menu' ] );
add_action( 'admin_init', [ '\\WPMR\\PFV\\Admin\\Settings', 'register_settings' ] );

// Init REST
add_action( 'rest_api_init', function() {
    $controller = new \WPMR\PFV\REST\Validate_Controller();
    $controller->register_routes();
    $rules_controller = new \WPMR\PFV\REST\Rules_Controller();
    $rules_controller->register_routes();
    $tests_controller = new \WPMR\PFV\REST\Tests_Controller();
    $tests_controller->register_routes();
    if ( file_exists( WPMR_PFV_PLUGIN_DIR . 'includes/REST/Reports_Controller.php' ) ) {
        require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/Reports_Controller.php';
    }
    if ( class_exists( '\\WPMR\\PFV\\REST\\Reports_Controller' ) ) {
        $reports_controller = new \WPMR\PFV\REST\Reports_Controller();
        $reports_controller->register_routes();
    }
    if ( file_exists( WPMR_PFV_PLUGIN_DIR . 'includes/REST/FullScan_Controller.php' ) ) {
        require_once WPMR_PFV_PLUGIN_DIR . 'includes/REST/FullScan_Controller.php';
    }
    if ( class_exists( '\\WPMR\\PFV\\REST\\FullScan_Controller' ) ) {
        $fullscan_controller = new \WPMR\PFV\REST\FullScan_Controller();
        $fullscan_controller->register_routes();
    }
} );

// Init Public UI (shortcode)
add_action( 'init', function() {
    \WPMR\PFV\PublicUI\Shortcode::register();
} );

// Init Gutenberg Block (server-rendered)
add_action( 'init', function() {
    if ( function_exists( 'register_block_type' ) ) {
        register_block_type( WPMR_PFV_PLUGIN_DIR . 'blocks/feed-validator' );
    }
} );

// Register FullScan cron handler
add_action( 'init', function() {
    \WPMR\PFV\Services\FullScan::register_hook();
} );

// Admin assets
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( strpos( $hook, 'wpmr-pfv' ) !== false ) {
        wp_enqueue_script( 'wpmr-pfv-admin', WPMR_PFV_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WPMR_PFV_VERSION, true );
        wp_enqueue_style( 'wpmr-pfv-admin', WPMR_PFV_PLUGIN_URL . 'assets/css/admin.css', [], WPMR_PFV_VERSION );
        // Localize REST settings for admin JS
        wp_localize_script( 'wpmr-pfv-admin', 'WPMR_PFV_ADMIN', [
            'restBase' => esc_url_raw( rest_url( 'wpmr/v1' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ] );
        // Additional script for Rule Manager interactions
        if ( $hook === 'settings_page_wpmr-pfv-rules' ) {
            wp_enqueue_script( 'wpmr-pfv-admin-rules', WPMR_PFV_PLUGIN_URL . 'assets/js/admin-rules.js', [ 'wpmr-pfv-admin' ], WPMR_PFV_VERSION, true );
        }
    }
} );
