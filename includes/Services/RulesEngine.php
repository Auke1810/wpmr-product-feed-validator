<?php
namespace WPMR\PFV\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class RulesEngine {
    /**
     * Evaluate transport diagnostics and per-item fields against effective rules.
     * Returns list of issues (each with item_id, code, message, category, severity).
     *
     * @param array $items From Parser::parse_sample()['items']
     * @param array $transportDiagnostics From Fetcher::fetch()['diagnostics']
     * @param array $pack Rule pack
     * @param array $effective Effective rules map code=>meta from Rules::effective_rules
     */
    public static function evaluate( array $items, array $transportDiagnostics, array $pack, array $effective ): array {
        $issues = [];

        // 1) Transport diagnostics -> map directly to issues (GLOBAL scope)
        foreach ( $transportDiagnostics as $diag ) {
            $code = $diag['code'] ?? 'transport';
            $rule = $effective[ $code ] ?? null;
            $category = $rule['category'] ?? 'transport';
            $severity = $rule['severity'] ?? ( $diag['severity'] ?? 'warning' );
            $message  = $rule['message'] ?? ( $diag['message'] ?? $code );
            if ( isset( $rule['enabled'] ) && ! $rule['enabled'] ) { continue; }
            $issues[] = [
                'item_id'  => '',
                'code'     => $code,
                'message'  => $message,
                'category' => $category,
                'severity' => $severity,
                'path'     => 'transport',
                'docs_url' => (string) ( $rule['docs_url'] ?? '' ),
            ];
        }

        // 2) Per-item checks
        foreach ( $items as $idx => $it ) {
            $item_id = (string) ( $it['id'] ?? '' );
            if ( $item_id === '' ) { $item_id = '(missing:#' . ( $idx + 1 ) . ')'; }

            // Required attributes
            self::require_field( $issues, $effective, $item_id, 'missing_id', empty( $it['id'] ), 'required_attributes', 'Missing g:id' );
            self::require_field( $issues, $effective, $item_id, 'missing_title', self::is_empty( $it['title'] ?? '' ), 'required_attributes', 'Missing g:title' );
            self::require_field( $issues, $effective, $item_id, 'missing_description', self::is_empty( $it['description'] ?? '' ), 'required_attributes', 'Missing g:description' );
            self::require_field( $issues, $effective, $item_id, 'missing_link', self::is_empty( $it['link'] ?? '' ), 'required_attributes', 'Missing g:link' );
            self::require_field( $issues, $effective, $item_id, 'missing_image_link', self::is_empty( $it['image_link'] ?? '' ), 'required_attributes', 'Missing g:image_link' );

            // Availability (normalize to underscore canonical values and validate)
            $availability = self::normalize_availability( (string) ( $it['availability'] ?? '' ) );
            $valid_avail = [ 'in_stock', 'out_of_stock', 'preorder', 'backorder' ];
            if ( $availability === '' || ! in_array( $availability, $valid_avail, true ) ) {
                self::add_issue( $issues, $effective, $item_id, 'invalid_availability', 'required_attributes', 'Missing or invalid g:availability' );
            }

            // Price format
            $price = (string) ( $it['price'] ?? '' );
            if ( $price === '' || ! self::looks_like_price( $price ) ) {
                self::add_issue( $issues, $effective, $item_id, 'invalid_price', 'required_attributes', 'Missing or invalid g:price' );
            }

            // Sale price < price
            $sale_price = (string) ( $it['sale_price'] ?? '' );
            if ( $sale_price !== '' && self::looks_like_price( $sale_price ) && self::looks_like_price( $price ) ) {
                $p = self::price_to_number( $price );
                $sp = self::price_to_number( $sale_price );
                if ( $p !== null && $sp !== null && $sp >= $p ) {
                    self::add_issue( $issues, $effective, $item_id, 'sale_price_gte_price', 'price', 'g:sale_price must be less than g:price' );
                }
            }

            // Shipping rules
            $shipping = is_array( $it['shipping'] ?? null ) ? $it['shipping'] : [];
            foreach ( $shipping as $sh ) {
                $sh_price = (string) ( $sh['price'] ?? '' );
                $sh_country = (string) ( $sh['country'] ?? '' );
                if ( $sh_price === '' ) {
                    self::add_issue( $issues, $effective, $item_id, 'shipping_missing_price', 'shipping', 'Shipping node missing price.' );
                } elseif ( ! self::looks_like_price( $sh_price ) ) {
                    self::add_issue( $issues, $effective, $item_id, 'shipping_price_invalid', 'shipping', 'Shipping price must be like "9.99 USD".' );
                }
                // Optional: if region set without country
                if ( $sh_country === '' && ( (string) ( $sh['region'] ?? '' ) ) !== '' ) {
                    self::add_issue( $issues, $effective, $item_id, 'shipping_country_missing', 'shipping', 'Shipping region provided without country.' );
                }
            }

            // Tax rules
            $tax = is_array( $it['tax'] ?? null ) ? $it['tax'] : [];
            foreach ( $tax as $tx ) {
                $rate = trim( (string) ( $tx['rate'] ?? '' ) );
                $country = trim( (string) ( $tx['country'] ?? '' ) );
                if ( $rate === '' ) {
                    self::add_issue( $issues, $effective, $item_id, 'tax_missing_rate', 'tax', 'Tax node missing rate.' );
                } else {
                    $num = self::percent_to_number( $rate );
                    if ( $num === null || $num < 0 || $num > 100 ) {
                        self::add_issue( $issues, $effective, $item_id, 'tax_rate_invalid', 'tax', 'Tax rate must be 0..100 (percentage).' );
                    }
                }
                if ( $country === '' ) {
                    self::add_issue( $issues, $effective, $item_id, 'tax_country_missing', 'tax', 'Tax node missing country.' );
                }
            }

            // URLs
            $link = (string) ( $it['link'] ?? '' );
            if ( $link !== '' ) {
                $parts = wp_parse_url( $link );
                if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) || empty( $parts['host'] ) ) {
                    self::add_issue( $issues, $effective, $item_id, 'link_not_absolute', 'urls', 'g:link must be absolute http(s)' );
                }
            }
            $image_link = (string) ( $it['image_link'] ?? '' );
            if ( $image_link !== '' && stripos( $image_link, 'https://' ) !== 0 ) {
                self::add_issue( $issues, $effective, $item_id, 'image_link_not_https', 'urls', 'g:image_link should be https' );
            }

            // Text quality
            $title = (string) ( $it['title'] ?? '' );
            if ( strlen( $title ) > 150 ) {
                self::add_issue( $issues, $effective, $item_id, 'title_too_long', 'text', 'Title length > 150 chars' );
            }
            $desc = (string) ( $it['description'] ?? '' );
            if ( $desc !== '' && strlen( $desc ) < 100 ) {
                self::add_issue( $issues, $effective, $item_id, 'description_too_short', 'text', 'Description too short (< 100 chars)' );
            }

            // Identifiers
            $gtin = trim( (string) ( $it['gtin'] ?? '' ) );
            $brand = trim( (string) ( $it['brand'] ?? '' ) );
            $mpn = trim( (string) ( $it['mpn'] ?? '' ) );
            if ( $gtin === '' && $brand === '' && $mpn === '' ) {
                self::add_issue( $issues, $effective, $item_id, 'identifiers_all_missing', 'identifiers', 'Missing all of: g:gtin, g:brand, g:mpn' );
            }
            if ( $gtin !== '' && ! preg_match( '/^\d{8,14}$/', $gtin ) ) {
                self::add_issue( $issues, $effective, $item_id, 'gtin_invalid', 'identifiers', 'GTIN present but fails length/numeric check.' );
            }

            // Category & Product type
            $google_cat = trim( (string) ( $it['google_product_category'] ?? '' ) );
            if ( $google_cat === '' ) {
                self::add_issue( $issues, $effective, $item_id, 'missing_google_category', 'category', 'Missing g:google_product_category' );
            }
            $product_type = trim( (string) ( $it['product_type'] ?? '' ) );
            if ( $product_type === '' ) {
                self::add_issue( $issues, $effective, $item_id, 'missing_product_type', 'category', 'Missing g:product_type' );
            }

            // Variants / Apparel: if size or color present but no item_group_id
            $item_group_id = trim( (string) ( $it['item_group_id'] ?? '' ) );
            $has_variant_attr = ( trim( (string) ( $it['size'] ?? '' ) ) !== '' ) || ( trim( (string) ( $it['color'] ?? '' ) ) !== '' );
            if ( $has_variant_attr && $item_group_id === '' ) {
                self::add_issue( $issues, $effective, $item_id, 'variants_missing_group', 'variants', 'Variants detected without g:item_group_id' );
            }

            // Policy-adjacent: possible adult terms without flag
            $adult_flag = strtolower( trim( (string) ( $it['adult'] ?? '' ) ) );
            $haystack = strtolower( $title . ' ' . $desc . ' ' . $product_type . ' ' . $google_cat );
            $adult_terms = [ 'adult', 'xxx', 'lingerie', 'sex', 'porn', 'bdsm' ];
            $possibly_adult = false;
            foreach ( $adult_terms as $term ) {
                if ( $term !== '' && strpos( $haystack, $term ) !== false ) { $possibly_adult = true; break; }
            }
            if ( $possibly_adult && $adult_flag !== 'yes' ) {
                self::add_issue( $issues, $effective, $item_id, 'adult_without_flag', 'policy', 'Possible adult content without g:adult flag' );
            }
        }

        // Duplicate IDs
        foreach ( $items as $idx => $it ) {
            if ( ! empty( $it['is_duplicate_id'] ) && ! empty( $it['id'] ) ) {
                self::add_issue( $issues, $effective, (string) $it['id'], 'duplicate_id', 'structure', 'Duplicate g:id value.' );
            }
        }

        return $issues;
    }

    protected static function is_empty( $v ): bool {
        return trim( (string) $v ) === '';
    }

    protected static function looks_like_price( string $v ): bool {
        return (bool) preg_match( '/^\s*\d+(?:[\.,]\d{2})?\s+[A-Z]{3}\s*$/', $v );
    }

    protected static function price_to_number( string $v ) {
        if ( ! preg_match( '/(\d+[\.,]?\d*)\s+[A-Z]{3}/', $v, $m ) ) { return null; }
        $num = str_replace( ',', '.', $m[1] );
        return is_numeric( $num ) ? (float) $num : null;
    }

    protected static function percent_to_number( string $v ) {
        $v = trim( $v );
        $v = rtrim( $v, "% \t\n\r\0\x0B" );
        $v = str_replace( ',', '.', $v );
        return is_numeric( $v ) ? (float) $v : null;
    }

    protected static function normalize_availability( string $v ): string {
        $v = strtolower( trim( $v ) );
        if ( $v === '' ) { return ''; }
        // Normalize to underscore style: turn hyphens and spaces into underscores, collapse multiples
        $v = str_replace( '-', '_', $v );
        $v = preg_replace( '/\s+/', '_', $v );
        $v = preg_replace( '/_+/', '_', $v );
        $v = trim( $v, '_' );

        // Map common synonyms/variants to canonical underscore values
        $map = [
            'in_stock'     => [ 'in_stock', 'instock', 'available', 'on_stock' ],
            'out_of_stock' => [ 'out_of_stock', 'outofstock', 'oos', 'sold_out', 'soldout' ],
            'preorder'     => [ 'preorder', 'pre_order' ],
            'backorder'    => [ 'backorder', 'back_order', 'on_backorder' ],
        ];
        foreach ( $map as $canon => $list ) {
            if ( in_array( $v, $list, true ) ) { return $canon; }
        }
        return $v;
    }

    protected static function add_issue( array &$issues, array $effective, string $item_id, string $code, string $fallback_category, string $fallback_message ): void {
        $rule = $effective[ $code ] ?? null;
        if ( $rule && isset( $rule['enabled'] ) && ! $rule['enabled'] ) { return; }
        $issue = [
            'item_id'  => $item_id,
            'code'     => $code,
            'message'  => $rule['message'] ?? $fallback_message,
            'category' => $rule['category'] ?? $fallback_category,
            'severity' => $rule['severity'] ?? ( $rule['default_severity'] ?? 'warning' ),
            'docs_url' => (string) ( $rule['docs_url'] ?? '' ),
        ];
        if ( $rule && isset( $rule['weight_override'] ) && is_numeric( $rule['weight_override'] ) && (int) $rule['weight_override'] > 0 ) {
            $issue['weight'] = (int) $rule['weight_override'];
        }
        $issues[] = $issue;
    }

    protected static function require_field( array &$issues, array $effective, string $item_id, string $code, bool $condition, string $fallback_category, string $fallback_message ): void {
        if ( $condition ) {
            self::add_issue( $issues, $effective, $item_id, $code, $fallback_category, $fallback_message );
        }
    }
}
