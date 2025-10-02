<?php
namespace WPMR\PFV\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Scoring {
    /**
     * Compute a score from 0..100 given issues and pack weights + category cap.
     *
     * @param array $issues Each: {severity: 'error'|'warning'|'advice', category: string}
     * @param array $pack   Rule pack with weights {error,warning,advice,cap_per_category}
     * @return array {score:int, totals: {errors:int,warnings:int,advice:int}, penalties_by_category: array}
     */
    public static function compute( array $issues, array $pack ): array {
        $weights = is_array( $pack['weights'] ?? null ) ? $pack['weights'] : [ 'error' => 7, 'warning' => 3, 'advice' => 1, 'cap_per_category' => 20 ];
        $cap_per_category = (int) ( $weights['cap_per_category'] ?? 20 );

        $totals = [ 'errors' => 0, 'warnings' => 0, 'advice' => 0 ];
        $penalties_by_category = [];

        foreach ( $issues as $is ) {
            $sev = $is['severity'] ?? 'warning';
            $cat = $is['category'] ?? 'general';
            if ( $sev === 'error' ) { $totals['errors']++; }
            elseif ( $sev === 'warning' ) { $totals['warnings']++; }
            else { $totals['advice']++; }

            // Prefer per-issue weight override when provided
            if ( isset( $is['weight'] ) && is_numeric( $is['weight'] ) && (int) $is['weight'] > 0 ) {
                $w = (int) $is['weight'];
            } else {
                $w = (int) ( $weights[ $sev ] ?? 0 );
            }
            if ( $w <= 0 ) { continue; }
            if ( ! isset( $penalties_by_category[ $cat ] ) ) { $penalties_by_category[ $cat ] = 0; }
            // Apply cap per category
            if ( $penalties_by_category[ $cat ] < $cap_per_category ) {
                $penalties_by_category[ $cat ] = min( $cap_per_category, $penalties_by_category[ $cat ] + $w );
            }
        }

        $base = 100;
        $total_penalty = 0;
        foreach ( $penalties_by_category as $sum ) { $total_penalty += (int) $sum; }
        $score = max( 0, $base - $total_penalty );

        return [
            'score' => $score,
            'totals' => $totals,
            'penalties_by_category' => $penalties_by_category,
        ];
    }
}
