<?php

/**
 * Handle data collection notification after 7 days
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.1.2
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 */

class Wpspeedtestpro_Data_Collection_Notice {

    public function __construct() {
        add_action('admin_notices', array($this, 'show_data_collection_notice'));
        add_action('wp_ajax_wpspeedtestpro_opt_in_data_collection', array($this, 'handle_opt_in'));
        add_action('wp_ajax_wpspeedtestpro_dismiss_data_collection_notice', array($this, 'handle_dismiss'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_notice_scripts'));
    }

    /**
     * Check if we should show the data collection notice
     */
    private function should_show_notice() {
        // Don't show if user already opted in
        if (get_option('wpspeedtestpro_allow_data_collection', false)) {
            return false;
        }

        // Don't show if user already dismissed the notice
        if (get_option('wpspeedtestpro_data_collection_notice_dismissed', false)) {
            return false;
        }

        // Check if 7 days have passed since data collection was disabled
        $disabled_time = get_option('wpspeedtestpro_data_collection_disabled_time', false);
        if (!$disabled_time) {
            return false;
        }

        // Fixed: Use proper 7-day interval
        $seven_days_ago = time() - (60 * 60);
        return $disabled_time <= $seven_days_ago;
    }

    /**
     * Show the data collection notice
     */
    public function show_data_collection_notice() {
        // Only show on plugin pages - improved sanitization
        $screen = get_current_screen();
        if (!$screen || strpos(sanitize_text_field($screen->id), 'wpspeedtestpro') === false) {
            return;
        }

        if (!$this->should_show_notice()) {
            return;
        }

        ?>
        <div id="wpspeedtestpro-data-collection-notice" class="notice notice-info is-dismissible">
            <p><strong><?php esc_html_e('WP Speedtest Pro:', 'wpspeedtestpro'); ?></strong> <?php esc_html_e('Help us improve the plugin by allowing anonymous data collection. This helps us understand performance trends and make the plugin better for everyone.', 'wpspeedtestpro'); ?></p>
            <p>
                <button type="button" class="button button-primary" id="wpspeedtestpro-opt-in-data"><?php esc_html_e('Opt In', 'wpspeedtestpro'); ?></button>
                <button type="button" class="button button-secondary" id="wpspeedtestpro-dismiss-notice"><?php esc_html_e('No Thanks', 'wpspeedtestpro'); ?></button>
            </p>
            <p><small><?php esc_html_e('Anonymous data collection helps improve plugin performance and features.', 'wpspeedtestpro'); ?></small></p>
        </div>
        <?php
    }

    /**
     * Enqueue scripts for the notice
     */
    public function enqueue_notice_scripts() {
        $screen = get_current_screen();
        if (!$screen || strpos(sanitize_text_field($screen->id), 'wpspeedtestpro') === false) {
            return;
        }

        if (!$this->should_show_notice()) {
            return;
        }

        // Enqueue the data collection notice script
        wp_enqueue_script( $this->plugin_name . '-data-collection-notice', plugin_dir_url( __FILE__ ) . '../admin/js/wpspeedtestpro-data-collection-notice.js', array( 'jquery' ), $this->version, false );

        
        // Localize script data for security
        wp_localize_script($this->plugin_name . '-data-collection-notice', 'wpspeedtestpro_notice_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_data_collection_nonce'),
            'opt_in_action' => 'wpspeedtestpro_opt_in_data_collection',
            'dismiss_action' => 'wpspeedtestpro_dismiss_data_collection_notice'
        ));
    }

    /**
     * Handle opt-in to data collection
     */
    public function handle_opt_in() {
        // Verify nonce
        if (!check_ajax_referer('wpspeedtestpro_data_collection_nonce', 'nonce', false)) {
            wp_send_json_error(esc_html__('Security check failed', 'wpspeedtestpro'));
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Unauthorized', 'wpspeedtestpro'));
            return;
        }

        update_option('wpspeedtestpro_allow_data_collection', true);
        delete_option('wpspeedtestpro_data_collection_disabled_time');
        
        wp_send_json_success(esc_html__('Data collection enabled', 'wpspeedtestpro'));
    }

    /**
     * Handle dismissing the notice
     */
    public function handle_dismiss() {
        // Verify nonce
        if (!check_ajax_referer('wpspeedtestpro_data_collection_nonce', 'nonce', false)) {
            wp_send_json_error(esc_html__('Security check failed', 'wpspeedtestpro'));
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Unauthorized', 'wpspeedtestpro'));
            return;
        }

        update_option('wpspeedtestpro_data_collection_notice_dismissed', true);
        
        wp_send_json_success(esc_html__('Notice dismissed', 'wpspeedtestpro'));
    }

    /**
     * Set the timestamp when data collection is disabled
     */
    public static function set_data_collection_disabled() {
        update_option('wpspeedtestpro_data_collection_disabled_time', time());
        delete_option('wpspeedtestpro_data_collection_notice_dismissed');
    }

    /**
     * Clear the disabled timestamp when data collection is enabled
     */
    public static function clear_data_collection_disabled() {
        delete_option('wpspeedtestpro_data_collection_disabled_time');
        delete_option('wpspeedtestpro_data_collection_notice_dismissed');
    }
}