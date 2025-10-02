<?php
namespace WPMR\PFV\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Email {
    /**
     * Send an email with optional CSV attachment.
     * Returns true on success. Falls back to text-only if HTML send fails.
     */
    public static function send_report( string $to, string $subject, string $html_body, ?string $csv_content = null, string $csv_filename = 'feed-report.csv' ): bool {
        $attachments = [];
        $tmp_file = '';
        if ( $csv_content !== null && $csv_content !== '' ) {
            // Ensure .csv extension
            if ( substr( $csv_filename, -4 ) !== '.csv' ) {
                $csv_filename .= '.csv';
            }
            // Prefer uploads directory to preserve filename extension for the recipient
            $upload = function_exists('wp_upload_dir') ? \wp_upload_dir() : null;
            if ( is_array($upload) && ! empty( $upload['path'] ) && is_dir( $upload['path'] ) && is_writable( $upload['path'] ) ) {
                $base = function_exists('sanitize_file_name') ? \sanitize_file_name( $csv_filename ) : $csv_filename;
                $unique = function_exists('wp_unique_filename') ? \wp_unique_filename( $upload['path'], $base ) : ( time() . '-' . $base );
                $tmp_file = rtrim( $upload['path'], '/\\' ) . DIRECTORY_SEPARATOR . $unique;
            } elseif ( function_exists( 'wp_tempnam' ) ) {
                $tmp_file = \wp_tempnam( $csv_filename );
                // Force .csv extension if temp name uses .tmp
                if ( substr( $tmp_file, -4 ) !== '.csv' ) {
                    $csv_path = $tmp_file . '.csv';
                    @\rename( $tmp_file, $csv_path );
                    $tmp_file = $csv_path;
                }
            } else {
                $tmp = \tempnam( sys_get_temp_dir(), 'pfv_' );
                $tmp_file = $tmp . '.csv';
                @\rename( $tmp, $tmp_file );
            }
            if ( $tmp_file ) {
                \file_put_contents( $tmp_file, $csv_content );
                $attachments[] = $tmp_file;
            }
        }

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        if ( ! empty( $attachments ) ) {
            $sent = \wp_mail( $to, $subject, $html_body, $headers, $attachments );
        } else {
            // Do not pass empty attachments array to avoid provider edge-cases
            $sent = \wp_mail( $to, $subject, $html_body, $headers );
        }

        if ( ! $sent ) {
            // Fallback to text-only
            $text = \wp_strip_all_tags( $html_body );
            $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
            if ( ! empty( $attachments ) ) {
                $sent = \wp_mail( $to, $subject, $text, $headers, $attachments );
            } else {
                $sent = \wp_mail( $to, $subject, $text, $headers );
            }
            if ( ! $sent ) {
                \error_log( '[WPMR PFV] Failed to send report email to ' . $to );
            }
        }

        if ( $tmp_file && \file_exists( $tmp_file ) ) {
            @\unlink( $tmp_file );
        }

        return (bool) $sent;
    }
}
