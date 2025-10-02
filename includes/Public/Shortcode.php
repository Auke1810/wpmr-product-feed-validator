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
        <form class="wpmr-pfv-form" data-endpoint="<?php echo $action; ?>">
            <p>
                <label for="wpmr-pfv-url"><?php esc_html_e( 'Feed URL', 'wpmr-product-feed-validator' ); ?></label><br />
                <input type="url" id="wpmr-pfv-url" name="url" required placeholder="https://example.com/feed.xml" />
            </p>
            <?php if ( ! $is_logged_in ) : ?>
            <p>
                <label for="wpmr-pfv-email"><?php esc_html_e( 'Email', 'wpmr-product-feed-validator' ); ?></label><br />
                <input type="email" id="wpmr-pfv-email" name="email" required placeholder="you@example.com" />
            </p>
            <p>
                <label>
                    <input type="checkbox" name="consent" value="1" required />
                    <?php esc_html_e( 'I consent to receive the validation report via email.', 'wpmr-product-feed-validator' ); ?>
                </label>
            </p>
            <?php if ( $captcha_provider !== 'none' && $captcha_site_key !== '' ) : ?>
            <p>
                <?php if ( $captcha_provider === 'recaptcha' ) : ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $captcha_site_key ); ?>"></div>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <?php elseif ( $captcha_provider === 'turnstile' ) : ?>
                    <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $captcha_site_key ); ?>"></div>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php endif; ?>
            <input type="hidden" name="sample" value="<?php echo $sample ? '1' : '0'; ?>" />
            <p>
                <button type="submit"><?php esc_html_e( 'Validate Feed', 'wpmr-product-feed-validator' ); ?></button>
            </p>
            <div class="wpmr-pfv-result" aria-live="polite"></div>
        </form>
        <?php
        self::enqueue_assets();
        return ob_get_clean();
    }

    protected static function enqueue_assets() {
        // Styles
        wp_enqueue_style( 'wpmr-pfv-public', \WPMR_PFV_PLUGIN_URL . 'assets/css/public.css', [], \WPMR_PFV_VERSION );
        // Script
        wp_enqueue_script( 'wpmr-pfv-public', \WPMR_PFV_PLUGIN_URL . 'assets/js/public.js', [ 'wp-api-fetch' ], \WPMR_PFV_VERSION, true );
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        wp_localize_script( 'wpmr-pfv-public', 'WPMR_PFV_I18N', [
            'validating'   => __( 'Validating…', 'wpmr-product-feed-validator' ),
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
