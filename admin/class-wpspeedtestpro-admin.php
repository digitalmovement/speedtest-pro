<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Admin {

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
     * @var      Wpspeedtestpro_Core    $core    The core functionality instance.
     */
    private $core;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Wpspeedtestpro_Core    $core    The core functionality instance.
     */

    private $latency_testing;
    private $ssl_testing;
    private $settings;
    private $server_performance;
    private $uptime_monitoring;
    private $page_speed_testing;

    private $server_information;
   


    public function __construct( $plugin_name, $version, $core ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;

        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for the Admin facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-wizard.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-dashboard.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-server-information.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-latency-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-server-performance.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-ssl-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-uptime-monitoring.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-page-speed-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-settings.php';


        $this->wizard               = new Wpspeedtestpro_Wizard($this->plugin_name, $this->version, $this->core);
        $this->dashboard            = new Wpspeedtestpro_Dashboard($this->plugin_name, $this->version, $this->core);
        $this->latency_testing      = new Wpspeedtestpro_Latency_Testing( $this->plugin_name, $this->version, $this->core );
        $this->settings             = new Wpspeedtestpro_Settings( $this->plugin_name, $this->version, $this->core );
        $this->ssl_testing          = new Wpspeedtestpro_SSL_Testing( $this->plugin_name, $this->version, $this->core );
        $this->server_performance   = new Wpspeedtestpro_Server_Performance( $this->plugin_name, $this->version, $this->core );
        $this->uptime_monitoring    = new Wpspeedtestpro_Uptime_Monitoring( $this->plugin_name, $this->version, $this->core );
        $this->page_speed_testing   = new Wpspeedtestpro_PageSpeed( $this->plugin_name, $this->version, $this->core );
        $this->server_information   = new Wpspeedtestpro_Server_Information($this->plugin_name, $this->version, $this->core);  
        $this->sync_handler         = new Wpspeedtestpro_Sync_Handler($this->core->db);

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
   
               // Add dashboard-specific styles only on dashboard page
               $screen = get_current_screen();
               if ($screen && strpos($screen->id, $this->plugin_name) !== 0) {
                wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-admin.css', array(), $this->version, 'all' );
                wp_enqueue_style('font-awesome',  plugin_dir_url( __FILE__ ) . 'assets/css/font-awesome-all.min.css', array(), $this->version, 'all');


                   wp_enqueue_style(
                       $this->plugin_name . '-dashboard',
                       plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-dashboard.css',
                       array(),
                       $this->version
                   );
               }

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        $screen = get_current_screen();
        if ($screen && strpos($screen->id, $this->plugin_name) !== 0) {
        

            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-admin.js', array( 'jquery' ), $this->version, false );
            wp_enqueue_script( 'chart-js',   plugin_dir_url( __FILE__ ) . 'js/chart.js', array(), '3.7.0', true );
            wp_enqueue_script('chart-date-js',   plugin_dir_url( __FILE__ ) . 'js/chartjs-adapter-date-fns.bundle.min.js', array(), '3.7.0', true);

            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
            
            // Enqueue jQuery UI CSS
            wp_enqueue_style('jquery-ui-css',   plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', array(), $this->version, 'all');

                wp_localize_script(
                    $this->plugin_name . '-dashboard',
                    'wpspeedtestpro_dashboard',
                    array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                        'selected_region' => get_option('wpspeedtestpro_selected_region'),
                        'home_url' => home_url()
                    )
                );
        
        }

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_server_performance_scripts' ) );
    }

    /**
     * Enqueue scripts specifically for the server performance page.
     *
     * @since    1.0.0
     */
    // The below function is not used in the plugin - canditate for removal
     public function enqueue_server_performance_scripts($hook) {
        if ($hook === 'wpspeedtestpro_page_' . $this->plugin_name . '-server-performance') {
            wp_enqueue_script( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-server-performance.js', array( 'jquery', 'chart-js' ), $this->version, false );
            wp_enqueue_style( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-server-performance.css', array(), $this->version, 'all' );
        }
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_menu_page( 'WP Speedtest Pro', 'WP Speedtest Pro', 'manage_options', $this->plugin_name, array($this, 'display_plugin_dashboard_page'), 'dashicons-performance', 99 );
       
        add_submenu_page( $this->plugin_name, 'Dashboard', 'Dashboard', 'manage_options', $this->plugin_name, array($this, 'display_plugin_dashboard_page') );
        add_submenu_page( $this->plugin_name, 'Server Information','Server Information','manage_options',$this->plugin_name . '-server-information',array($this, 'display_plugin_server_information_page'));
        add_submenu_page( $this->plugin_name, 'Server Performance', 'Server Performance', 'manage_options', $this->plugin_name . '-server-performance', array($this, 'display_plugin_server_performance_page') );
        add_submenu_page( $this->plugin_name, 'Latency Testing', 'Latency Testing', 'manage_options', $this->plugin_name . '-latency-testing', array($this, 'display_plugin_latency_testing_page') );
        add_submenu_page( $this->plugin_name, 'SSL Testing', 'SSL Testing', 'manage_options', $this->plugin_name . '-ssl-testing', array($this, 'display_plugin_ssl_testing_page') );
        add_submenu_page( $this->plugin_name, 'Uptime Monitoring', 'Uptime Monitoring', 'manage_options', $this->plugin_name . '-uptime-monitoring', array($this, 'display_plugin_uptime_monitoring_page') );
        add_submenu_page( $this->plugin_name, 'Page Speed Testing', 'Page Speed Testing', 'manage_options', $this->plugin_name . '-page-speed-testing', array($this, 'display_plugin_page_speed_testing_page') );
        add_submenu_page( $this->plugin_name, 'Settings', 'Settings', 'manage_options', $this->plugin_name . '-settings', array($this, 'display_plugin_settings_page') );
    }

    /**
     * Render the dashboard page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_dashboard_page() {
        $dashboard = new Wpspeedtestpro_Dashboard( $this->plugin_name, $this->version, $this->core );
        $api = $this->core->get_api();
        $db = $this->core->get_db();
        $data = array(
            'endpoints' => $api->get_gcp_endpoints(),
            'latest_results' => $db->get_latest_results()
        );

        $dashboard->display_dashboard();
    }

    public function display_plugin_server_information_page() {
        $this->server_information->display_server_information();
    }

    private function add_hooks() {
        // ... [existing hooks]

        // Dashboard AJAX handlers
        add_action('wp_ajax_wpspeedtestpro_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_wpspeedtestpro_get_performance_data', array($this, 'get_performance_data'));
        add_action('wp_ajax_wpspeedtestpro_get_latency_data', array($this, 'get_latency_data'));
        add_action('wp_ajax_wpspeedtestpro_get_ssl_data', array($this, 'get_ssl_data'));
        add_action('wp_ajax_wpspeedtestpro_get_uptime_data', array($this, 'get_uptime_data'));
        add_action('wp_ajax_wpspeedtestpro_get_pagespeed_data', array($this, 'get_pagespeed_data'));
    }

    /**
     * Render the latency testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_latency_testing_page() {
        $this->latency_testing->display_latency_testing();
    }

    /**
     * Render the server performance page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_server_performance_page() {
        $this->server_performance->display_server_performance();
    }

    /**
     * Render the SSL testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_ssl_testing_page() {
        $this->ssl_testing->display_ssl_testing();
    }

    /**
     * Render the uptime monitoring page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_uptime_monitoring_page() {
        $this->uptime_monitoring->display_uptime_monitoring();
    }

    /**
     * Render the page speed testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_page_speed_testing_page() {
        $this->page_speed_testing->display_page_speed_testing();
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
       $this->settings->display_settings();
    }
}

class Wpspeedtestpro_Sync_Handler {
    private $db;
    private $worker_url;
    private $shared_secret;
    private $site_key;
    private $send_diagnostic_data;

    public function __construct($db) {
        $this->db = $db;
        $this->worker_url = 'https://analytics.wpspeedtestpro.com/upload';
        $this->shared_secret = 'C_xhEWoZKeRzFLcNptpSmPncIA';
        $send_diagnostic_data = false;

       // Generate or get site key
       $this->site_key = get_option('wpspeedtestpro_site_key');
       if (empty($this->site_key)) {
           $this->site_key = $this->generate_site_key();
           update_option('wpspeedtestpro_site_key', $this->site_key);
       }

       $this->init();

    }

    private function generate_site_key() {
        $unique_parts = array(
            wp_parse_url(site_url(), PHP_URL_HOST), // Domain name
            defined('ABSPATH') ? ABSPATH : '', // WordPress installation path
            defined('DB_NAME') ? DB_NAME : '', // Database name
            php_uname(), // System information
            time(), // Current timestamp
            uniqid('', true), // Unique identifier with more entropy
            random_bytes(16) // Random bytes for additional entropy
        );

        // Combine all parts and hash them
        $combined = implode('|', $unique_parts);
        return hash('sha256', $combined);
    }


    public function init() {

        add_action('wpspeedtestpro_sync_data', array($this, 'sync_data'));
        add_action('wp_ajax_wpspeedtestpro_sync_diagnostics', array($this, 'ajax_sync_diagnostics'));


        // Schedule hourly sync if not already scheduled
        if (!wp_next_scheduled('wpspeedtestpro_sync_data')) {
            wp_schedule_event(time(), 'hourly', 'wpspeedtestpro_sync_data');
        }

    }

    private function get_environment_info() {
        global $wp_version;
        
        return array(
            'php_version' => phpversion(),
            'wp_version' => $wp_version,
            'mysql_version' => $this->get_mysql_version(),
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'unknown',
            'os' => PHP_OS,
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'max_input_time' => ini_get('max_input_time'),
            'memory_limit' => ini_get('memory_limit'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_extensions' => get_loaded_extensions(),
            'active_plugins' => $this->get_active_plugins(),
            'theme' => $this->get_theme_info(),
            'multisite' => is_multisite(),
            'is_ssl' => is_ssl(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'timezone' => wp_timezone_string(),
            'hosting_provider' => get_option('wpspeedtestpro_selected_provider'),
            'hosting_package' => get_option('wpspeedtestpro_selected_package'),
            'hosting_region' => get_option('wpspeedtestpro_selected_region')
        );
    }

    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
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

    private function get_theme_info() {
        $theme = wp_get_theme();
        return array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author')
        );
    }

    private function generate_signature($data) {
        return hash_hmac('sha256', json_encode($data), $this->shared_secret);
    }

    public function ajax_sync_diagnostics() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        
        $this->send_diagnostic_data = true; // allow up to force data collection as the user requested it

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        try {
            $this->sync_data();
            wp_send_json_success(array('site_key' => get_option('wpspeedtestpro_site_key')));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    

    public function sync_data() {

        // Check if data collection is still allowed
        if (!$this->send_diagnostic_data && !get_option('wpspeedtestpro_allow_data_collection', false)) {
            return;
        }


        try {
            // Get all unsynced data
            $unsynced_data = $this->db->get_unsynced_data();
            
            if (empty($unsynced_data['benchmark_results']) && 
                empty($unsynced_data['hosting_results']) && 
                empty($unsynced_data['pagespeed_results'])) {
                return;
            }

            // Remove full_report and URL from pagespeed results to anonymize data
            if (!empty($unsynced_data['pagespeed_results'])) {
                $unsynced_data['pagespeed_results'] = array_map(function($result) {
                    unset($result['full_report']);
                    unset($result['url']);
                    return $result;
                }, $unsynced_data['pagespeed_results']);
            }



            // Prepare data for sync
            $sync_data = array(
                'site_key' => $this->site_key,
                'sync_time' => current_time('mysql'),
                'environment' => $this->get_environment_info(),
                'data' => $unsynced_data
            );

            // Generate signature
            $signature = $this->generate_signature($sync_data);

            // Send to Cloudflare Worker
            $response = wp_remote_post($this->worker_url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-Signature' => $signature
                ),
                'body' => json_encode($sync_data),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                throw new Exception('Failed to send data: ' . $response->get_error_message());
            }

            

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($body['success'])) {
                // Mark data as synced
                if (!empty($unsynced_data['benchmark_results'])) {
                    $this->db->mark_as_synced('benchmark', array_column($unsynced_data['benchmark_results'], 'id'));
                }
                if (!empty($unsynced_data['hosting_results'])) {
                    $this->db->mark_as_synced('hosting', array_column($unsynced_data['hosting_results'], 'id'));
                }
                if (!empty($unsynced_data['pagespeed_results'])) {
                    $this->db->mark_as_synced('pagespeed', array_column($unsynced_data['pagespeed_results'], 'id'));
                }
            }

        } catch (Exception $e) {
            error_log('WPSpeedTestPro Sync Error: ' . $e->getMessage());
        }
    }
}