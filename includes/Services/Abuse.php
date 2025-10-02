<?php
namespace WPMR\PFV\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Abuse {
    public static function client_ip(): string {
        // Basic IP detection (keep simple to avoid spoofing via headers)
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    }

    public static function is_blocked( string $email, string $ip ): bool {
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        $list = (array) ( $opts['blocklist'] ?? [] );
        $email_l = strtolower( trim( $email ) );
        $domain = '';
        if ( $email_l && false !== strpos( $email_l, '@' ) ) {
            $domain = substr( $email_l, strrpos( $email_l, '@' ) + 1 );
        }
        foreach ( $list as $entry ) {
            $e = strtolower( trim( (string) $entry ) );
            if ( $e === '' ) { continue; }
            if ( $email_l !== '' && $email_l === $e ) { return true; }
            if ( $domain !== '' && $domain === $e ) { return true; }
            if ( $ip !== '' && $ip === $e ) { return true; }
        }
        return false;
    }

    public static function enforce_rate_limits( string $email, string $ip ) {
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        $max_ip = max( 0, (int) ( $opts['rate_limit_ip_per_day'] ?? 0 ) );
        $max_email = max( 0, (int) ( $opts['rate_limit_email_per_day'] ?? 0 ) );
        $date = gmdate('Ymd');
        $ttl = self::ttl_until( strtotime( '+1 day midnight' ) );

        if ( $max_ip > 0 && $ip ) {
            $ip_key = 'wpmr_pfv_rl_ip_' . $date . '_' . md5( $ip );
            $count = (int) get_transient( $ip_key );
            if ( $count >= $max_ip ) {
                return new WP_Error( 'wpmr_pfv_rate_limited_ip', __( 'Rate limit exceeded for this IP. Please try again later.', 'wpmr-product-feed-validator' ), [ 'status' => 429 ] );
            }
            set_transient( $ip_key, $count + 1, $ttl );
        }

        if ( $max_email > 0 && $email ) {
            $hash = hash( 'sha256', strtolower( trim( $email ) ) );
            $em_key = 'wpmr_pfv_rl_em_' . $date . '_' . $hash;
            $count = (int) get_transient( $em_key );
            if ( $count >= $max_email ) {
                return new WP_Error( 'wpmr_pfv_rate_limited_email', __( 'Rate limit exceeded for this email address. Please try again later.', 'wpmr-product-feed-validator' ), [ 'status' => 429 ] );
            }
            set_transient( $em_key, $count + 1, $ttl );
        }

        return true;
    }

    protected static function ttl_until( int $target ): int {
        $now = time();
        $ttl = $target - $now;
        if ( $ttl <= 0 ) { $ttl = DAY_IN_SECONDS; }
        return $ttl;
    }
}
