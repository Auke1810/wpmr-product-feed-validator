<?php
namespace WPMR\PFV\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Rules {
    const OPTION_OVERRIDES = 'wpmr_pfv_rule_overrides';
    const OPTION_WEIGHTS   = 'wpmr_pfv_rule_weights';

    public static function load_rulepack( string $version = 'google-v2025-09' ): array {
        $path = trailingslashit( \WPMR_PFV_PLUGIN_DIR ) . 'rules/' . $version . '.json';
        if ( ! file_exists( $path ) ) { return [ 'id' => $version, 'weights' => [ 'error'=>7,'warning'=>3,'advice'=>1,'cap_per_category'=>20 ], 'rules' => [] ]; }
        $json = file_get_contents( $path );
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) { $data = [ 'id' => $version, 'weights' => [ 'error'=>7,'warning'=>3,'advice'=>1,'cap_per_category'=>20 ], 'rules' => [] ]; }
        /** Allow customization */
        $data = apply_filters( 'gpfv_rules', $data );
        return $data;
    }

    public static function get_overrides( string $version = 'google-v2025-09' ): array {
        $all = get_option( self::OPTION_OVERRIDES, [] );
        return is_array( $all ) && isset( $all[ $version ] ) && is_array( $all[ $version ] ) ? $all[ $version ] : [];
    }

    public static function set_overrides( string $version, array $overrides ): void {
        $all = get_option( self::OPTION_OVERRIDES, [] );
        $all[ $version ] = $overrides;
        update_option( self::OPTION_OVERRIDES, $all );
    }

    public static function delete_overrides( string $version ): void {
        $all = get_option( self::OPTION_OVERRIDES, [] );
        if ( isset( $all[ $version ] ) ) {
            unset( $all[ $version ] );
            update_option( self::OPTION_OVERRIDES, $all );
        }
    }

    public static function effective_rules( array $pack, array $overrides ): array {
        $rules = [];
        $o = $overrides;
        foreach ( (array) ( $pack['rules'] ?? [] ) as $r ) {
            $code = $r['code'];
            $e = [
                'code' => $code,
                'category' => $r['category'] ?? 'general',
                'default_severity' => $r['default_severity'] ?? 'warning',
                'message' => $r['message'] ?? '',
                'docs_url' => $r['docs_url'] ?? '',
                'can_override' => ! empty( $r['can_override'] ),
            ];
            $ov = isset( $o[ $code ] ) ? $o[ $code ] : [];
            $e['enabled'] = isset( $ov['enabled'] ) ? (int) ( ! empty( $ov['enabled'] ) ) : 1;
            $e['severity'] = isset( $ov['severity'] ) && in_array( $ov['severity'], [ 'error','warning','advice' ], true ) ? $ov['severity'] : $e['default_severity'];
            $e['weight_override'] = isset( $ov['weight_override'] ) ? (int) $ov['weight_override'] : null;
            $e['source'] = ! empty( $ov ) ? 'override' : 'default';
            $rules[ $code ] = $e;
        }
        return $rules;
    }

    /**
     * Global weights overrides (error, warning, advice, cap_per_category).
     */
    public static function get_weights_overrides( string $version = 'google-v2025-09' ): array {
        $all = get_option( self::OPTION_WEIGHTS, [] );
        return is_array( $all ) && isset( $all[ $version ] ) && is_array( $all[ $version ] ) ? $all[ $version ] : [];
    }

    public static function set_weights_overrides( string $version, array $weights ): void {
        $allowed = [ 'error', 'warning', 'advice', 'cap_per_category' ];
        $clean = [];
        foreach ( $allowed as $k ) {
            if ( isset( $weights[ $k ] ) && is_numeric( $weights[ $k ] ) ) {
                $v = max( 0, (int) $weights[ $k ] );
                $clean[ $k ] = $v;
            }
        }
        $all = get_option( self::OPTION_WEIGHTS, [] );
        $all[ $version ] = $clean;
        update_option( self::OPTION_WEIGHTS, $all );
    }

    public static function delete_weights_overrides( string $version ): void {
        $all = get_option( self::OPTION_WEIGHTS, [] );
        if ( isset( $all[ $version ] ) ) {
            unset( $all[ $version ] );
            update_option( self::OPTION_WEIGHTS, $all );
        }
    }

    public static function effective_weights( array $pack, string $version = 'google-v2025-09' ): array {
        $base = is_array( $pack['weights'] ?? null ) ? $pack['weights'] : [ 'error'=>7,'warning'=>3,'advice'=>1,'cap_per_category'=>20 ];
        $ov   = self::get_weights_overrides( $version );
        foreach ( [ 'error','warning','advice','cap_per_category' ] as $k ) {
            if ( isset( $ov[ $k ] ) && is_numeric( $ov[ $k ] ) ) {
                $base[ $k ] = (int) $ov[ $k ];
            }
        }
        return $base;
    }
}
