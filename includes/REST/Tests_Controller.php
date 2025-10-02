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
        $results[] = $this->test_rate_limiting();
        $results[] = $this->test_load_performance();
        $results[] = $this->test_gmc_correctness();

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

    protected function test_rate_limiting() : array {
        // Test rate limiting functionality
        $email = 'test-rate-limit-' . time() . '@example.com';
        $ip = '127.0.0.1';

        // Clean up any existing transients for this test
        global $wpdb;
        $date = gmdate('Ymd');
        $ip_key = 'wpmr_pfv_rl_ip_' . $date . '_' . md5($ip);
        $email_key = 'wpmr_pfv_rl_em_' . $date . '_' . hash('sha256', strtolower($email));
        delete_transient($ip_key);
        delete_transient($email_key);

        // Test initial request should pass
        $result1 = \WPMR\PFV\Services\Abuse::enforce_rate_limits($email, $ip);
        $passed = !is_wp_error($result1);

        if ($passed) {
            // Test second request should still pass (assuming default limits are reasonable)
            $result2 = \WPMR\PFV\Services\Abuse::enforce_rate_limits($email, $ip);
            $passed = $passed && !is_wp_error($result2);
        }

        // Test blocking
        $blocked = \WPMR\PFV\Services\Abuse::is_blocked($email, $ip);
        $passed = $passed && !$blocked;

        // Clean up
        delete_transient($ip_key);
        delete_transient($email_key);

        return [
            'name' => 'Rate limiting basic functionality',
            'passed' => $passed,
            'details' => [
                'email' => $email,
                'ip' => $ip,
                'first_request_ok' => !is_wp_error($result1),
                'second_request_ok' => !is_wp_error($result2 ?? null),
                'not_blocked' => !$blocked
            ]
        ];
    }

    protected function test_load_performance() : array {
        // Test parsing performance with various feed sizes
        $sizes = [500, 2000, 5000, 10000]; // Items to test
        $results = [];

        foreach ($sizes as $size) {
            $xml = $this->generate_large_feed_xml($size);
            $start_time = microtime(true);

            $parse_result = \WPMR\PFV\Services\Parser::parse_sample($xml, [
                'sample' => true,
                'sample_size' => min(500, $size) // Sample first 500 or all if smaller
            ]);

            $end_time = microtime(true);
            $duration = $end_time - $start_time;

            $results[] = [
                'size' => $size,
                'duration' => round($duration, 3),
                'items_scanned' => is_array($parse_result) ? ($parse_result['items_scanned'] ?? 0) : 0,
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ];
        }

        // Check performance requirements: <30s for typical feeds (500 items)
        $typical_duration = $results[0]['duration'] ?? 999;
        $passed = $typical_duration < 30;

        return [
            'name' => 'Load performance testing',
            'passed' => $passed,
            'details' => [
                'requirement' => 'Typical feeds (<500 items) should parse in <30 seconds',
                'results' => $results,
                'typical_feed_duration' => $typical_duration . 's',
                'requirement_met' => $passed
            ]
        ];
    }

    protected function generate_large_feed_xml(int $num_items) : string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>Test Feed</title>' . "\n";
        $xml .= '<description>Test feed for performance testing</description>' . "\n";
        $xml .= '<link>https://example.com</link>' . "\n";

        for ($i = 1; $i <= $num_items; $i++) {
            $id = 'TEST' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $xml .= '<item>' . "\n";
            $xml .= '<g:id>' . $id . '</g:id>' . "\n";
            $xml .= '<g:title>Test Product ' . $i . '</g:title>' . "\n";
            $xml .= '<g:description>This is a test product description for item ' . $i . ' with some additional text to make it more realistic.</g:description>' . "\n";
            $xml .= '<g:link>https://example.com/product/' . $i . '</g:link>' . "\n";
            $xml .= '<g:image_link>https://example.com/images/product' . $i . '.jpg</g:image_link>' . "\n";
            $xml .= '<g:availability>in_stock</g:availability>' . "\n";
            $xml .= '<g:price>' . (10 + ($i % 100)) . '.99 EUR</g:price>' . "\n";
            $xml .= '<g:google_product_category>Electronics &gt; Computers</g:google_product_category>' . "\n";
            $xml .= '<g:brand>TestBrand</g:brand>' . "\n";
            $xml .= '<g:gtin>123456789012' . str_pad($i, 3, '0', STR_PAD_LEFT) . '</g:gtin>' . "\n";
            $xml .= '<g:shipping>' . "\n";
            $xml .= '<g:country>NL</g:country>' . "\n";
            $xml .= '<g:service>Standard</g:service>' . "\n";
            $xml .= '<g:price>5.95 EUR</g:price>' . "\n";
            $xml .= '</g:shipping>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>' . "\n";

        return $xml;
    }

    protected function test_gmc_correctness() : array {
        // Test against known Google Merchant Center validation scenarios
        $test_cases = [
            [
                'name' => 'Valid product with all required fields',
                'xml' => $this->generate_valid_product_xml(),
                'expected_issues' => 0,
                'expected_score' => 100
            ],
            [
                'name' => 'Product with sale price higher than regular price',
                'xml' => $this->generate_invalid_sale_price_xml(),
                'expected_issues' => 1,
                'expected_issue_code' => 'sale_price_gte_price'
            ],
            [
                'name' => 'Product with relative URLs',
                'xml' => $this->generate_relative_url_xml(),
                'expected_issues' => 1,
                'expected_issue_code' => 'link_not_absolute'
            ],
            [
                'name' => 'Product with invalid shipping price',
                'xml' => $this->generate_invalid_shipping_xml(),
                'expected_issues' => 1,
                'expected_issue_code' => 'shipping_price_invalid'
            ],
            [
                'name' => 'Product with duplicate IDs',
                'xml' => $this->generate_duplicate_id_xml(),
                'expected_issues' => 1,
                'expected_issue_code' => 'duplicate_product_ids'
            ]
        ];

        $results = [];
        $all_passed = true;

        foreach ($test_cases as $case) {
            // Parse the XML
            $parse_result = \WPMR\PFV\Services\Parser::parse_sample($case['xml'], [
                'sample' => false,
                'sample_size' => 500
            ]);

            if (is_wp_error($parse_result)) {
                $results[] = [
                    'case' => $case['name'],
                    'passed' => false,
                    'error' => $parse_result->get_error_message()
                ];
                $all_passed = false;
                continue;
            }

            $items = $parse_result['items'] ?? [];
            $transport_diag = []; // Empty for test

            // Evaluate with rules engine
            $issues = $this->evaluate_items($items, $transport_diag);
            $pack = \WPMR\PFV\Services\Rules::load_rulepack($this->version);
            $pack['weights'] = \WPMR\PFV\Services\Rules::effective_weights($pack, $this->version);
            $score_data = \WPMR\PFV\Services\Scoring::compute($issues, $pack);

            $actual_issues = count($issues);
            $actual_score = $score_data['score'] ?? 0;

            // Check expectations
            $issues_correct = $actual_issues >= $case['expected_issues'];
            $score_correct = abs($actual_score - $case['expected_score']) <= 10; // Allow some tolerance

            $case_passed = $issues_correct && $score_correct;

            if (isset($case['expected_issue_code'])) {
                $has_expected_issue = $this->has_issue($issues, $case['expected_issue_code']);
                $case_passed = $case_passed && $has_expected_issue;
            }

            $results[] = [
                'case' => $case['name'],
                'passed' => $case_passed,
                'issues_found' => $actual_issues,
                'expected_issues' => $case['expected_issues'],
                'score' => $actual_score,
                'expected_score' => $case['expected_score'],
                'issues_correct' => $issues_correct,
                'score_correct' => $score_correct
            ];

            if (!$case_passed) {
                $all_passed = false;
            }
        }

        return [
            'name' => 'Google Merchant Center correctness testing',
            'passed' => $all_passed,
            'details' => [
                'test_cases_run' => count($test_cases),
                'results' => $results
            ]
        ];
    }

    protected function generate_valid_product_xml() : string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Test Feed</title>
<description>Valid product test</description>
<link>https://example.com</link>
<item>
<g:id>VALID001</g:id>
<g:title>Valid Test Product</g:title>
<g:description>A valid test product description</g:description>
<g:link>https://example.com/product/valid001</g:link>
<g:image_link>https://example.com/images/valid001.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>29.99 EUR</g:price>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
<g:brand>TestBrand</g:brand>
<g:gtin>123456789012345</g:gtin>
<g:shipping>
<g:country>NL</g:country>
<g:service>Standard</g:service>
<g:price>4.95 EUR</g:price>
</g:shipping>
</item>
</channel>
</rss>';
    }

    protected function generate_invalid_sale_price_xml() : string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Test Feed</title>
<description>Invalid sale price test</description>
<link>https://example.com</link>
<item>
<g:id>INVALID001</g:id>
<g:title>Invalid Sale Price Product</g:title>
<g:description>Test product with sale price higher than regular price</g:description>
<g:link>https://example.com/product/invalid001</g:link>
<g:image_link>https://example.com/images/invalid001.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>19.99 EUR</g:price>
<g:sale_price>25.99 EUR</g:sale_price>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
<g:brand>TestBrand</g:brand>
</item>
</channel>
</rss>';
    }

    protected function generate_relative_url_xml() : string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Test Feed</title>
<description>Relative URL test</description>
<link>https://example.com</link>
<item>
<g:id>RELATIVE001</g:id>
<g:title>Relative URL Product</g:title>
<g:description>Test product with relative URL</g:description>
<g:link>/product/relative001</g:link>
<g:image_link>https://example.com/images/relative001.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>9.99 EUR</g:price>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
</item>
</channel>
</rss>';
    }

    protected function generate_invalid_shipping_xml() : string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Test Feed</title>
<description>Invalid shipping test</description>
<link>https://example.com</link>
<item>
<g:id>SHIPPING001</g:id>
<g:title>Invalid Shipping Product</g:title>
<g:description>Test product with invalid shipping price</g:description>
<g:link>https://example.com/product/shipping001</g:link>
<g:image_link>https://example.com/images/shipping001.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>49.99 EUR</g:price>
<g:shipping>
<g:country>NL</g:country>
<g:service>Standard</g:service>
<g:price>free</g:price>
</g:shipping>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
</item>
</channel>
</rss>';
    }

    protected function generate_duplicate_id_xml() : string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Test Feed</title>
<description>Duplicate ID test</description>
<link>https://example.com</link>
<item>
<g:id>DUPLICATE001</g:id>
<g:title>First Product</g:title>
<g:description>First product with duplicate ID</g:description>
<g:link>https://example.com/product/first</g:link>
<g:image_link>https://example.com/images/first.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>9.99 EUR</g:price>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
</item>
<item>
<g:id>DUPLICATE001</g:id>
<g:title>Second Product</g:title>
<g:description>Second product with same ID</g:description>
<g:link>https://example.com/product/second</g:link>
<g:image_link>https://example.com/images/second.jpg</g:image_link>
<g:availability>in_stock</g:availability>
<g:price>19.99 EUR</g:price>
<g:google_product_category>Electronics &gt; Computers</g:google_product_category>
</item>
</channel>
</rss>';
    }
}
