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
        
        // Stop immediately if HTTP status is not 200
        if ( (int) $code !== 200 ) {
            return new WP_Error( 
                'wpmr_pfv_http_status', 
                sprintf( 
                    __( 'Cannot reach the feed file. HTTP status: %d', 'wpmr-product-feed-validator' ), 
                    (int) $code 
                ),
                [ 'http_code' => (int) $code ]
            );
        }

        $ct   = (string) wp_remote_retrieve_header( $resp, 'content-type' );
        $body = wp_remote_retrieve_body( $resp );
        $len  = strlen( $body );

        $diagnostics = [];
        if ( $len <= 0 ) {
            $diagnostics[] = [ 'severity' => 'error', 'code' => 'empty_body', 'message' => __( 'Response body is empty.', 'wpmr-product-feed-validator' ) ];
        }

        // Enhanced XML declaration and encoding validation
        $validation_result = self::validate_xml_declaration( $body );
        if ( ! empty( $validation_result['diagnostics'] ) ) {
            $diagnostics = array_merge( $diagnostics, $validation_result['diagnostics'] );
        }

        // Content-Type check
        $is_xml_ct = stripos( $ct, 'xml' ) !== false;
        if ( ! $is_xml_ct && ! $validation_result['has_declaration'] ) {
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
     * Validate XML declaration and encoding.
     *
     * @param string $xml XML content to validate
     * @return array{has_declaration:bool,version:?string,encoding:?string,diagnostics:array}
     */
    protected static function validate_xml_declaration( string $xml ) {
        $diagnostics = [];
        $has_declaration = false;
        $version = null;
        $encoding = null;

        // Check for BOM
        $bom_detected = self::detect_bom( $xml );
        if ( $bom_detected ) {
            $diagnostics[] = [
                'severity' => 'warning',
                'code' => 'bom_detected',
                'message' => sprintf(
                    __( 'BOM (Byte Order Mark) detected: %s. This may cause parsing issues.', 'wpmr-product-feed-validator' ),
                    $bom_detected
                ),
            ];
        }

        // Extract XML declaration
        $trimmed = ltrim( $xml );
        if ( strpos( $trimmed, '<?xml' ) === 0 ) {
            $has_declaration = true;
            
            // Extract declaration attributes
            if ( preg_match( '/^<\?xml\s+([^?]*)\?>/i', $trimmed, $matches ) ) {
                $decl = $matches[1];
                
                // Extract version
                if ( preg_match( '/version\s*=\s*["\']([^"\']+)["\']/', $decl, $ver_match ) ) {
                    $version = $ver_match[1];
                    if ( ! in_array( $version, [ '1.0', '1.1' ], true ) ) {
                        $diagnostics[] = [
                            'severity' => 'error',
                            'code' => 'invalid_xml_version',
                            'message' => sprintf(
                                __( 'Invalid XML version "%s". Must be "1.0" or "1.1".', 'wpmr-product-feed-validator' ),
                                esc_html( $version )
                            ),
                        ];
                    }
                } else {
                    $diagnostics[] = [
                        'severity' => 'warning',
                        'code' => 'missing_xml_version',
                        'message' => __( 'XML declaration missing version attribute.', 'wpmr-product-feed-validator' ),
                    ];
                }
                
                // Extract encoding
                if ( preg_match( '/encoding\s*=\s*["\']([^"\']+)["\']/', $decl, $enc_match ) ) {
                    $encoding = strtoupper( $enc_match[1] );
                    
                    // Validate encoding value
                    $valid_encodings = [ 'UTF-8', 'UTF-16', 'ISO-8859-1', 'US-ASCII' ];
                    if ( ! in_array( $encoding, $valid_encodings, true ) ) {
                        $diagnostics[] = [
                            'severity' => 'warning',
                            'code' => 'uncommon_encoding',
                            'message' => sprintf(
                                __( 'Uncommon encoding "%s" declared. Recommended: UTF-8.', 'wpmr-product-feed-validator' ),
                                esc_html( $encoding )
                            ),
                        ];
                    }
                    
                    // Check BOM vs declared encoding mismatch
                    if ( $bom_detected && $bom_detected !== $encoding ) {
                        $diagnostics[] = [
                            'severity' => 'error',
                            'code' => 'encoding_mismatch',
                            'message' => sprintf(
                                __( 'Encoding mismatch: BOM indicates %s but declaration says %s.', 'wpmr-product-feed-validator' ),
                                $bom_detected,
                                esc_html( $encoding )
                            ),
                        ];
                    }
                } else {
                    $diagnostics[] = [
                        'severity' => 'warning',
                        'code' => 'missing_encoding',
                        'message' => __( 'XML declaration missing encoding attribute. UTF-8 assumed.', 'wpmr-product-feed-validator' ),
                    ];
                }
            }
        } else {
            $diagnostics[] = [
                'severity' => 'warning',
                'code' => 'missing_xml_declaration',
                'message' => __( 'XML declaration (<?xml version="1.0" encoding="UTF-8"?>) is missing.', 'wpmr-product-feed-validator' ),
            ];
        }

        return [
            'has_declaration' => $has_declaration,
            'version' => $version,
            'encoding' => $encoding,
            'diagnostics' => $diagnostics,
        ];
    }

    /**
     * Detect BOM (Byte Order Mark) in XML content.
     *
     * @param string $xml XML content
     * @return string|null BOM encoding detected or null
     */
    protected static function detect_bom( string $xml ) {
        // UTF-8 BOM: EF BB BF
        if ( substr( $xml, 0, 3 ) === "\xEF\xBB\xBF" ) {
            return 'UTF-8';
        }
        // UTF-16 BE BOM: FE FF
        if ( substr( $xml, 0, 2 ) === "\xFE\xFF" ) {
            return 'UTF-16BE';
        }
        // UTF-16 LE BOM: FF FE
        if ( substr( $xml, 0, 2 ) === "\xFF\xFE" ) {
            return 'UTF-16LE';
        }
        // UTF-32 BE BOM: 00 00 FE FF
        if ( substr( $xml, 0, 4 ) === "\x00\x00\xFE\xFF" ) {
            return 'UTF-32BE';
        }
        // UTF-32 LE BOM: FF FE 00 00
        if ( substr( $xml, 0, 4 ) === "\xFF\xFE\x00\x00" ) {
            return 'UTF-32LE';
        }
        return null;
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
