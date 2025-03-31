<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the dashboard functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Dashboard {

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
  
    private $core;

    private $uptime_monitoring;
    private $bug_report_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
  public function __construct( $plugin_name, $version, $core ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->add_hooks();
        $this->bug_report_handler   = new Wpspeedtestpro_Bug_Report_Handler($this->core->db);
        $this->bug_report_handler->init();
 
    }

    private function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_wpspeedtestpro_get_performance_data', array($this, 'get_performance_data'));
        add_action('wp_ajax_wpspeedtestpro_get_latency_data', array($this, 'get_latency_data'));
        add_action('wp_ajax_wpspeedtestpro_get_ssl_data', array($this, 'get_ssl_data'));
        add_action('wp_ajax_wpspeedtestpro_get_uptime_data', array($this, 'get_uptime_data'));
        add_action('wp_ajax_wpspeedtestpro_get_pagespeed_data', array($this, 'get_pagespeed_data'));
        add_action('wp_ajax_wpspeedtestpro_get_advertisers', array($this, 'get_advertisers_ajax'));

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'toplevel_page_wpspeedtestpro';    
        }
    }


    /**
     * Register the stylesheets for the dashboard area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if ($this->is_this_the_right_plugin_page()) {
            wp_enqueue_style( $this->plugin_name . '-dashboard', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-dashboard.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name . '-bug-report', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-bug-report.css', array(), $this->version, 'all' );

        }
    }
    /**
     * Register the JavaScript for the dashboard area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if ($this->is_this_the_right_plugin_page()) {
            wp_enqueue_script( $this->plugin_name . '-dashboard', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-dashboard.js', array( 'jquery' ), $this->version, false );
            wp_enqueue_script( $this->plugin_name . '-bug-report', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-bug-report.js', array( 'jquery' ), $this->version, false );

            wp_localize_script($this->plugin_name . '-dashboard', 'wpspeedtestpro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'performance_nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'ssl_nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'uptime_nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'pagespeed_nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'selected_region' => get_option('wpspeedtestpro_selected_region'),
                'home_url' => home_url()
            ));    
            wp_localize_script($this->plugin_name . '-dashboard', 'wpspeedtestpro_advertisers', array(
                'data' => $this->get_cached_advertisers()
            ));

        }
  
    }


    /**
     * Render the dashboard page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_dashboard() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        $api = $this->core->get_api();
        $db = $this->core->get_db();

        // Use API and DB functionalities
        $endpoints = $api->get_gcp_endpoints();
        $latest_results = $db->get_latest_results();

        if (!current_user_can('manage_options')) {
            return;
        }


        $data = array(
            'user_region' => get_option('wpspeedtestpro_selected_region'),
            'latest_tests' => array(
                'performance' => $this->get_latest_performance_test(),
                'latency' => $this->get_latest_latency_test(),
                'ssl' => $this->get_latest_ssl_test(),
                'uptime' => $this->get_latest_uptime_data(),
                'pagespeed' => $this->get_latest_pagespeed_test()
            )
        );


        // Use the data to render your dashboard
        include_once( 'partials/wpspeedtestpro-dashboard-display.php' );
    }
    // Add more methods as needed for dashboard functionality

    private function get_latest_performance_test() {
        return $this->core->db->get_latest_benchmark_results();
    }

    private function get_latest_latency_test() {
        return $this->core->db->get_latest_results_by_region();
    }

    private function get_latest_ssl_test() {
        return get_transient('wpspeedtestpro_ssl_results');
    }

    private function get_latest_uptime_data() {
        $this->uptime_monitoring    = new Wpspeedtestpro_Uptime_Monitoring( $this->plugin_name, $this->version, $this->core );
        return $this->uptime_monitoring->uptimerobot_get_monitor_data();
    }

    public function get_chart_data($type, $period = '24_hours') {
        switch ($type) {
            case 'performance':
                return $this->get_performance_chart_data($period);
            case 'latency':
                return $this->get_latency_chart_data($period);
            case 'uptime':
                return $this->get_uptime_chart_data($period);
            default:
                return array();
        }
    }

    private function get_performance_chart_data($period) {
        return $this->core->db->get_benchmark_results_by_time_range($period);
    }

    private function get_latency_chart_data($period) {
        return $this->core->db->get_results_by_time_range($period);
    }

    private function get_uptime_chart_data($period) {
        $monitor_data = $this->core->api->uptimerobot_get_monitor_data();
        if (!$monitor_data) {
            return array();
        }

        $ping_monitor = null;
        foreach ($monitor_data as $monitor) {
            if (strpos($monitor['friendly_name'], 'Ping') !== false) {
                $ping_monitor = $monitor;
                break;
            }
        }

        return $ping_monitor ? $ping_monitor['response_times'] : array();
    }


    public function get_dashboard_data() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $data = array(
            'performance' => $this->get_performance_summary(),
            'latency' => $this->get_latency_summary(),
            'ssl' => $this->get_ssl_summary(),
            'uptime' => $this->get_uptime_summary(),
            'pagespeed' => $this->get_pagespeed_summary()
        );

        wp_send_json_success($data);
    }

    private function get_performance_summary() {
        $latest_results = $this->core->db->get_latest_benchmark_results();
        $industry_avg = $this->core->api->get_industry_averages();

        return array(
            'latest_results' => $latest_results,
            'industry_avg' => $industry_avg,
            'last_tested' => isset($latest_results['test_date']) ? $latest_results['test_date'] : null
        );
    }

    private function get_latency_summary() {
        $selected_region = get_option('wpspeedtestpro_selected_region');
        $latest_results = $this->core->db->get_latest_results_by_region();
        $fastest_slowest = $this->core->db->get_fastest_and_slowest_results();

        return array(
            'selected_region' => $selected_region,
            'latest_results' => $latest_results,
            'fastest_slowest' => $fastest_slowest
        );
    }

    private function get_ssl_summary() {
        $cached_result = get_transient('wpspeedtestpro_ssl_results');
        return $cached_result ? $cached_result : array();
    }

    private function get_uptime_summary() {
        $data = array();
        $monitor_data = $this->core->api->uptimerobot_get_monitor_data();
        
        if ($monitor_data) {
            foreach ($monitor_data as $monitor) {
                if (strpos($monitor['friendly_name'], 'Ping') !== false) {
                    $data['ping'] = $monitor;
                } elseif (strpos($monitor['friendly_name'], 'Cron') !== false) {
                    $data['cron'] = $monitor;
                }
            }
        }
        
        return $data;
    }

    private function get_latest_pagespeed_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_pagespeed_results';
        
        // Get latest desktop result
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $desktop = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}wpspeedtestpro_pagespeed_results` 
                WHERE device = %s 
                ORDER BY test_date DESC 
                LIMIT 1",
                'desktop'
            )
        );

            // Get latest mobile result
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $mobile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}wpspeedtestpro_pagespeed_results` 
                WHERE device = %s 
                ORDER BY test_date DESC 
                LIMIT 1",
                'mobile'
            )
        );

        return array(
            'desktop' => $desktop,
            'mobile' => $mobile,
            'test_url' => $desktop ? $desktop->url : home_url(),
            'last_tested' => $desktop ? $desktop->test_date : null
        );
    }

    private function get_latest_pagespeed_test() {
        $results = $this->get_latest_pagespeed_data();
        
        // Format the results for the dashboard
        $formatted = array();
        
        foreach (['desktop', 'mobile'] as $device) {
            if ($results[$device]) {
                $formatted[$device] = array(
                    'performance_score' => $results[$device]->performance_score,
                    'accessibility_score' => $results[$device]->accessibility_score,
                    'best_practices_score' => $results[$device]->best_practices_score,
                    'seo_score' => $results[$device]->seo_score,
                    'fcp' => $results[$device]->fcp,
                    'lcp' => $results[$device]->lcp,
                    'cls' => $results[$device]->cls,
                    'si' => $results[$device]->si,
                    'tti' => $results[$device]->tti,
                    'tbt' => $results[$device]->tbt,
                    'test_date' => $results[$device]->test_date,
                    'url' => $results[$device]->url
                );
            }
        }

        return array(
            'results' => $formatted,
            'test_url' => $results['test_url'],
            'last_tested' => $results['last_tested']
        );
    }

    public function get_pagespeed_summary() {
        $latest_results = $this->get_latest_pagespeed_test();
        
        $desktop = isset($latest_results['results']['desktop']) ? $latest_results['results']['desktop'] : null;
        $mobile = isset($latest_results['results']['mobile']) ? $latest_results['results']['mobile'] : null;

        return array(
            'desktop' => $desktop,
            'mobile' => $mobile,
            'test_url' => $latest_results['test_url'],
            'last_tested' => $latest_results['last_tested']
        );
    }

    public function get_pagespeed_data() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $data = $this->get_pagespeed_summary();
        wp_send_json_success($data);
    }

    public function get_ssl_data() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $data = $this->get_ssl_summary();
        wp_send_json_success($data);
    }

    private function get_cached_advertisers() {
        $cached = get_transient('wpspeedtestpro_advertisers');
        if ($cached !== false) {
            return $cached;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/dashboard-advertisers.json');
        if (is_wp_error($response)) {
            return false;
        }
    
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data)) {
            return false;
        }
    
        // Filter for 300x250 banners only
        $data['banners'] = array_filter($data['banners'], function($banner) {
            return $banner['imageSize'] === '300x250';
        });
    
        // Cache for 7 days
        set_transient('wpspeedtestpro_advertisers', $data, 7 * DAY_IN_SECONDS);
        return $data;
    }
    
    public function get_advertisers_ajax() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        $data = $this->get_cached_advertisers();
        wp_send_json_success($data);
    }

}


class Wpspeedtestpro_Bug_Report_Handler {
    private $db;
    private $worker_url;
    private $shared_secret;
    private $site_key;

    public function __construct($db) {
        $this->db = $db;
        $this->worker_url = 'https://analytics.wpspeedtestpro.com/bugreport';
        $this->shared_secret =  'C_xhEWoZKeRzFLcNptpSmPncIA';
        $this->site_key = get_option('wpspeedtestpro_site_key');
    }

    public function init() {
        add_action('wp_ajax_wpspeedtestpro_submit_bug_report', array($this, 'handle_bug_report'));
    }


    private function generate_signature($data) {
        return hash_hmac('sha256', json_encode($data), $this->shared_secret);
    }


    public function handle_bug_report() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }

        // Collect form data
        $report_data = array(
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'priority' => isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : '',
            'severity' => isset($_POST['severity']) ? sanitize_text_field(wp_unslash($_POST['severity'])) : '',
            'steps_to_reproduce' => isset($_POST['stepsToReproduce']) ? sanitize_textarea_field(wp_unslash($_POST['stepsToReproduce'])) : '',
            'expected_behavior' => isset($_POST['expectedBehavior']) ? sanitize_textarea_field(wp_unslash($_POST['expectedBehavior'])) : '',
            'actual_behavior' => isset($_POST['actualBehavior']) ? sanitize_textarea_field(wp_unslash($_POST['actualBehavior'])) : '',
            'frequency' => isset($_POST['frequency']) ? sanitize_text_field(wp_unslash($_POST['frequency'])) : '',
            'environment' => array(
                'os' => isset($_POST['os']) ? sanitize_text_field(wp_unslash($_POST['os'])) : '',
                'browser' => isset($_POST['browser']) ? sanitize_text_field(wp_unslash($_POST['browser'])) : '',
                'device_type' => isset($_POST['deviceType']) ? sanitize_text_field(wp_unslash($_POST['deviceType'])) : '',
            ),
            'site_info' => $this->get_site_info(),
            'timestamp' => current_time('mysql'),
        );

        // Handle file uploads
        $screenshots = array();
        foreach ($_FILES as $key => $file) {
            if (strpos($key, 'screenshot_') === 0) {
                $upload = $this->handle_file_upload($file);
                if ($upload['success']) {
                    $screenshots[] = $upload['url'];
                }
            }
        }
        $report_data['screenshots'] = $screenshots;

       
        // Prepare the payload
        $payload = array(
            'site_key' => $this->site_key,
            'report_data' => $report_data,
        );

         // Generate signature for verification
        $signature = $this->generate_signature($payload);

        // Send to worker
        $response = wp_remote_post($this->worker_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Signature' => $signature
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to submit bug report: ' . $response->get_error_message());
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['success'])) {
            wp_send_json_success('Bug report submitted successfully');
        } else {
            wp_send_json_error('Failed to submit bug report: ' . ($body['message'] ?? 'Unknown error'));
        }
    }

    private function handle_file_upload($file) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($file, array(
            'test_form' => false,
            'mimes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ),
        ));

        if (isset($upload['error'])) {
            return array('success' => false, 'error' => $upload['error']);
        }

        return array('success' => true, 'url' => $upload['url']);
    }

    private function get_site_info() {
        global $wp_version;
        
        return array(
            'wp_version' => $wp_version,
            'php_version' => phpversion(),
            'active_plugins' => $this->get_active_plugins(),
            'current_theme' => wp_get_theme()->get('Name'),
            'site_url' => site_url(),
            'plugin_version' => WPSPEEDTESTPRO_VERSION,
        );
    }

    private function get_active_plugins() {
        $active_plugins = get_option('active_plugins');
        $plugin_info = array();
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_info[] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version']
            );
        }
        
        return $plugin_info;
    }


    
}
