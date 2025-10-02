<?php
namespace WPMR\PFV\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Captcha {
    /**
     * Verify captcha token for configured provider.
     * Returns true when valid, or WP_Error on failure.
     */
    public static function verify( string $token, string $provider, string $secret, string $ip = '' ) {
        $token = trim( $token );
        if ( $provider === 'none' || $secret === '' ) { return true; }
        if ( $token === '' ) {
            return new WP_Error( 'wpmr_pfv_captcha_missing', __( 'CAPTCHA is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        if ( $provider === 'recaptcha' ) {
            $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
            $res = wp_remote_post( $endpoint, [
                'timeout' => 8,
                'body' => [ 'secret' => $secret, 'response' => $token, 'remoteip' => $ip ],
            ] );
            if ( is_wp_error( $res ) ) { return new WP_Error( 'wpmr_pfv_captcha_http_error', $res->get_error_message(), [ 'status' => 400 ] ); }
            $code = wp_remote_retrieve_response_code( $res );
            $body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
            if ( $code !== 200 || empty( $body['success'] ) ) {
                return new WP_Error( 'wpmr_pfv_captcha_failed', __( 'CAPTCHA verification failed.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
            }
            return true;
        }

        if ( $provider === 'turnstile' ) {
            $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $res = wp_remote_post( $endpoint, [
                'timeout' => 8,
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body' => wp_json_encode( [ 'secret' => $secret, 'response' => $token, 'remoteip' => $ip ] ),
            ] );
            if ( is_wp_error( $res ) ) { return new WP_Error( 'wpmr_pfv_captcha_http_error', $res->get_error_message(), [ 'status' => 400 ] ); }
            $code = wp_remote_retrieve_response_code( $res );
            $body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
            if ( $code !== 200 || empty( $body['success'] ) ) {
                return new WP_Error( 'wpmr_pfv_captcha_failed', __( 'CAPTCHA verification failed.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
            }
            return true;
        }

        // Unknown provider: treat as disabled
        return true;
    }
}
