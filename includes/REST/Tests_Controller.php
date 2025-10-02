<?php
namespace WPMR\PFV\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tests_Controller extends \WP_REST_Controller {
    protected string $version = 'google-v2025-09';

    public function __construct() {
        $this->namespace = 'wpmr/v1';
        $this->rest_base = 'tests';
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'run' ],
                'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            ],
        ] );
    }

    public function run( WP_REST_Request $req ) {
        $results = [];

        $results[] = $this->test_availability_normalization();
        $results[] = $this->test_shipping_price_invalid();
        $results[] = $this->test_tax_rate_validation();
        $results[] = $this->test_sale_price_rule();
        $results[] = $this->test_url_absolute_rule();
        $results[] = $this->test_identifiers_missing();
        $results[] = $this->test_scoring_weight_override();

        $passed = array_reduce( $results, function( $ok, $t ){ return $ok && ! empty( $t['passed'] ); }, true );

        return new WP_REST_Response( [ 'passed' => (bool) $passed, 'tests' => $results ], 200 );
    }

    protected function evaluate_items( array $items, array $transport_diag = [] ) : array {
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( $this->version );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, \WPMR\PFV\Services\Rules::get_overrides( $this->version ) );
        // Use effective weights in pack
        $pack['weights'] = \WPMR\PFV\Services\Rules::effective_weights( $pack, $this->version );
        return \WPMR\PFV\Services\RulesEngine::evaluate( $items, $transport_diag, $pack, $effective );
    }

    protected function find_issue_codes( array $issues ) : array {
        return array_values( array_unique( array_map( function( $i ){ return $i['code'] ?? ''; }, $issues ) ) );
    }

    protected function has_issue( array $issues, string $code ) : bool {
        foreach ( $issues as $i ) { if ( ($i['code'] ?? '') === $code ) return true; }
        return false;
    }

    protected function test_availability_normalization() : array {
        $items = [
            [ 'id' => 'A', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ],
            [ 'id' => 'B', 'availability' => 'in-stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ],
            [ 'id' => 'C', 'availability' => 'instock',  'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ],
            [ 'id' => 'D', 'availability' => 'available','price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ],
        ];
        $issues = $this->evaluate_items( $items );
        $codes = $this->find_issue_codes( $issues );
        $passed = ! in_array( 'invalid_availability', $codes, true );
        return [ 'name' => 'Availability normalization', 'passed' => $passed, 'codes' => $codes ];
    }

    protected function test_shipping_price_invalid() : array {
        $items = [ [ 'id' => 'S1', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100), 'shipping' => [ [ 'country' => 'NL', 'price' => 'free' ] ] ] ];
        $issues = $this->evaluate_items( $items );
        $passed = $this->has_issue( $issues, 'shipping_price_invalid' );
        return [ 'name' => 'Shipping price invalid', 'passed' => $passed ];
    }

    protected function test_tax_rate_validation() : array {
        $items = [
            [ 'id' => 'T1', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100), 'tax' => [ [ 'country' => 'NL', 'rate' => '-5%' ] ] ],
            [ 'id' => 'T2', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100), 'tax' => [ [ 'country' => 'NL', 'rate' => '150%' ] ] ],
            [ 'id' => 'T3', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100), 'tax' => [ [ 'country' => 'NL', 'rate' => '19%' ] ] ],
        ];
        $issues = $this->evaluate_items( $items );
        $passed = $this->has_issue( $issues, 'tax_rate_invalid' ) && ! $this->has_issue( $issues, 'tax_missing_rate' );
        return [ 'name' => 'Tax rate validation', 'passed' => $passed ];
    }

    protected function test_sale_price_rule() : array {
        $items = [ [ 'id' => 'P1', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'sale_price' => '12.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ] ];
        $issues = $this->evaluate_items( $items );
        $passed = $this->has_issue( $issues, 'sale_price_gte_price' );
        return [ 'name' => 'Sale price less than price', 'passed' => $passed ];
    }

    protected function test_url_absolute_rule() : array {
        $items = [ [ 'id' => 'U1', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => '/relative/url', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ] ];
        $issues = $this->evaluate_items( $items );
        $passed = $this->has_issue( $issues, 'link_not_absolute' );
        return [ 'name' => 'URL absolute check', 'passed' => $passed ];
    }

    protected function test_identifiers_missing() : array {
        $items = [ [ 'id' => 'I1', 'availability' => 'in_stock', 'price' => '10.00 EUR', 'link' => 'https://ex.com', 'image_link' => 'https://ex.com/i.jpg', 'title' => 't', 'description' => str_repeat('d', 100) ] ];
        $issues = $this->evaluate_items( $items );
        $passed = $this->has_issue( $issues, 'identifiers_all_missing' );
        return [ 'name' => 'Identifiers all missing', 'passed' => $passed ];
    }

    protected function test_scoring_weight_override() : array {
        // Directly test scoring with an override weight per issue
        $pack = [ 'weights' => [ 'error' => 7, 'warning' => 3, 'advice' => 1, 'cap_per_category' => 20 ] ];
        $issues = [ [ 'severity' => 'warning', 'category' => 'text', 'weight' => 5 ] ];
        $res = \WPMR\PFV\Services\Scoring::compute( $issues, $pack );
        $passed = ( isset( $res['score'] ) && (int) $res['score'] === 95 );
        return [ 'name' => 'Scoring per-issue weight override', 'passed' => $passed, 'result' => $res ];
    }
}
