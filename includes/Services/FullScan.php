<?php
namespace WPMR\PFV\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FullScan {
    const HOOK = 'wpmr_pfv_full_scan';

    public static function queue( array $args ): bool {
        $payload = [
            'url'      => (string) ( $args['url'] ?? '' ),
            'email'    => (string) ( $args['email'] ?? '' ),
            'user_id'  => (int) ( $args['user_id'] ?? 0 ),
            'tokens'   => [
                'captcha' => (string) ( $args['captcha_token'] ?? '' ),
            ],
        ];
        return (bool) wp_schedule_single_event( time() + 5, self::HOOK, [ $payload ] );
    }

    public static function register_hook(): void {
        add_action( self::HOOK, [ __CLASS__, 'handle' ], 10, 1 );
    }

    public static function handle( array $payload ): void {
        $url = (string) ( $payload['url'] ?? '' );
        $email = (string) ( $payload['email'] ?? '' );
        $user_id = (int) ( $payload['user_id'] ?? 0 );

        if ( empty( $url ) ) { return; }

        // Resolve recipient
        if ( $user_id > 0 ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user && $user->user_email ) { $email = (string) $user->user_email; }
        }
        $recipient = $email;

        $opts = \WPMR\PFV\Admin\Settings::get_options();

        // Fetch (increase limits)
        $fetch = \WPMR\PFV\Services\Fetcher::fetch( $url, [
            'timeout_seconds' => max( 30, (int) ( $opts['timeout_seconds'] ?? 20 ) ),
            'redirect_cap'    => max( 3, (int) ( $opts['redirect_cap'] ?? 3 ) ),
            'max_file_mb'     => max( 200, (int) ( $opts['max_file_mb'] ?? 100 ) ),
        ] );
        if ( is_wp_error( $fetch ) ) {
            error_log( '[WPMR PFV] Full scan fetch error: ' . $fetch->get_error_message() );
            return;
        }

        $transport_diags = $fetch['diagnostics'];

        // Parse full (no sampling)
        $parse = \WPMR\PFV\Services\Parser::parse_sample( (string) $fetch['body'], [ 'sample' => false, 'sample_size' => 0 ] );
        if ( is_wp_error( $parse ) ) {
            $transport_diags[] = [ 'severity' => 'error', 'code' => $parse->get_error_code(), 'message' => $parse->get_error_message() ];
            $items_scanned = 0; $duplicates = []; $missing_id_count = 0; $format = null; $items = [];
        } else {
            $items_scanned = (int) $parse['items_scanned'];
            $duplicates = (array) $parse['duplicates'];
            $missing_id_count = (int) $parse['missing_id_count'];
            $format = $parse['format'];
            $items = (array) $parse['items'];
            $transport_diags = array_merge( $transport_diags, (array) $parse['diagnostics'] );
        }

        // Rules + scoring (effective)
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( 'google-v2025-09' );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( 'google-v2025-09' );
        $eff_weights = \WPMR\PFV\Services\Rules::effective_weights( $pack, 'google-v2025-09' );
        $pack_eff = $pack; $pack_eff['weights'] = $eff_weights;
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        $issues = \WPMR\PFV\Services\RulesEngine::evaluate( $items, $transport_diags, $pack_eff, $effective );
        $score_data = \WPMR\PFV\Services\Scoring::compute( $issues, $pack_eff );

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
        ];

        // Persist
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

        // Email + webhook
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
            $lines = []; $lines[] = 'item_id,code,severity,category,message';
            foreach ( $report['issues'] as $it ) {
                $row = [ (string) ( $it['item_id'] ?? '' ), (string) ( $it['code'] ?? '' ), (string) ( $it['severity'] ?? '' ), (string) ( $it['category'] ?? '' ), (string) ( $it['message'] ?? '' ) ];
                foreach ( $row as &$col ) { $col = '"' . str_replace( '"', '""', $col ) . '"'; } unset($col);
                $lines[] = implode( ',', $row ); if ( count( $lines ) > 20000 ) { break; }
            }
            $csv_content = implode( "\n", $lines );
        }
        \WPMR\PFV\Services\Email::send_report( $recipient, $subject, $body, $csv_content );

        $report_url = $public_endpoint ?: '';
        \WPMR\PFV\Services\Webhook::send( [
            'email'      => $recipient,
            'url'        => $url,
            'score'      => (int) ( $report['score'] ?? 0 ),
            'errors'     => (int) ( $report['totals']['errors'] ?? 0 ),
            'warnings'   => (int) ( $report['totals']['warnings'] ?? 0 ),
            'report_url' => $report_url,
        ] );
    }
}
