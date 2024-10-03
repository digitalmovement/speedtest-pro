<?php

/**
 * The latency testing functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The latency testing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the latency testing functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Latency_Testing {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The core functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Wpspeedtestpro_Core    $core    The core functionality of the plugin.
     */
    private $core;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Wpspeedtestpro_Core    $core    The core functionality of the plugin.
     */
    public function __construct($plugin_name, $version, $core) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;

        $this->add_hooks();
    }

    /**
     * Add hooks for latency testing functionality.
     *
     * @since    1.0.0
     */
    private function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_start_latency_test', array($this, 'start_latency_test'));
        add_action('wp_ajax_wpspeedtestpro_reset_latency_test', array($this, 'reset_latency_test'));
        add_action('wp_ajax_wpspeedtestpro_stop_latency_test', array($this, 'stop_latency_test'));
        add_action('wp_ajax_wpspeedtestpro_get_latest_results', array($this, 'get_latest_results'));
        add_action('wp_ajax_wpspeedtestpro_get_results_for_time_range', array($this, 'get_results_for_time_range'));
        add_action('wp_ajax_wpspeedtestpro_delete_all_results', array($this, 'delete_all_results'));
        add_action('wpspeedtestpro_cron_hook', array($this, 'run_scheduled_test'));
    }

    /**
     * Register the stylesheets for the latency testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-latency-testing', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-latency-testing.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the latency testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name . '-latency-testing', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-latency-testing.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name . '-latency-testing', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_nonce')
        ));
    }

    /**
     * Render the latency testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_latency_testing() {
        include_once('partials/wpspeedtestpro-admin-latency-testing-display.php');
    }

    /**
     * Start the latency test.
     *
     * @since    1.0.0
     */
    public function start_latency_test() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

        if (!wp_next_scheduled('wpspeedtestpro_cron_hook')) {
            $start_time = time();
            update_option('wpspeedtestpro_start_time', $start_time);
            wp_schedule_event($start_time, 'five_minutes', 'wpspeedtestpro_cron_hook');

            // Run the first test immediately
            $this->run_scheduled_test();

            wp_send_json_success(array(
                'message' => 'Test started successfully',
                'start_time' => $start_time
            ));
        } else {
            wp_send_json_error('Test is already running');
        }
    }

    /**
     * Reset the latency test.
     *
     * @since    1.0.0
     */
    public function reset_latency_test() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

        wp_clear_scheduled_hook('wpspeedtestpro_cron_hook');
        delete_option('wpspeedtestpro_start_time');
        
        $this->start_latency_test();
    }

    /**
     * Stop the latency test.
     *
     * @since    1.0.0
     */
    public function stop_latency_test() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

        wp_clear_scheduled_hook('wpspeedtestpro_cron_hook');
        delete_option('wpspeedtestpro_start_time');
        wp_send_json_success('Test stopped successfully');
    }

    /**
     * Get the latest test results.
     *
     * @since    1.0.0
     */
    public function get_latest_results() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

        if (!$this->core->db) {
            wp_send_json_error('Database object not initialized');
            return;
        }

        $latest_results = $this->core->db->get_latest_results_by_region();
        $fastest_and_slowest = $this->core->db->get_fastest_and_slowest_results();

        // Merge the data
        foreach ($latest_results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }

        wp_send_json_success($latest_results);
    }

    /**
     * Get results for a specific time range.
     *
     * @since    1.0.0
     */
    public function get_results_for_time_range() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');
        
        $time_range = isset($_POST['time_range']) ? sanitize_text_field($_POST['time_range']) : '24_hours';
    
        // Fetch results from DB based on the time range
        $results = $this->core->db->get_results_by_time_range($time_range);
        $fastest_and_slowest = $this->core->db->get_fastest_and_slowest_results();

        // Merge the data
        foreach ($results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {                        
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }
        
        if (!empty($results)) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error('No results found for the selected time range.');
        }
    }

    /**
     * Delete all test results.
     *
     * @since    1.0.0
     */
    public function delete_all_results() {
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');
        $this->core->db->delete_all_results();
        wp_send_json_success('All results deleted');
    }

    /**
     * Run the scheduled latency test.
     *
     * @since    1.0.0
     */
    public function run_scheduled_test() {
        $endpoints = $this->core->api->get_gcp_endpoints();
        foreach ($endpoints as $endpoint) {
            $latency = $this->core->api->ping_endpoint($endpoint['url']);
            if ($latency !== false) {
                $this->core->db->insert_result($endpoint['region_name'], $latency);
            }
        }
    }
}