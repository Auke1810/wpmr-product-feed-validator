<?php
namespace WPMR\PFV\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Fetcher {
    /**
     * Validate a URL for http(s) scheme and public host/IP.
     */
    public static function validate_url( string $url ) {
        $url = trim( $url );
        if ( $url === '' ) {
            return new WP_Error( 'wpmr_pfv_url_empty', __( 'Feed URL is required.', 'wpmr-product-feed-validator' ) );
        }
        $parts = wp_parse_url( $url );
        if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
            return new WP_Error( 'wpmr_pfv_url_scheme', __( 'URL must start with http or https.', 'wpmr-product-feed-validator' ) );
        }
        if ( empty( $parts['host'] ) ) {
            return new WP_Error( 'wpmr_pfv_url_host', __( 'URL must contain a valid host.', 'wpmr-product-feed-validator' ) );
        }

        // If host is an IP literal, ensure it is public.
        if ( filter_var( $parts['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
            if ( ! self::is_public_ip( $parts['host'] ) ) {
                return new WP_Error( 'wpmr_pfv_private_ip', __( 'URL resolves to a non-public IP and is not allowed.', 'wpmr-product-feed-validator' ) );
            }
        } else {
            // Resolve A and AAAA records and ensure all are public.
            $ips_v4 = gethostbynamel( $parts['host'] ) ?: [];
            $ips_v6 = [];
            if ( function_exists( 'dns_get_record' ) ) {
                $aaaa = @dns_get_record( $parts['host'], DNS_AAAA );
                if ( is_array( $aaaa ) ) {
                    foreach ( $aaaa as $rec ) {
                        if ( ! empty( $rec['ipv6'] ) ) { $ips_v6[] = $rec['ipv6']; }
                    }
                }
            }
            foreach ( array_merge( $ips_v4, $ips_v6 ) as $ip ) {
                if ( ! self::is_public_ip( $ip ) ) {
                    return new WP_Error( 'wpmr_pfv_private_ip', __( 'URL resolves to a non-public IP and is not allowed.', 'wpmr-product-feed-validator' ) );
                }
            }
        }

        return [ 'ok' => true, 'url' => $url ];
    }

    /**
     * Perform a GET request with safety limits and diagnostics.
     */
    public static function fetch( string $url, array $opts ) {
        $val = self::validate_url( $url );
        if ( is_wp_error( $val ) ) { return $val; }

        $timeout     = max( 1, intval( $opts['timeout_seconds'] ?? 20 ) );
        $redirects   = max( 0, intval( $opts['redirect_cap'] ?? 3 ) );
        $max_file_mb = max( 1, intval( $opts['max_file_mb'] ?? 100 ) );
        $limit_bytes = $max_file_mb * 1024 * 1024;

        $args = [
            'timeout'               => $timeout,
            'redirection'           => $redirects,
            'user-agent'            => 'WPMR-Product-Feed-Validator/' . ( defined('WPMR_PFV_VERSION') ? WPMR_PFV_VERSION : '0.1.0' ),
            'reject_unsafe_urls'    => true,
            'limit_response_size'   => $limit_bytes,
            'headers'               => [ 'Accept' => 'application/xml, text/xml;q=0.9, */*;q=0.5' ],
        ];

        $resp = wp_remote_get( $url, $args );
        if ( is_wp_error( $resp ) ) {
            return new WP_Error( 'wpmr_pfv_http_error', $resp->get_error_message(), [ 'error' => $resp ] );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $ct   = (string) wp_remote_retrieve_header( $resp, 'content-type' );
        $body = wp_remote_retrieve_body( $resp );
        $len  = strlen( $body );

        $diagnostics = [];
        if ( (int) $code !== 200 ) {
            $diagnostics[] = [ 'severity' => 'error', 'code' => 'http_status', 'message' => sprintf( __( 'HTTP status not 200 (got %d).', 'wpmr-product-feed-validator' ), (int) $code ) ];
        }
        if ( $len <= 0 ) {
            $diagnostics[] = [ 'severity' => 'error', 'code' => 'empty_body', 'message' => __( 'Response body is empty.', 'wpmr-product-feed-validator' ) ];
        }

        $is_xml_ct = stripos( $ct, 'xml' ) !== false;
        $has_xml_decl = strpos( ltrim( $body ), '<?xml' ) === 0;
        if ( ! $is_xml_ct && ! $has_xml_decl ) {
            $diagnostics[] = [ 'severity' => 'error', 'code' => 'content_type', 'message' => __( 'Content-Type not XML and no XML declaration found.', 'wpmr-product-feed-validator' ) ];
        } elseif ( ! $is_xml_ct ) {
            $diagnostics[] = [ 'severity' => 'warning', 'code' => 'content_type_warning', 'message' => __( 'Content-Type header does not indicate XML.', 'wpmr-product-feed-validator' ) ];
        }

        return [
            'ok'            => empty( array_filter( $diagnostics, fn($d) => $d['severity'] === 'error' ) ),
            'http_code'     => (int) $code,
            'content_type'  => $ct,
            'bytes'         => $len,
            'body'          => $body,
            'diagnostics'   => $diagnostics,
        ];
    }

    /**
     * Check if IP is public (not private/reserved).
     */
    protected static function is_public_ip( string $ip ): bool {
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
        }
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
        }
        return false;
    }
}
