<?php
namespace WPMR\PFV\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Rules_Controller extends \WP_REST_Controller {
    protected string $version = 'google-v2025-09';

    public function __construct() {
        $this->namespace = 'wpmr/v1';
        $this->rest_base = 'rules';
    }

    public function get_weights( WP_REST_Request $req ) {
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $over = \WPMR\PFV\Services\Rules::get_weights_overrides( $this->version );
        $eff = \WPMR\PFV\Services\Rules::effective_weights( $pack, $this->version );
        return new WP_REST_Response( [
            'rule_version' => $pack['id'] ?? $this->version,
            'base'         => $pack['weights'] ?? [],
            'overrides'    => $over,
            'effective'    => $eff,
        ], 200 );
    }

    public function set_weights( WP_REST_Request $req ) {
        $weights = [];
        foreach ( [ 'error','warning','advice','cap_per_category' ] as $k ) {
            $v = $req->get_param( $k );
            if ( $v !== null && is_numeric( $v ) ) { $weights[ $k ] = (int) $v; }
        }
        \WPMR\PFV\Services\Rules::set_weights_overrides( $this->version, $weights );
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $eff = \WPMR\PFV\Services\Rules::effective_weights( $pack, $this->version );
        return new WP_REST_Response( [ 'updated' => true, 'effective' => $eff ], 200 );
    }

    public function export_config( WP_REST_Request $req ) {
        $weights = \WPMR\PFV\Services\Rules::get_weights_overrides( $this->version );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( $this->version );
        return new WP_REST_Response( [
            'rule_version' => $this->version,
            'weights'      => $weights,
            'overrides'    => $overrides,
        ], 200 );
    }

    public function import_config( WP_REST_Request $req ) {
        $payload = $req->get_json_params();
        if ( ! is_array( $payload ) ) {
            return new WP_Error( 'wpmr_pfv_invalid_json', __( 'Invalid JSON payload.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }
        if ( isset( $payload['weights'] ) && is_array( $payload['weights'] ) ) {
            \WPMR\PFV\Services\Rules::set_weights_overrides( $this->version, $payload['weights'] );
        }
        if ( isset( $payload['overrides'] ) && is_array( $payload['overrides'] ) ) {
            // Replace all overrides for this version
            \WPMR\PFV\Services\Rules::set_overrides( $this->version, $payload['overrides'] );
        }
        return new WP_REST_Response( [ 'imported' => true ], 200 );
    }

    public function restore_defaults( WP_REST_Request $req ) {
        \WPMR\PFV\Services\Rules::delete_weights_overrides( $this->version );
        \WPMR\PFV\Services\Rules::delete_overrides( $this->version );
        return new WP_REST_Response( [ 'restored' => true ], 200 );
    }

    public function register_routes() {
        // GET /rules → current effective rules
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_rules' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );

        // POST /rules/overrides → upsert one override
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/overrides', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'upsert_override' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
                'args'                => [
                    'rule_code'      => [ 'type' => 'string', 'required' => true ],
                    'severity'       => [ 'type' => 'string', 'required' => false ],
                    'enabled'        => [ 'type' => 'boolean', 'required' => false ],
                    'weight_override'=> [ 'type' => 'integer', 'required' => false ],
                ],
            ],
        ] );

        // DELETE /rules/overrides/{rule_code} → remove override
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/overrides/(?P<rule_code>[a-zA-Z0-9_\-]+)', [
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_override' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );

        // POST /rules/preview → dry-run validate with temporary overrides
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/preview', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'preview' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
                'args'                => [
                    'url'        => [ 'type' => 'string', 'required' => true ],
                    'sample'     => [ 'type' => 'boolean', 'required' => false, 'default' => true ],
                    'overrides'  => [ 'type' => 'object', 'required' => false ],
                ],
            ],
        ] );

        // GET /rules/weights → effective + overrides
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/weights', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_weights' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );

        // POST /rules/weights → set global weight overrides
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/weights', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'set_weights' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
                'args'                => [
                    'error'            => [ 'type' => 'integer', 'required' => false ],
                    'warning'          => [ 'type' => 'integer', 'required' => false ],
                    'advice'           => [ 'type' => 'integer', 'required' => false ],
                    'cap_per_category' => [ 'type' => 'integer', 'required' => false ],
                ],
            ],
        ] );

        // GET /rules/export → current overrides + weight overrides
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/export', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'export_config' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );

        // POST /rules/import → replace overrides + weight overrides from payload
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'import_config' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );

        // POST /rules/restore → delete overrides + weight overrides
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/restore', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'restore_defaults' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );
    }

    public function get_rules( WP_REST_Request $req ) {
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( $this->version );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        return new WP_REST_Response( [
            'rule_version' => $pack['id'] ?? $this->version,
            'weights'      => \WPMR\PFV\Services\Rules::effective_weights( $pack, $this->version ),
            'rules'        => array_values( $effective ),
        ], 200 );
    }

    public function upsert_override( WP_REST_Request $req ) {
        $rule_code = (string) $req->get_param( 'rule_code' );
        $severity  = $req->get_param( 'severity' );
        $enabled   = $req->get_param( 'enabled' );
        $weight    = $req->get_param( 'weight_override' );

        if ( $rule_code === '' ) {
            return new WP_Error( 'wpmr_pfv_missing_code', __( 'rule_code is required.', 'wpmr-product-feed-validator' ), [ 'status' => 400 ] );
        }

        $overrides = \WPMR\PFV\Services\Rules::get_overrides( $this->version );
        if ( ! isset( $overrides[ $rule_code ] ) ) { $overrides[ $rule_code ] = []; }
        if ( $severity !== null && in_array( $severity, [ 'error','warning','advice' ], true ) ) {
            $overrides[ $rule_code ]['severity'] = $severity;
        }
        if ( $enabled !== null ) {
            $overrides[ $rule_code ]['enabled'] = $enabled ? 1 : 0;
        }
        if ( $weight !== null && is_numeric( $weight ) ) {
            $overrides[ $rule_code ]['weight_override'] = (int) $weight;
        }

        \WPMR\PFV\Services\Rules::set_overrides( $this->version, $overrides );

        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        return new WP_REST_Response( [ 'updated' => true, 'rule' => ( $effective[ $rule_code ] ?? null ) ], 200 );
    }

    public function delete_override( WP_REST_Request $req ) {
        $rule_code = (string) $req->get_param( 'rule_code' );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( $this->version );
        if ( isset( $overrides[ $rule_code ] ) ) {
            unset( $overrides[ $rule_code ] );
            \WPMR\PFV\Services\Rules::set_overrides( $this->version, $overrides );
        }
        return new WP_REST_Response( [ 'deleted' => true ], 200 );
    }

    public function preview( WP_REST_Request $req ) {
        $url = (string) $req->get_param( 'url' );
        $sample = (bool) $req->get_param( 'sample' );
        $tmp_overrides = (array) $req->get_param( 'overrides' );

        $opts = \WPMR\PFV\Admin\Settings::get_options();
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
            $items = [];
        } else {
            $items = (array) $parse['items'];
            $transport_diags = array_merge( $transport_diags, (array) $parse['diagnostics'] );
        }

        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $persist_overrides = \WPMR\PFV\Services\Rules::get_overrides( $this->version );
        $eff_weights = \WPMR\PFV\Services\Rules::effective_weights( $pack, $this->version );

        // Baseline (current persisted overrides)
        $effective_base = \WPMR\PFV\Services\Rules::effective_rules( $pack, $persist_overrides );
        $pack_base = $pack; $pack_base['weights'] = $eff_weights;
        $issues_base = \WPMR\PFV\Services\RulesEngine::evaluate( $items, $transport_diags, $pack_base, $effective_base );
        $score_base  = \WPMR\PFV\Services\Scoring::compute( $issues_base, $pack_base );

        // Preview (merged overrides)
        $effective_prev = \WPMR\PFV\Services\Rules::effective_rules( $pack, array_merge( $persist_overrides, $tmp_overrides ) );
        $pack_prev = $pack; $pack_prev['weights'] = $eff_weights;
        $issues_prev = \WPMR\PFV\Services\RulesEngine::evaluate( $items, $transport_diags, $pack_prev, $effective_prev );
        $score_prev  = \WPMR\PFV\Services\Scoring::compute( $issues_prev, $pack_prev );

        // Deltas
        $score_delta = (int) ( ( $score_prev['score'] ?? 0 ) - ( $score_base['score'] ?? 0 ) );
        $totals_base = (array) ( $score_base['totals'] ?? [] );
        $totals_prev = (array) ( $score_prev['totals'] ?? [] );
        $totals_delta = [
            'errors'   => (int) ( ( $totals_prev['errors'] ?? 0 ) - ( $totals_base['errors'] ?? 0 ) ),
            'warnings' => (int) ( ( $totals_prev['warnings'] ?? 0 ) - ( $totals_base['warnings'] ?? 0 ) ),
            'advice'   => (int) ( ( $totals_prev['advice'] ?? 0 ) - ( $totals_base['advice'] ?? 0 ) ),
        ];

        return new WP_REST_Response( [
            'rule_version' => $pack['id'] ?? $this->version,
            'baseline' => [ 'score' => $score_base['score'] ?? null, 'totals' => $score_base['totals'] ?? [] ],
            'preview'  => [ 'score' => $score_prev['score'] ?? null, 'totals' => $score_prev['totals'] ?? [] ],
            'delta'    => [ 'score' => $score_delta, 'totals' => $totals_delta ],
            'issues'   => $issues_prev,
        ], 200 );
    }
}
