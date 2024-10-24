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

        if (!isset($_POST['delete_data']) || !$_POST['delete_data']) {
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
        $wpdb->query('START TRANSACTION');

        try {
            // Delete all plugin tables
            $tables = array(
                $wpdb->prefix . 'wpspeedtestpro_hosting_benchmarking_results',
                $wpdb->prefix . 'wpspeedtestpro_benchmark_results',
                $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests',
                $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests'
            );

            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }

            // Delete all plugin options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpspeedtestpro_%'");

            // Clear any scheduled cron events
            wp_clear_scheduled_hook('wpspeedtestpro_hourly_test');
            wp_clear_scheduled_hook('speedvitals_check_scheduled_tests');
            wp_clear_scheduled_hook('speedvitals_check_pending_tests');
            wp_clear_scheduled_hook('wpspeedtestpro_cron_hook');
            wp_clear_scheduled_hook('speedvitals_run_scheduled_tests');

            // Delete any transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpspeedtestpro_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wpspeedtestpro_%'");

            // Commit transaction
            $wpdb->query('COMMIT');

            // Clean object cache if available
            wp_cache_flush();

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            throw new Exception('Failed to delete plugin data: ' . $e->getMessage());
        }
    }

    public static function deactivate() {
        // Handle basic deactivation tasks
        wp_clear_scheduled_hook('speedvitals_check_scheduled_tests');
        
        // Data deletion is handled via AJAX before deactivation
    }
}
