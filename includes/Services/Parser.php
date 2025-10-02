<?php
namespace WPMR\PFV\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Parser {
    /**
     * Parse an XML feed string using XMLReader with optional sampling.
     * Returns diagnostics and basic counts, plus duplicate g:id detection.
     *
     * @param string $xml
     * @param array{sample:bool,sample_size:int} $opts
     * @return array|WP_Error
     */
    public static function parse_sample( string $xml, array $opts ) {
        if ( ! class_exists( '\\XMLReader' ) ) {
            return new WP_Error( 'wpmr_pfv_no_xmlreader', __( 'XMLReader is not available on this server.', 'wpmr-product-feed-validator' ) );
        }

        $sample      = ! empty( $opts['sample'] );
        $sample_size = max( 1, intval( $opts['sample_size'] ?? 500 ) );

        $reader = new \XMLReader();
        $ok = @$reader->XML( $xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING );
        if ( ! $ok ) {
            return new WP_Error( 'wpmr_pfv_xml_invalid', __( 'XML is not well-formed or could not be read.', 'wpmr-product-feed-validator' ) );
        }

        $format = null; // rss|atom|null
        $diagnostics = [];
        $seen_ids = [];
        $duplicates = [];
        $items_scanned = 0;
        $items_total = 0; // best-effort; equals scanned for now
        $missing_id_count = 0;
        $found_item_nodes = false;
        $items = [];

        while ( $reader->read() ) {
            if ( $reader->nodeType !== \XMLReader::ELEMENT ) { continue; }
            $localName = $reader->localName;

            if ( $localName === 'item' || $localName === 'entry' ) {
                $found_item_nodes = true;
                if ( ! $format ) { $format = ( $localName === 'item' ) ? 'rss' : 'atom'; }

                // Capture current item XML and parse minimal fields
                $outer = $reader->readOuterXML();
                if ( $outer === '' ) { continue; }

                $sx = @simplexml_load_string( $outer );
                if ( $sx === false ) {
                    $diagnostics[] = [ 'severity' => 'error', 'code' => 'item_xml_invalid', 'message' => __( 'Item XML not well-formed.', 'wpmr-product-feed-validator' ) ];
                } else {
                    // Attempt to extract g:id
                    $gId = null;
                    $namespaces = $sx->getNamespaces( true );
                    if ( isset( $namespaces['g'] ) ) {
                        $g = $sx->children( $namespaces['g'] );
                        if ( isset( $g->id ) ) {
                            $gId = trim( (string) $g->id );
                        }
                        // Extract key Google fields
                        $item = [
                            'id'           => $gId,
                            'title'        => isset( $g->title ) ? (string) $g->title : '',
                            'description'  => isset( $g->description ) ? (string) $g->description : '',
                            'link'         => isset( $g->link ) ? (string) $g->link : '',
                            'image_link'   => isset( $g->image_link ) ? (string) $g->image_link : '',
                            'availability' => isset( $g->availability ) ? (string) $g->availability : '',
                            'price'        => isset( $g->price ) ? (string) $g->price : '',
                            'sale_price'   => isset( $g->sale_price ) ? (string) $g->sale_price : '',
                            'gtin'         => isset( $g->gtin ) ? (string) $g->gtin : '',
                            'brand'        => isset( $g->brand ) ? (string) $g->brand : '',
                            'mpn'          => isset( $g->mpn ) ? (string) $g->mpn : '',
                            'google_product_category' => isset( $g->google_product_category ) ? (string) $g->google_product_category : '',
                            'product_type'            => isset( $g->product_type ) ? (string) $g->product_type : '',
                            'item_group_id'           => isset( $g->item_group_id ) ? (string) $g->item_group_id : '',
                            'color'                   => isset( $g->color ) ? (string) $g->color : '',
                            'size'                    => isset( $g->size ) ? (string) $g->size : '',
                            'adult'                   => isset( $g->adult ) ? (string) $g->adult : '',
                            'shipping'                => [],
                            'tax'                     => [],
                        ];
                        // shipping nodes (can be multiple)
                        if ( isset( $g->shipping ) ) {
                            foreach ( $g->shipping as $sh ) {
                                $item['shipping'][] = [
                                    'country' => isset( $sh->country ) ? (string) $sh->country : '',
                                    'region'  => isset( $sh->region ) ? (string) $sh->region : '',
                                    'service' => isset( $sh->service ) ? (string) $sh->service : '',
                                    'price'   => isset( $sh->price ) ? (string) $sh->price : '',
                                ];
                            }
                        }
                        // tax nodes (can be multiple)
                        if ( isset( $g->tax ) ) {
                            foreach ( $g->tax as $tx ) {
                                $item['tax'][] = [
                                    'country' => isset( $tx->country ) ? (string) $tx->country : '',
                                    'region'  => isset( $tx->region ) ? (string) $tx->region : '',
                                    'rate'    => isset( $tx->rate ) ? (string) $tx->rate : '',
                                ];
                            }
                        }
                    } else {
                        // Fallback minimal extraction when no g: namespace available
                        $item = [
                            'id'           => $gId,
                            'title'        => (string) ( $sx->title ?? '' ),
                            'description'  => (string) ( $sx->description ?? '' ),
                            'link'         => (string) ( $sx->link ?? '' ),
                            'image_link'   => '',
                            'availability' => '',
                            'price'        => '',
                            'sale_price'   => '',
                            'gtin'         => '',
                            'brand'        => '',
                            'mpn'          => '',
                            'google_product_category' => '',
                            'product_type'            => '',
                            'item_group_id'           => '',
                            'color'                   => '',
                            'size'                    => '',
                            'adult'                   => '',
                            'shipping'                => [],
                            'tax'                     => [],
                        ];
                    }
                    if ( $gId === null || $gId === '' ) {
                        $missing_id_count++;
                        $item['is_duplicate_id'] = false;
                    } else {
                        if ( isset( $seen_ids[ $gId ] ) ) {
                            $duplicates[] = $gId;
                            $item['is_duplicate_id'] = true;
                        }
                        $seen_ids[ $gId ] = true;
                        if ( empty( $item['is_duplicate_id'] ) ) { $item['is_duplicate_id'] = false; }
                    }
                    $items[] = $item;
                }

                $items_scanned++;
                $items_total++;
                if ( $sample && $items_scanned >= $sample_size ) {
                    break;
                }

                // Note: readOuterXML moves the cursor; continue loop
            }
        }

        if ( ! $found_item_nodes ) {
            $diagnostics[] = [ 'severity' => 'error', 'code' => 'no_items', 'message' => __( 'Neither RSS <item> nor Atom <entry> elements were found.', 'wpmr-product-feed-validator' ) ];
        }

        $reader->close();

        return [
            'ok' => empty( array_filter( $diagnostics, fn($d) => $d['severity'] === 'error' ) ),
            'format' => $format,
            'items_scanned' => $items_scanned,
            'items_total' => $items_total,
            'missing_id_count' => $missing_id_count,
            'duplicates' => array_values( array_unique( $duplicates ) ),
            'items' => $items,
            'diagnostics' => $diagnostics,
        ];
    }
}
