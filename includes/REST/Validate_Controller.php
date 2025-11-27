<?php
namespace WPMR\PFV\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Validate_Controller extends \WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'wpmr/v1';
        $this->rest_base = 'validate';
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_validate' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'url'     => [ 'type' => 'string', 'required' => true ],
                    'email'   => [ 'type' => 'string', 'required' => false ],
                    'consent' => [ 'type' => 'boolean', 'required' => false ],
                    'sample'  => [ 'type' => 'boolean', 'required' => false, 'default' => true ],
                    'captcha_token' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ] );
    }

    public function handle_validate( WP_REST_Request $request ) {
        $url     = trim( (string) $request->get_param( 'url' ) );
        $email   = (string) $request->get_param( 'email' );
        $consent = (bool) $request->get_param( 'consent' );
        $sample  = (bool) $request->get_param( 'sample' );
        $captcha_token = (string) $request->get_param( 'captcha_token' );

        if ( empty( $url ) ) {
            return new WP_Error( 'wpmr_pfv_missing_url', __( 'Feed URL is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        // Resolve recipient: if authenticated, use account email and ignore posted email
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $recipient = $user && $user->user_email ? $user->user_email : '';
        } else {
            $recipient = sanitize_email( $email );
            if ( ! is_email( $recipient ) ) {
                return new WP_Error( 'wpmr_pfv_invalid_email', __( 'A valid email is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
            }
        }

        // Consent enforcement (anonymous only)
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        if ( ! is_user_logged_in() && ! empty( $opts['require_consent'] ) && ! $consent ) {
            return new WP_Error( 'wpmr_pfv_missing_consent', __( 'Consent is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        // CAPTCHA verification for anonymous users if configured
        $ip = \WPMR\PFV\Services\Abuse::client_ip();
        if ( ! is_user_logged_in() ) {
            $provider = (string) ( $opts['captcha_provider'] ?? 'none' );
            $secret   = (string) ( $opts['captcha_secret_key'] ?? '' );
            if ( $provider !== 'none' && $secret !== '' ) {
                $ok = \WPMR\PFV\Services\Captcha::verify( $captcha_token, $provider, $secret, $ip );
                if ( is_wp_error( $ok ) ) { return $ok; }
            }
        }

        // Milestone 5: Security/Abuse controls — blocklist + rate limiting
        if ( \WPMR\PFV\Services\Abuse::is_blocked( $recipient, $ip ) ) {
            return new WP_Error( 'wpmr_pfv_blocked', __( 'Requests from this email, domain, or IP are blocked.', 'wpmr-product-feed-validator' ), [ 'status' => 403 ] );
        }
        $rate_ok = \WPMR\PFV\Services\Abuse::enforce_rate_limits( $recipient, $ip );
        if ( is_wp_error( $rate_ok ) ) { return $rate_ok; }

        // Milestone 2: Perform SSRF-safe fetch and streaming parse (sample-first)
        $fetch = \WPMR\PFV\Services\Fetcher::fetch( $url, [
            'timeout_seconds' => $opts['timeout_seconds'] ?? 20,
            'redirect_cap'    => $opts['redirect_cap'] ?? 3,
            'max_file_mb'     => $opts['max_file_mb'] ?? 100,
        ] );
        if ( is_wp_error( $fetch ) ) {
            return new WP_Error( $fetch->get_error_code(), $fetch->get_error_message(), [ 'status' => 400 ] );
        }

        $transport_diags = $fetch['diagnostics'];
        $parse = \WPMR\PFV\Services\Parser::parse_sample( (string) $fetch['body'], [
            'sample'      => $sample,
            'sample_size' => (int) ( $opts['sample_size'] ?? 500 ),
        ] );

        if ( is_wp_error( $parse ) ) {
            $transport_diags[] = [ 'severity' => 'error', 'code' => $parse->get_error_code(), 'message' => $parse->get_error_message() ];
            $parse_ok = false;
            $items_scanned = 0;
            $duplicates = [];
            $missing_id_count = 0;
            $format = null;
            $items = [];
        } else {
            $parse_ok = (bool) $parse['ok'];
            $items_scanned = (int) $parse['items_scanned'];
            $duplicates = (array) $parse['duplicates'];
            $missing_id_count = (int) $parse['missing_id_count'];
            $format = $parse['format'];
            $items = (array) $parse['items'];
            $transport_diags = array_merge( $transport_diags, (array) $parse['diagnostics'] );
        }

        // Milestone 3: Rules + Scoring
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( 'google-v2025-09' );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( 'google-v2025-09' );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        // Use effective weights throughout evaluation and scoring
        $eff_weights = \WPMR\PFV\Services\Rules::effective_weights( $pack, 'google-v2025-09' );
        $pack_eff = $pack; $pack_eff['weights'] = $eff_weights;
        $issues = \WPMR\PFV\Services\RulesEngine::evaluate( $items, $transport_diags, $pack_eff, $effective );
        $score_data = \WPMR\PFV\Services\Scoring::compute( $issues, $pack_eff );
        
        // Calculate quality scores for all products
        $quality_scores = \WPMR\PFV\Services\RulesEngine::calculate_all_quality_scores( $issues, $items );

        // Build report payload (scoring will be added in Milestone 3)
        $report = [
            'rule_version'      => 'google-v2025-09',
            'items_scanned'     => $items_scanned,
            'format'            => $format,
            'duplicates'        => $duplicates,
            'missing_id_count'  => $missing_id_count,
            'transport'         => [
                'http_code'    => $fetch['http_code'],
                'content_type' => $fetch['content_type'],
                'bytes'        => $fetch['bytes'],
            ],
            'diagnostics'       => $transport_diags,
            'issues'            => $issues,
            'score'             => $score_data['score'],
            'totals'            => $score_data['totals'],
            'quality_scores'    => $quality_scores,
        ];

        $delivery_mode = $opts['delivery_mode'] ?? 'email_plus_display';
        $message = sprintf( __( 'Validation request accepted for %s. A report will be emailed to %s.', 'wpmr-product-feed-validator' ), esc_url_raw( $url ), esc_html( $recipient ) );

        // Milestone 4: Persist report
        $weights_ov = \WPMR\PFV\Services\Rules::get_weights_overrides( 'google-v2025-09' );
        $override_count = count( (array) $overrides ) + count( array_filter( (array) $weights_ov, function($v){ return is_numeric($v); }) );
        $create = \WPMR\PFV\Services\Reports::create_report( [
            'url'              => $url,
            'email'            => $recipient,
            'rule_version'     => (string) ( $report['rule_version'] ?? 'google-v2025-09' ),
            'items_scanned'    => (int) $report['items_scanned'],
            'score'            => (int) $report['score'],
            'totals'           => (array) $report['totals'],
            'issues'           => (array) $report['issues'],
            'transport'        => (array) $report['transport'],
            'format'           => (string) $report['format'],
            'missing_id_count' => (int) $report['missing_id_count'],
            'duplicates'       => (array) $report['duplicates'],
            'override_count'   => (int) $override_count,
        ] );
        $public_key = is_array($create) ? ( $create['public_key'] ?? null ) : null;
        $public_endpoint = $public_key ? rest_url( 'wpmr/v1/reports/public/' . rawurlencode( $public_key ) ) : null;

        // Milestone 4: Email delivery (HTML + optional CSV)
        $subject_tpl = (string) ( $opts['email_subject_template'] ?? __( 'Your Product Feed Report — {score}/100', 'wpmr-product-feed-validator' ) );
        $body_tpl    = (string) ( $opts['email_body_template'] ?? __( 'Here is your product feed report for {url}. Items scanned: {items_scanned}. Errors: {errors}. Warnings: {warnings}. Date: {date}.', 'wpmr-product-feed-validator' ) );
        $tokens = [
            '{url}'            => $url,
            '{score}'          => (string) ( $report['score'] ?? '' ),
            '{items_scanned}'  => (string) ( $report['items_scanned'] ?? '' ),
            '{errors}'         => (string) ( $report['totals']['errors'] ?? 0 ),
            '{warnings}'       => (string) ( $report['totals']['warnings'] ?? 0 ),
            '{date}'           => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
            '{rule_version}'   => (string) ( $report['rule_version'] ?? 'google-v2025-09' ),
            '{override_count}' => (string) $override_count,
        ];
        $subject = strtr( $subject_tpl, $tokens );
        $body    = wpautop( esc_html( strtr( $body_tpl, $tokens ) ) );
        if ( $public_endpoint ) {
            $body .= '\n\n' . wp_kses_post( sprintf( __( 'Public report link: <a href="%s" target="_blank" rel="noopener">%s</a>', 'wpmr-product-feed-validator' ), esc_url( $public_endpoint ), esc_html( $public_endpoint ) ) );
        }

        $csv_content = null;
        if ( ! empty( $opts['attach_csv'] ) && ! empty( $report['issues'] ) && is_array( $report['issues'] ) ) {
            // Build CSV: item_id,code,severity,category,message
            $lines = [];
            $lines[] = 'item_id,code,severity,category,message';
            foreach ( $report['issues'] as $it ) {
                $row = [
                    isset($it['item_id']) ? (string) $it['item_id'] : '',
                    isset($it['code']) ? (string) $it['code'] : '',
                    isset($it['severity']) ? (string) $it['severity'] : '',
                    isset($it['category']) ? (string) $it['category'] : '',
                    isset($it['message']) ? (string) $it['message'] : '',
                ];
                // Escape CSV fields
                foreach ( $row as &$col ) {
                    $col = '"' . str_replace( '"', '""', $col ) . '"';
                }
                unset($col);
                $lines[] = implode( ',', $row );
                if ( count( $lines ) > 5000 ) { break; } // safety cap
            }
            $csv_content = implode( "\n", $lines );
        }

        // Send email now (both delivery modes send an email)
        \WPMR\PFV\Services\Email::send_report( $recipient, $subject, $body, $csv_content );

        // Optional webhook (fire-and-forget)
        $report_url = $public_endpoint ?: '';
        \WPMR\PFV\Services\Webhook::send( [
            'email'      => $recipient,
            'url'        => $url,
            'score'      => (int) ( $report['score'] ?? 0 ),
            'errors'     => (int) ( $report['totals']['errors'] ?? 0 ),
            'warnings'   => (int) ( $report['totals']['warnings'] ?? 0 ),
            'report_url' => $report_url,
        ] );

        if ( $delivery_mode === 'email_only' ) {
            return new WP_REST_Response( [ 'delivery_mode' => 'email_only', 'message' => $message, 'report_id' => (int) ( $create['id'] ?? 0 ), 'public_key' => $public_key, 'public_endpoint' => $public_endpoint ], 200 );
        }

        return new WP_REST_Response( [ 'delivery_mode' => 'email_plus_display', 'message' => $message, 'report' => $report, 'report_id' => (int) ( $create['id'] ?? 0 ), 'public_key' => $public_key, 'public_endpoint' => $public_endpoint ], 200 );
    }
}
