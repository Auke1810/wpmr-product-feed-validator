<?php
namespace WPMR\PFV\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class RuleManager {
    public static function register_menu() {
        add_options_page(
            __( 'Feed Validator Rules', 'wpmr-product-feed-validator' ),
            __( 'Feed Validator Rules', 'wpmr-product-feed-validator' ),
            'manage_options',
            'wpmr-pfv-rules',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( 'google-v2025-09' );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( 'google-v2025-09' );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        $weights_base = $pack['weights'] ?? [ 'error'=>7, 'warning'=>3, 'advice'=>1, 'cap_per_category'=>20 ];
        $weights_eff  = \WPMR\PFV\Services\Rules::effective_weights( $pack, 'google-v2025-09' );
        ?>
        <div class="wrap wpmr-pfv-rules">
            <h1><?php echo esc_html__( 'Feed Validator Rules', 'wpmr-product-feed-validator' ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Adjust global weights and per-rule severity. Changes apply immediately.', 'wpmr-product-feed-validator' ); ?>
            </p>
            <?php self::render_inner(); ?>
        </div>
        <?php
    }

    /**
     * Render the inner content of the Rules manager without outer wrap/h1.
     * Allows embedding inside Settings tabs.
     */
    public static function render_inner() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $pack = \WPMR\PFV\Services\Rules::load_rulepack( 'google-v2025-09' );
        $overrides = \WPMR\PFV\Services\Rules::get_overrides( 'google-v2025-09' );
        $effective = \WPMR\PFV\Services\Rules::effective_rules( $pack, $overrides );
        $weights_base = $pack['weights'] ?? [ 'error'=>7, 'warning'=>3, 'advice'=>1, 'cap_per_category'=>20 ];
        $weights_eff  = \WPMR\PFV\Services\Rules::effective_weights( $pack, 'google-v2025-09' );
        ?>
            <p>
                <strong><?php esc_html_e( 'Rulepack', 'wpmr-product-feed-validator' ); ?>:</strong>
                <code><?php echo esc_html( $pack['id'] ?? 'google-v2025-09' ); ?></code>
                &middot;
                <strong><?php esc_html_e( 'Effective Weights', 'wpmr-product-feed-validator' ); ?>:</strong>
                <?php printf( 'E=%d, W=%d, A=%d, cap=%d', (int) $weights_eff['error'], (int) $weights_eff['warning'], (int) $weights_eff['advice'], (int) $weights_eff['cap_per_category'] ); ?>
            </p>

            <hr />
            <h2><?php esc_html_e( 'Global Weights', 'wpmr-product-feed-validator' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Adjust global penalties and category cap. Leave blank to keep current.', 'wpmr-product-feed-validator' ); ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wpmr-pfv-weight-error"><?php esc_html_e( 'Error weight', 'wpmr-product-feed-validator' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="wpmr-pfv-weight-error" class="small-text" value="<?php echo (int) $weights_eff['error']; ?>" />
                            <p class="description"><?php printf( esc_html__( 'Base: %d', 'wpmr-product-feed-validator' ), (int) $weights_base['error'] ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpmr-pfv-weight-warning"><?php esc_html_e( 'Warning weight', 'wpmr-product-feed-validator' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="wpmr-pfv-weight-warning" class="small-text" value="<?php echo (int) $weights_eff['warning']; ?>" />
                            <p class="description"><?php printf( esc_html__( 'Base: %d', 'wpmr-product-feed-validator' ), (int) $weights_base['warning'] ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpmr-pfv-weight-advice"><?php esc_html_e( 'Advice weight', 'wpmr-product-feed-validator' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="wpmr-pfv-weight-advice" class="small-text" value="<?php echo (int) $weights_eff['advice']; ?>" />
                            <p class="description"><?php printf( esc_html__( 'Base: %d', 'wpmr-product-feed-validator' ), (int) $weights_base['advice'] ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpmr-pfv-weight-cap"><?php esc_html_e( 'Category cap', 'wpmr-product-feed-validator' ); ?></label></th>
                        <td>
                            <input type="number" min="0" id="wpmr-pfv-weight-cap" class="small-text" value="<?php echo (int) $weights_eff['cap_per_category']; ?>" />
                            <p class="description"><?php printf( esc_html__( 'Base: %d', 'wpmr-product-feed-validator' ), (int) $weights_base['cap_per_category'] ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
                <button type="button" class="button button-primary" id="wpmr-pfv-save-weights"><?php esc_html_e( 'Save Weights', 'wpmr-product-feed-validator' ); ?></button>
                <span id="wpmr-pfv-weights-status" style="margin-left:8px;"></span>
            </p>

            <p>
                <label for="wpmr-pfv-rules-search"><?php esc_html_e( 'Search', 'wpmr-product-feed-validator' ); ?>:</label>
                <input type="search" id="wpmr-pfv-rules-search" class="regular-text" placeholder="code/category/message" />
            </p>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Code', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Severity', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Enabled', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Weight override', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Docs', 'wpmr-product-feed-validator' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wpmr-product-feed-validator' ); ?></th>
                    </tr>
                </thead>
                <tbody id="wpmr-pfv-rules-body">
                <?php foreach ( $effective as $code => $r ): ?>
                    <tr data-code="<?php echo esc_attr( $code ); ?>" data-category="<?php echo esc_attr( $r['category'] ); ?>" data-message="<?php echo esc_attr( $r['message'] ); ?>">
                        <td><code><?php echo esc_html( $code ); ?></code></td>
                        <td><?php echo esc_html( ucfirst( str_replace('_',' ', $r['category']) ) ); ?></td>
                        <td>
                            <select class="wpmr-pfv-rule-severity">
                                <?php foreach ( ['error','warning','advice'] as $sev ): ?>
                                    <option value="<?php echo esc_attr( $sev ); ?>" <?php selected( $r['severity'], $sev ); ?>><?php echo esc_html( ucfirst( $sev ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <label>
                                <input type="checkbox" class="wpmr-pfv-rule-enabled" <?php checked( ! empty( $r['enabled'] ) ); ?> />
                            </label>
                        </td>
                        <td>
                            <input type="number" class="small-text wpmr-pfv-rule-weight" min="0" placeholder="—" value="<?php echo isset( $r['weight_override'] ) && $r['weight_override'] !== null ? (int) $r['weight_override'] : ''; ?>" />
                        </td>
                        <td>
                            <?php if ( ! empty( $r['docs_url'] ) ) : ?>
                                <a href="<?php echo esc_url( $r['docs_url'] ); ?>" target="_blank" rel="noopener">Docs</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-secondary wpmr-pfv-rule-reset"><?php esc_html_e( 'Reset', 'wpmr-product-feed-validator' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <hr />
            <h2><?php esc_html_e( 'Import / Export', 'wpmr-product-feed-validator' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Export or import rule overrides and global weights as JSON. Restore defaults clears all overrides.', 'wpmr-product-feed-validator' ); ?></p>
            <p>
                <button type="button" class="button" id="wpmr-pfv-export-json"><?php esc_html_e( 'Export JSON', 'wpmr-product-feed-validator' ); ?></button>
                <label class="button" for="wpmr-pfv-import-file"><?php esc_html_e( 'Import JSON…', 'wpmr-product-feed-validator' ); ?></label>
                <input type="file" id="wpmr-pfv-import-file" accept="application/json,.json" style="display:none;" />
                <button type="button" class="button button-secondary" id="wpmr-pfv-restore-defaults"><?php esc_html_e( 'Restore defaults', 'wpmr-product-feed-validator' ); ?></button>
                <span id="wpmr-pfv-importexport-status" style="margin-left:8px;"></span>
            </p>

            <hr />
            <h2><?php esc_html_e( 'Unit Tests (Admin only)', 'wpmr-product-feed-validator' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Run a quick rule/scoring test suite to verify core validations. Results are shown below.', 'wpmr-product-feed-validator' ); ?></p>
            <p>
                <button type="button" class="button" id="wpmr-pfv-run-tests"><?php esc_html_e( 'Run Tests', 'wpmr-product-feed-validator' ); ?></button>
                <span id="wpmr-pfv-tests-status" style="margin-left:8px;"></span>
            </p>
            <pre id="wpmr-pfv-tests-output" style="max-height:240px; overflow:auto; background:#f6f7f7; padding:8px; border:1px solid #ccd0d4;"></pre>
        <?php
    }
}
