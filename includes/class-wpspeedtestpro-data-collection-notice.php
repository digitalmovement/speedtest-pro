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

        //$seven_days_ago = time() - (7 * 24 * 60 * 60);
        $seven_days_ago = time() - (60 * 60);
        return $disabled_time <= $seven_days_ago;
    }

    /**
     * Show the data collection notice
     */
    public function show_data_collection_notice() {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpspeedtestpro') === false) {
            return;
        }

        if (!$this->should_show_notice()) {
            return;
        }

        ?>
        <div id="wpspeedtestpro-data-collection-notice" class="notice notice-info is-dismissible">
            <p><strong>WP Speedtest Pro:</strong> Help us improve the plugin by allowing anonymous data collection. This helps us understand performance trends and make the plugin better for everyone.</p>
            <p>
                <button type="button" class="button button-primary" id="wpspeedtestpro-opt-in-data">Opt In</button>
                <button type="button" class="button button-secondary" id="wpspeedtestpro-dismiss-notice">No Thanks</button>
            </p>
            <p><small><a href="https://wpspeedtestpro.com/privacy-policy" target="_blank">Learn more about our privacy policy</a></small></p>
        </div>
        <?php
    }

    /**
     * Enqueue scripts for the notice
     */
    public function enqueue_notice_scripts() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpspeedtestpro') === false) {
            return;
        }

        if (!$this->should_show_notice()) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('#wpspeedtestpro-opt-in-data').on('click', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpspeedtestpro_opt_in_data_collection',
                            nonce: '" . wp_create_nonce('wpspeedtestpro_data_collection_nonce') . "'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#wpspeedtestpro-data-collection-notice').fadeOut();
                            }
                        }
                    });
                });

                $('#wpspeedtestpro-dismiss-notice').on('click', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpspeedtestpro_dismiss_data_collection_notice',
                            nonce: '" . wp_create_nonce('wpspeedtestpro_data_collection_nonce') . "'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#wpspeedtestpro-data-collection-notice').fadeOut();
                            }
                        }
                    });
                });

                // Handle default dismiss button
                $('#wpspeedtestpro-data-collection-notice .notice-dismiss').on('click', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpspeedtestpro_dismiss_data_collection_notice',
                            nonce: '" . wp_create_nonce('wpspeedtestpro_data_collection_nonce') . "'
                        }
                    });
                });
            });
        ");
    }

    /**
     * Handle opt-in to data collection
     */
    public function handle_opt_in() {
        check_ajax_referer('wpspeedtestpro_data_collection_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        update_option('wpspeedtestpro_allow_data_collection', true);
        delete_option('wpspeedtestpro_data_collection_disabled_time');
        wp_send_json_success('Data collection enabled');
    }

    /**
     * Handle dismissing the notice
     */
    public function handle_dismiss() {
        check_ajax_referer('wpspeedtestpro_data_collection_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        update_option('wpspeedtestpro_data_collection_notice_dismissed', true);
        wp_send_json_success('Notice dismissed');
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