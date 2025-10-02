<?php
namespace WPMR\PFV\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Webhook {
    /**
     * Send webhook (fire-and-forget). Returns true if dispatched.
     * Payload example: { email, url, score, errors, warnings, report_url }
     */
    public static function send( array $payload ): bool {
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        $endpoint = (string) ( $opts['webhook_url'] ?? '' );
        if ( $endpoint === '' ) { return false; }

        $args = [
            'method'      => 'POST',
            'timeout'     => 5,
            'redirection' => 0,
            'blocking'    => false, // fire-and-forget
            'headers'     => [ 'Content-Type' => 'application/json' ],
            'body'        => wp_json_encode( $payload ),
        ];

        $res = \wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $res ) ) {
            \error_log( '[WPMR PFV] Webhook dispatch failed: ' . $res->get_error_message() );
            return false;
        }
        return true;
    }
}
