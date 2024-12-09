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
    }

    private function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_wpspeedtestpro_get_performance_data', array($this, 'get_performance_data'));
        add_action('wp_ajax_wpspeedtestpro_get_latency_data', array($this, 'get_latency_data'));
        add_action('wp_ajax_wpspeedtestpro_get_ssl_data', array($this, 'get_ssl_data'));
        add_action('wp_ajax_wpspeedtestpro_get_uptime_data', array($this, 'get_uptime_data'));
        add_action('wp_ajax_wpspeedtestpro_get_pagespeed_data', array($this, 'get_pagespeed_data'));
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

            wp_localize_script($this->plugin_name . '-dashboard', 'wpspeedtestpro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_nonce'),
                'performance_nonce' => wp_create_nonce('wpspeedtestpro_performance_nonce'),
                'ssl_nonce' => wp_create_nonce('ssl_testing_nonce'),
                'uptime_nonce' => wp_create_nonce('wpspeedtestpro_uptime_nonce'),
                'pagespeed_nonce' => wp_create_nonce('wpspeedtestpro-page-speed-testing-nonce'),
                'selected_region' => get_option('wpspeedtestpro_selected_region'),
                'home_url' => home_url()
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
        check_ajax_referer('wpspeedtestpro_dashboard_nonce', 'nonce');

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
        $desktop = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE device = %s 
                ORDER BY test_date DESC 
                LIMIT 1",
                'desktop'
            )
        );

        // Get latest mobile result
        $mobile = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
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
        check_ajax_referer('wpspeedtestpro_pagespeed_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $data = $this->get_pagespeed_summary();
        wp_send_json_success($data);
    }

}