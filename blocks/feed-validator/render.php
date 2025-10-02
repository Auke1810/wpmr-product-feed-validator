<?php
/**
 * Server-rendered block for Feed Validator.
 */

use WPMR\PFV\PublicUI\Shortcode;

if ( ! defined( 'ABSPATH' ) ) { exit; }

$attrs = is_array( $attributes ?? null ) ? $attributes : [];
$sample = isset( $attrs['sample'] ) ? (bool) $attrs['sample'] : true;

echo Shortcode::render( [ 'sample' => $sample ? 'true' : 'false' ] );
