<?php
namespace WPMR\PFV\PublicUI;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shortcode {
    public static function register() {
        add_shortcode( 'feed_validator', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts = [] ) {
        $atts = shortcode_atts( [ 'sample' => 'true' ], $atts, 'feed_validator' );
        $sample = filter_var( $atts['sample'], FILTER_VALIDATE_BOOLEAN );

        ob_start();
        $action = esc_url( rest_url( 'wpmr/v1/validate' ) );
        $is_logged_in = is_user_logged_in();
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        $captcha_provider = (string) ( $opts['captcha_provider'] ?? 'none' );
        $captcha_site_key = (string) ( $opts['captcha_site_key'] ?? '' );
        ?>
        <form class="wpmr-pfv-form" data-endpoint="<?php echo $action; ?>" aria-live="polite" role="form" aria-labelledby="wpmr-pfv-form-title" novalidate>
            <h2 id="wpmr-pfv-form-title" class="screen-reader-text"><?php esc_html_e( 'Product Feed Validation Form', 'wpmr-product-feed-validator' ); ?></h2>
            
            <p class="wpmr-pfv-field-group">
                <label for="wpmr-pfv-url" class="wpmr-pfv-label"><?php esc_html_e( 'Feed URL', 'wpmr-product-feed-validator' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'wpmr-product-feed-validator' ); ?>">*</span></label><br />
                <input type="url" id="wpmr-pfv-url" name="url" required 
                       placeholder="https://example.com/feed.xml"
                       aria-required="true" aria-describedby="wpmr-pfv-url-description" aria-label="<?php esc_attr_e( 'Feed URL', 'wpmr-product-feed-validator' ); ?>" />
                <span id="wpmr-pfv-url-description" class="screen-reader-text"><?php esc_html_e( 'Enter the URL of your product feed XML file for validation', 'wpmr-product-feed-validator' ); ?></span>
            </p>
            <div id="wpmr-pfv-url-error" class="wpmr-pfv-error" aria-live="polite" role="alert" style="display: none;"></div>
            
            <?php if ( ! $is_logged_in ) : ?>
            <p class="wpmr-pfv-field-group">
                <label for="wpmr-pfv-email" class="wpmr-pfv-label"><?php esc_html_e( 'Email Address', 'wpmr-product-feed-validator' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'wpmr-product-feed-validator' ); ?>">*</span></label><br />
                <input type="email" id="wpmr-pfv-email" name="email" required 
                       placeholder="you@example.com"
                       aria-required="true" aria-describedby="wpmr-pfv-email-description" aria-label="<?php esc_attr_e( 'Email Address', 'wpmr-product-feed-validator' ); ?>" />
                <span id="wpmr-pfv-email-description" class="screen-reader-text"><?php esc_html_e( 'Enter your email address to receive the validation report', 'wpmr-product-feed-validator' ); ?></span>
            </p>
            <div id="wpmr-pfv-email-error" class="wpmr-pfv-error" aria-live="polite" role="alert" style="display: none;"></div>
            
            <p class="wpmr-pfv-field-group">
                <label class="wpmr-pfv-consent-label">
                    <input type="checkbox" name="consent" value="1" required 
                           aria-required="true" aria-describedby="wpmr-pfv-consent-description" id="wpmr-pfv-consent" />
                    <span id="wpmr-pfv-consent-description"><?php esc_html_e( 'I consent to receive the validation report via email.', 'wpmr-product-feed-validator' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'wpmr-product-feed-validator' ); ?>">*</span></span>
                </label>
            </p>
            <div id="wpmr-pfv-consent-error" class="wpmr-pfv-error" aria-live="polite" role="alert" style="display: none;"></div>
            
            <?php if ( $captcha_provider !== 'none' && $captcha_site_key !== '' ) : ?>
            <div class="wpmr-pfv-captcha-group" id="wpmr-pfv-captcha-group" aria-live="polite">
                <label class="wpmr-pfv-label"><?php esc_html_e( 'Security Verification', 'wpmr-product-feed-validator' ); ?></label>
                <p>
                    <?php if ( $captcha_provider === 'recaptcha' ) : ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $captcha_site_key ); ?>" aria-label="reCAPTCHA"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <?php elseif ( $captcha_provider === 'turnstile' ) : ?>
                        <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $captcha_site_key ); ?>" aria-label="Cloudflare Turnstile"></div>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <?php endif; ?>
                </p>
            </div>
            <div id="wpmr-pfv-captcha-error" class="wpmr-pfv-error" aria-live="polite" role="alert" style="display: none;"></div>
            <?php endif; ?>
            <?php endif; ?>
            
            <input type="hidden" name="sample" value="<?php echo $sample ? '1' : '0'; ?>" />
            
            <p class="wpmr-pfv-submit-group">
                <button type="submit" aria-busy="false" data-aria-busy-text="<?php esc_attr_e( 'Validating...', 'wpmr-product-feed-validator' ); ?>" class="wpmr-pfv-submit-button">
                    <?php esc_html_e( 'Validate Feed', 'wpmr-product-feed-validator' ); ?>
                </button>
            </p>
            <div id="wpmr-pfv-submit-error" class="wpmr-pfv-error" aria-live="polite" role="alert" style="display: none;"></div>
            
            <div class="wpmr-pfv-result" aria-live="assertive" role="status" aria-atomic="true"></div>
        </form>
        <?php
        self::enqueue_assets();
        wp_enqueue_script( 'wpmr-pfv-accessibility', WPMR_PFV_PLUGIN_URL . 'assets/js/accessibility.js', [], WPMR_PFV_VERSION, true );
        return ob_get_clean();
    }

    protected static function enqueue_assets() {
        wp_enqueue_style( 'wpmr-pfv-public', WPMR_PFV_PLUGIN_URL . 'assets/css/public.css', [], WPMR_PFV_VERSION );
        wp_enqueue_script( 'wpmr-pfv-public', WPMR_PFV_PLUGIN_URL . 'assets/js/public.js', [ 'wp-api-fetch' ], WPMR_PFV_VERSION, true );
        wp_localize_script( 'wpmr-pfv-public', 'WPMR_PFV_I18N', [
            'validating'   => __( 'Validating...', 'wpmr-product-feed-validator' ),
            'success'      => __( 'Request accepted. Check your email for the report.', 'wpmr-product-feed-validator' ),
            'error'        => __( 'There was an error. Please try again.', 'wpmr-product-feed-validator' ),
            'rest_nonce'   => wp_create_nonce( 'wp_rest' ),
            'is_logged_in' => is_user_logged_in(),
            'delivery'     => $opts['delivery_mode'] ?? 'email_plus_display',
            'attach_csv'   => ! empty( $opts['attach_csv'] ),
            'docs_url'     => (string) ( $opts['docs_url'] ?? '' ),
            'captcha_provider' => (string) ( $opts['captcha_provider'] ?? 'none' ),
        ] );
    }
}
