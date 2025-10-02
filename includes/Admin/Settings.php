<?php
namespace WPMR\PFV\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {
    const OPTION_KEY = 'wpmr_pfv_options';

    public static function register_menu() {
        add_options_page(
            __( 'Product Feed Validator', 'wpmr-product-feed-validator' ),
            __( 'Feed Validator', 'wpmr-product-feed-validator' ),
            'manage_options',
            'wpmr-pfv-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function defaults() {
        return function_exists('wpmr_pfv_default_options') ? \wpmr_pfv_default_options() : [];
    }

    public static function register_settings() {
        register_setting( 'wpmr_pfv', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [ __CLASS__, 'sanitize' ],
            'default' => self::defaults(),
        ] );

        add_settings_section( 'wpmr_pfv_main', __( 'General', 'wpmr-product-feed-validator' ), '__return_null', 'wpmr_pfv' );
        self::add_field( 'delivery_mode', __( 'Delivery Mode', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_delivery_mode' ] );
        self::add_field( 'require_consent', __( 'Require Consent (anonymous users)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_checkbox' ] );
        self::add_field( 'attach_csv', __( 'Attach CSV to Email', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_checkbox' ] );
        self::add_field( 'rate_limit_ip_per_day', __( 'Rate Limit per IP/day', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'rate_limit_email_per_day', __( 'Rate Limit per Email/day', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'blocklist', __( 'Blocklist (one per line)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_textarea' ] );
        self::add_field( 'sampling_default', __( 'Enable Sampling by Default', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_checkbox' ] );
        self::add_field( 'sample_size', __( 'Sample Size', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'max_file_mb', __( 'Max File Size (MB)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'timeout_seconds', __( 'Timeout (seconds)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'redirect_cap', __( 'Redirect Cap', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'shareable_reports', __( 'Enable Shareable Reports', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_checkbox' ] );
        self::add_field( 'report_ttl_days', __( 'Report TTL (days, 0=indefinite)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_number' ] );
        self::add_field( 'captcha_provider', __( 'CAPTCHA Provider', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_captcha_provider' ] );
        self::add_field( 'captcha_site_key', __( 'CAPTCHA Site Key', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_text' ] );
        self::add_field( 'captcha_secret_key', __( 'CAPTCHA Secret Key', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_text' ] );
        self::add_field( 'webhook_url', __( 'Webhook URL (optional)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_text' ] );
        self::add_field( 'docs_url', __( 'Docs URL (CTA link)', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_text' ] );
        self::add_field( 'email_subject_template', __( 'Email Subject Template', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_text' ] );
        self::add_field( 'email_body_template', __( 'Email Body Template', 'wpmr-product-feed-validator' ), [ __CLASS__, 'field_textarea' ] );
    }

    protected static function add_field( $key, $label, $callback ) {
        add_settings_field( $key, $label, $callback, 'wpmr_pfv', 'wpmr_pfv_main', [ 'key' => $key ] );
    }

    public static function get_options() {
        $defaults = self::defaults();
        $opts = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( is_array( $opts ) ? $opts : [], $defaults );
    }

    public static function sanitize( $input ) {
        $defaults = self::defaults();
        $out = $defaults;
        $in = is_array( $input ) ? $input : [];

        $out['delivery_mode'] = in_array( $in['delivery_mode'] ?? 'email_plus_display', [ 'email_only', 'email_plus_display' ], true ) ? $in['delivery_mode'] : 'email_plus_display';
        $out['require_consent'] = ! empty( $in['require_consent'] ) ? 1 : 0;
        $out['attach_csv'] = ! empty( $in['attach_csv'] ) ? 1 : 0;
        $out['rate_limit_ip_per_day'] = max( 0, intval( $in['rate_limit_ip_per_day'] ?? $defaults['rate_limit_ip_per_day'] ) );
        $out['rate_limit_email_per_day'] = max( 0, intval( $in['rate_limit_email_per_day'] ?? $defaults['rate_limit_email_per_day'] ) );
        $blocklist_raw = isset( $in['blocklist'] ) ? (string) $in['blocklist'] : '';
        $out['blocklist'] = array_values( array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $blocklist_raw ) ) ) ) );
        $out['sampling_default'] = ! empty( $in['sampling_default'] ) ? 1 : 0;
        $out['sample_size'] = max( 1, intval( $in['sample_size'] ?? $defaults['sample_size'] ) );
        $out['max_file_mb'] = max( 1, intval( $in['max_file_mb'] ?? $defaults['max_file_mb'] ) );
        $out['timeout_seconds'] = max( 1, intval( $in['timeout_seconds'] ?? $defaults['timeout_seconds'] ) );
        $out['redirect_cap'] = max( 0, intval( $in['redirect_cap'] ?? $defaults['redirect_cap'] ) );
        $out['shareable_reports'] = ! empty( $in['shareable_reports'] ) ? 1 : 0;
        $out['report_ttl_days'] = max( 0, intval( $in['report_ttl_days'] ?? $defaults['report_ttl_days'] ) );
        $out['captcha_provider'] = in_array( $in['captcha_provider'] ?? 'none', [ 'none', 'recaptcha', 'turnstile' ], true ) ? $in['captcha_provider'] : 'none';
        $out['captcha_site_key'] = sanitize_text_field( $in['captcha_site_key'] ?? '' );
        $out['captcha_secret_key'] = sanitize_text_field( $in['captcha_secret_key'] ?? '' );
        $out['webhook_url'] = esc_url_raw( $in['webhook_url'] ?? '' );
        $out['docs_url'] = esc_url_raw( $in['docs_url'] ?? '' );
        $out['email_subject_template'] = sanitize_text_field( $in['email_subject_template'] ?? $defaults['email_subject_template'] );
        $out['email_body_template'] = wp_kses_post( $in['email_body_template'] ?? $defaults['email_body_template'] );

        return $out;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        $tabs = [
            'general' => __( 'General', 'wpmr-product-feed-validator' ),
            'rules'   => __( 'Rules', 'wpmr-product-feed-validator' ),
        ];
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Product Feed Validator', 'wpmr-product-feed-validator' ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tabs as $slug => $label ): ?>
                    <?php $active = $tab === $slug ? ' nav-tab-active' : ''; ?>
                    <a href="<?php echo esc_url( admin_url( 'options-general.php?page=wpmr-pfv-settings&tab=' . $slug ) ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>"><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </h2>

            <?php if ( $tab === 'general' ) : ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'wpmr_pfv' );
                    do_settings_sections( 'wpmr_pfv' );
                    submit_button();
                    ?>
                </form>
                <?php self::render_email_preview_box(); ?>
            <?php elseif ( $tab === 'rules' ) : ?>
                <div class="wpmr-pfv-rules">
                    <?php \WPMR\PFV\Admin\RuleManager::render_inner(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    protected static function render_email_preview_box() {
        $opts = self::get_options();
        $subject = (string) ( $opts['email_subject_template'] ?? '' );
        $body    = (string) ( $opts['email_body_template'] ?? '' );
        $tokens  = self::sample_tokens();
        $subject_preview = strtr( $subject, $tokens );
        $body_preview    = strtr( $body, $tokens );
        ?>
        <div class="wpmr-pfv-email-preview">
            <h2><?php echo esc_html__( 'Email Template Preview', 'wpmr-product-feed-validator' ); ?></h2>
            <p class="description"><?php echo esc_html__( 'Live preview using sample values. Update the fields above to customize subject and body.', 'wpmr-product-feed-validator' ); ?></p>
            <div class="wpmr-pfv-email-preview__box">
                <h3 class="wpmr-pfv-email-preview__subject"><?php echo esc_html( $subject_preview ); ?></h3>
                <div class="wpmr-pfv-email-preview__body"><?php echo wp_kses_post( $body_preview ); ?></div>
            </div>
        </div>
        <?php
    }

    protected static function sample_tokens() {
        return [
            '{url}' => 'https://example.com/feed.xml',
            '{score}' => '92',
            '{items_scanned}' => '500',
            '{errors}' => '3',
            '{warnings}' => '5',
            '{date}' => date_i18n( get_option( 'date_format' ) ),
            '{rule_version}' => 'google-v2025-09',
            '{override_count}' => '0',
        ];
    }

    // Field renderers
    public static function field_delivery_mode( $args ) {
        $opts = self::get_options();
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[delivery_mode]">
            <option value="email_only" <?php selected( $opts['delivery_mode'], 'email_only' ); ?>><?php esc_html_e( 'Email only', 'wpmr-product-feed-validator' ); ?></option>
            <option value="email_plus_display" <?php selected( $opts['delivery_mode'], 'email_plus_display' ); ?>><?php esc_html_e( 'Email + on-page display', 'wpmr-product-feed-validator' ); ?></option>
        </select>
        <?php
    }

    public static function field_checkbox( $args ) {
        $key = $args['key'];
        $opts = self::get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $opts[ $key ] ) ); ?> />
        </label>
        <?php
    }

    public static function field_number( $args ) {
        $key = $args['key'];
        $opts = self::get_options();
        ?>
        <input type="number" class="small-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( intval( $opts[ $key ] ?? 0 ) ); ?>" />
        <?php
    }

    public static function field_text( $args ) {
        $key = $args['key'];
        $opts = self::get_options();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( $opts[ $key ] ?? '' ) ); ?>" />
        <?php
    }

    public static function field_textarea( $args ) {
        $key = $args['key'];
        $opts = self::get_options();
        ?>
        <textarea class="large-text" rows="5" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( is_array( $opts[ $key ] ?? '' ) ? implode( "\n", (array) $opts[ $key ] ) : (string) ( $opts[ $key ] ?? '' ) ); ?></textarea>
        <?php
    }

    public static function field_captcha_provider() {
        $opts = self::get_options();
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[captcha_provider]">
            <option value="none" <?php selected( $opts['captcha_provider'], 'none' ); ?>><?php esc_html_e( 'None', 'wpmr-product-feed-validator' ); ?></option>
            <option value="recaptcha" <?php selected( $opts['captcha_provider'], 'recaptcha' ); ?>><?php esc_html_e( 'reCAPTCHA', 'wpmr-product-feed-validator' ); ?></option>
            <option value="turnstile" <?php selected( $opts['captcha_provider'], 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'wpmr-product-feed-validator' ); ?></option>
        </select>
        <?php
    }
}
