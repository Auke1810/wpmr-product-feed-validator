<?php
namespace WPMR\PFV\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Reports_Controller extends \WP_REST_Controller {
    public function __construct() {
        $this->namespace = 'wpmr/v1';
        $this->rest_base = 'reports';
    }

    public function register_routes() {
        // GET /reports/public/{public_key}
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/public/(?P<public_key>[A-Za-z0-9]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_public' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'public_key' => [ 'type' => 'string', 'required' => true ],
                ],
            ],
        ] );
    }

    public function get_public( WP_REST_Request $req ) {
        $key = (string) $req->get_param( 'public_key' );
        $data = \WPMR\PFV\Services\Reports::fetch_public_report( $key );
        if ( ! $data ) {
            return new WP_Error( 'wpmr_pfv_not_found', __( 'Report not found or expired.', 'wpmr-product-feed-validator' ), [ 'status' => 404 ] );
        }
        return new WP_REST_Response( $data, 200 );
    }
}
