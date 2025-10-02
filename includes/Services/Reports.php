<?php
namespace WPMR\PFV\Services;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Reports {
    public static function table_reports(): string {
        global $wpdb; return $wpdb->prefix . 'feed_validator_reports';
    }

    public static function table_overrides(): string {
        global $wpdb; return $wpdb->prefix . 'feed_validator_rule_overrides';
    }

    public static function install_schema(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $reports = self::table_reports();
        $sql1 = "CREATE TABLE {$reports} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL,
            url TEXT NOT NULL,
            url_host VARCHAR(191) NULL,
            email_sha256 CHAR(64) NULL,
            email_domain VARCHAR(191) NULL,
            rule_version VARCHAR(50) NOT NULL,
            items_scanned INT UNSIGNED NOT NULL DEFAULT 0,
            score INT UNSIGNED NOT NULL DEFAULT 0,
            totals_json LONGTEXT NULL,
            issues_json LONGTEXT NULL,
            transport_json LONGTEXT NULL,
            format VARCHAR(32) NULL,
            missing_id_count INT UNSIGNED NOT NULL DEFAULT 0,
            duplicates_json LONGTEXT NULL,
            public_key VARCHAR(64) NULL,
            expires_at DATETIME NULL,
            override_count INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY public_key (public_key),
            KEY created_at (created_at),
            KEY url_host (url_host),
            KEY email_domain (email_domain),
            KEY rule_version (rule_version)
        ) {$charset_collate};";

        $overrides = self::table_overrides();
        $sql2 = "CREATE TABLE {$overrides} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL,
            rule_version VARCHAR(50) NOT NULL,
            payload_json LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY rule_version (rule_version)
        ) {$charset_collate};";

        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }

    protected static function ensure_tables(): void {
        global $wpdb;
        $need_install = false;
        $t1 = self::table_reports();
        $t2 = self::table_overrides();
        $exists1 = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t1 ) );
        $exists2 = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t2 ) );
        if ( $exists1 !== $t1 || $exists2 !== $t2 ) { $need_install = true; }
        if ( $need_install ) { self::install_schema(); }
    }

    public static function create_report( array $args ): array {
        global $wpdb;
        // Ensure tables exist even if plugin was not re-activated after upgrade
        self::ensure_tables();
        $table = self::table_reports();
        $now = current_time( 'mysql' );

        $url = (string) ( $args['url'] ?? '' );
        $host = '';
        $parts = wp_parse_url( $url );
        if ( ! empty( $parts['host'] ) ) { $host = $parts['host']; }

        $email = (string) ( $args['email'] ?? '' );
        $email_sha256 = $email !== '' ? hash( 'sha256', strtolower( trim( $email ) ) ) : null;
        $email_domain = '';
        if ( $email !== '' && strpos( $email, '@' ) !== false ) {
            $email_domain = substr( $email, strrpos( $email, '@' ) + 1 );
        }

        $rule_version = (string) ( $args['rule_version'] ?? '' );
        $items_scanned = (int) ( $args['items_scanned'] ?? 0 );
        $score = (int) ( $args['score'] ?? 0 );
        $totals_json = wp_json_encode( $args['totals'] ?? [] );
        $issues_json = wp_json_encode( $args['issues'] ?? [] );
        $transport_json = wp_json_encode( $args['transport'] ?? [] );
        $format = (string) ( $args['format'] ?? '' );
        $missing_id_count = (int) ( $args['missing_id_count'] ?? 0 );
        $duplicates_json = wp_json_encode( $args['duplicates'] ?? [] );
        $override_count = (int) ( $args['override_count'] ?? 0 );

        // Public key when shareable, optional TTL
        $opts = \WPMR\PFV\Admin\Settings::get_options();
        $public_key = null; $expires_at = null;
        if ( ! empty( $opts['shareable_reports'] ) ) {
            $public_key = self::generate_public_key();
            $ttl_days = (int) ( $opts['report_ttl_days'] ?? 0 );
            if ( $ttl_days > 0 ) {
                $expires_at = gmdate( 'Y-m-d H:i:s', time() + ( $ttl_days * DAY_IN_SECONDS ) );
            }
        }

        $ok = $wpdb->insert( $table, [
            'created_at'       => $now,
            'url'              => $url,
            'url_host'         => $host,
            'email_sha256'     => $email_sha256,
            'email_domain'     => $email_domain,
            'rule_version'     => $rule_version,
            'items_scanned'    => $items_scanned,
            'score'            => $score,
            'totals_json'      => $totals_json,
            'issues_json'      => $issues_json,
            'transport_json'   => $transport_json,
            'format'           => $format,
            'missing_id_count' => $missing_id_count,
            'duplicates_json'  => $duplicates_json,
            'public_key'       => $public_key,
            'expires_at'       => $expires_at,
            'override_count'   => $override_count,
        ], [
            '%s','%s','%s','%s','%s','%s','%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%d'
        ] );
        if ( ! $ok && ! empty( $wpdb->last_error ) ) {
            error_log( '[WPMR PFV] Failed to insert report: ' . $wpdb->last_error );
        }
        $id = (int) $wpdb->insert_id;
        return [ 'id' => $id, 'public_key' => $public_key ];
    }

    public static function fetch_public_report( string $public_key ) {
        global $wpdb; $table = self::table_reports();
        if ( $public_key === '' ) { return null; }
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_key = %s", $public_key ), ARRAY_A );
        if ( ! $row ) { return null; }
        if ( ! empty( $row['expires_at'] ) && strtotime( $row['expires_at'] ) < time() ) { return null; }
        // Sanitize for public exposure — no email fields returned
        return [
            'created_at'     => $row['created_at'],
            'url'            => $row['url'],
            'rule_version'   => $row['rule_version'],
            'items_scanned'  => (int) $row['items_scanned'],
            'score'          => (int) $row['score'],
            'totals'         => json_decode( (string) $row['totals_json'], true ) ?: [],
            'issues'         => json_decode( (string) $row['issues_json'], true ) ?: [],
            'transport'      => json_decode( (string) $row['transport_json'], true ) ?: [],
            'format'         => $row['format'],
            'missing_id_count'=> (int) $row['missing_id_count'],
            'duplicates'     => json_decode( (string) $row['duplicates_json'], true ) ?: [],
        ];
    }

    protected static function generate_public_key(): string {
        // Generate a 32-char hex string using secure randomness with fallbacks
        try {
            if ( function_exists( 'random_bytes' ) ) {
                $bytes = random_bytes( 16 );
            } elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
                $bytes = openssl_random_pseudo_bytes( 16 );
            } else {
                // Last resort: derive bytes from high-entropy strings
                $seed = uniqid( '', true ) . '|' . (string) microtime( true ) . '|' . (string) mt_rand();
                $bytes = md5( $seed, true ); // 16 raw bytes
            }
            return bin2hex( $bytes );
        } catch ( \Throwable $e ) {
            // Absolute fallback
            $seed = uniqid( '', true ) . '|' . (string) microtime( true ) . '|' . (string) mt_rand();
            return md5( $seed );
        }
    }

    /**
     * Purge PII fields (email_sha256, email_domain) older than $days.
     * Keeps anonymized aggregates by only nulling PII fields, not deleting rows.
     * Returns affected rows count.
     */
    public static function purge_pii( int $days = 180 ): int {
        global $wpdb; $table = self::table_reports();
        if ( $days <= 0 ) { return 0; }
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        // Only update rows that still have PII
        $sql = $wpdb->prepare( "UPDATE {$table} SET email_sha256 = NULL, email_domain = NULL WHERE created_at < %s AND (email_sha256 IS NOT NULL OR email_domain IS NOT NULL)", $cutoff );
        $wpdb->query( $sql );
        return (int) $wpdb->rows_affected;
    }
}
