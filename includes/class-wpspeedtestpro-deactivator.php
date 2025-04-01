<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Deactivator {
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_deactivation_scripts'));
        add_action('wp_ajax_wpspeedtestpro_pre_deactivation', array(__CLASS__, 'handle_pre_deactivation'));
    }

    public static function enqueue_deactivation_scripts($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }

        wp_enqueue_style(
            'wpspeedtestpro-deactivation',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/wpspeedtestpro-deactivation.css',
            array(),
            WPSPEEDTESTPRO_VERSION
        );

        wp_enqueue_script(
            'wpspeedtestpro-deactivation',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/wpspeedtestpro-deactivation.js',
            array('jquery'),
            WPSPEEDTESTPRO_VERSION,
            true
        );

        wp_localize_script(
            'wpspeedtestpro-deactivation',
            'wpspeedtestpro_deactivation',
            array(
                'nonce' => wp_create_nonce('wpspeedtestpro_deactivation')
            )
        );
    }

    public static function handle_pre_deactivation() {
        check_ajax_referer('wpspeedtestpro_deactivation', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        if (!isset($_POST['delete_data']) || !sanitize_text_field(wp_unslash($_POST['delete_data']))) {
            wp_send_json_success();
            return;
        }

        try {
            self::delete_plugin_data();
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public static function delete_plugin_data() {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        try {
            // Delete all plugin tables
            $tables = array(
                $wpdb->prefix . 'wpspeedtestpro_benchmark_results',
                $wpdb->prefix . 'wpspeedtestpro_pagespeed_results',
                $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled',
                $wpdb->prefix . 'wpspeedtestpro_latency_results'
            );

            foreach ($tables as $table) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
                $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
            }

            // Delete all plugin options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpspeedtestpro_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            // Clear any scheduled cron events
            wp_clear_scheduled_hook('wpspeedtestpro_hourly_test');
            wp_clear_scheduled_hook('wpspeedtestpro_cron_hook');
            wp_clear_scheduled_hook('pagespeed_run_scheduled_tests');
            wp_clear_scheduled_hook('wpspeedtestpro_daily_pagespeed_check');
            

            // Delete any transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpspeedtestpro_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wpspeedtestpro_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pagespeed_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pagespeed_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_latency_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Clear all plugin-related object cache entries
            $cache_groups = ['', 'wpspeedtestpro', 'pagespeed'];
            foreach ($cache_groups as $group) {
                wp_cache_delete('wpspeedtestpro_latest_results', $group);
                wp_cache_delete('wpspeedtestpro_latest_benchmark_results', $group);
                wp_cache_delete('wpspeedtestpro_latest_results_by_region', $group);
                wp_cache_delete('wpspeedtestpro_fastest_and_slowest_results', $group);
                wp_cache_delete('wpspeedtestpro_unsynced_data', $group);
                wp_cache_delete('wpspeedtestpro_latency_results', $group);
                wp_cache_delete('wpspeedtestpro_latency_results_by_region', $group);
                wp_cache_delete('wpspeedtestpro_fastest_and_slowest_latency_results', $group);

                // Delete time range caches
                $time_ranges = ['24_hours', '7_days', '90_days'];
                foreach ($time_ranges as $range) {
                    wp_cache_delete('wpspeedtestpro_benchmark_results_' . $range, $group);
                    wp_cache_delete('wpspeedtestpro_latency_results_' . $range, $group);
                }
                
                // Delete benchmark results with different limits
                for ($i = 1; $i <= 100; $i++) {
                    wp_cache_delete('wpspeedtestpro_benchmark_results_' . $i, $group);
                }
            }
        
            // Commit transaction
            $wpdb->query('COMMIT'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            // Clean object cache if available
            wp_cache_flush();

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            throw new Exception('Failed to delete plugin data: ' . esc_html($e->getMessage()));
        }
    }

    public static function deactivate() {
        // Handle basic deactivation tasks
        wp_clear_scheduled_hook('pagespeed_check_scheduled_tests');
        wp_clear_scheduled_hook('wpspeedtestpro_sync_data');
        wp_clear_scheduled_hook('wpspeedtestpro_check_scheduled_pagespeed_tests');
        $timestamp = wp_next_scheduled('wpspeedtestpro_check_scheduled_pagespeed_tests');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpspeedtestpro_check_scheduled_pagespeed_tests');
        }

        
        // Data deletion is handled via AJAX before deactivation
    }
}
