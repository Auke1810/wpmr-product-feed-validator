<?php
namespace WPMR\PFV\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FullScan_Controller extends \WP_REST_Controller {
    public function __construct() {
        $this->namespace = 'wpmr/v1';
        $this->rest_base = 'fullscan';
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_fullscan' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'url'           => [ 'type' => 'string', 'required' => true ],
                    'email'         => [ 'type' => 'string', 'required' => false ],
                    'consent'       => [ 'type' => 'boolean', 'required' => false ],
                    'captcha_token' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ] );
    }

    public function handle_fullscan( WP_REST_Request $request ) {
        $url           = trim( (string) $request->get_param( 'url' ) );
        $email         = (string) $request->get_param( 'email' );
        $consent       = (bool) $request->get_param( 'consent' );
        $captcha_token = (string) $request->get_param( 'captcha_token' );

        if ( empty( $url ) ) {
            return new WP_Error( 'wpmr_pfv_missing_url', __( 'Feed URL is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        // Resolve recipient
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $recipient = $user && $user->user_email ? $user->user_email : '';
            $user_id = (int) ( $user ? $user->ID : 0 );
        } else {
            $recipient = sanitize_email( $email );
            $user_id = 0;
            if ( ! is_email( $recipient ) ) {
                return new WP_Error( 'wpmr_pfv_invalid_email', __( 'A valid email is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
            }
        }

        $opts = \WPMR\PFV\Admin\Settings::get_options();
        if ( ! is_user_logged_in() && ! empty( $opts['require_consent'] ) && ! $consent ) {
            return new WP_Error( 'wpmr_pfv_missing_consent', __( 'Consent is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        $ip = \WPMR\PFV\Services\Abuse::client_ip();
        if ( ! is_user_logged_in() ) {
            $provider = (string) ( $opts['captcha_provider'] ?? 'none' );
            $secret   = (string) ( $opts['captcha_secret_key'] ?? '' );
            if ( $provider !== 'none' && $secret !== '' ) {
                $ok = \WPMR\PFV\Services\Captcha::verify( $captcha_token, $provider, $secret, $ip );
                if ( is_wp_error( $ok ) ) { return $ok; }
            }
        }

        if ( \WPMR\PFV\Services\Abuse::is_blocked( $recipient, $ip ) ) {
            return new WP_Error( 'wpmr_pfv_blocked', __( 'Requests from this email, domain, or IP are blocked.', 'wpmr-product-feed-validator' ), [ 'status' => 403 ] );
        }
        $rate_ok = \WPMR\PFV\Services\Abuse::enforce_rate_limits( $recipient, $ip );
        if ( is_wp_error( $rate_ok ) ) { return $rate_ok; }

        // Queue background job
        \WPMR\PFV\Services\FullScan::queue( [
            'url'           => $url,
            'email'         => $recipient,
            'user_id'       => $user_id,
            'captcha_token' => $captcha_token,
        ] );

        $msg = sprintf( __( 'Full scan has been queued for %s. We\'ll email the full report to %s when it\'s done.', 'wpmr-product-feed-validator' ), esc_url_raw( $url ), esc_html( $recipient ) );
        return new WP_REST_Response( [ 'queued' => true, 'message' => $msg ], 202 );
    }
}
