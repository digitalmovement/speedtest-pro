<?php

/**
 * The server performance functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The server performance functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the server performance functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Server_Performance {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    private $core;

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

    /**
     * Add the hooks for the server performance area.
     *
     * @since    1.0.0
     */
    public function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_performance_toggle_test', array($this, 'ajax_performance_toggle_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_run_test', array($this, 'ajax_performance_run_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_get_results', array($this, 'ajax_performance_get_results'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

    }
    /**
     * Register the stylesheets for the server performance area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css', array(), null);
        wp_enqueue_style( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-server-performance.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the server performance area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
   
        wp_enqueue_script( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-server-performance.js', array( 'jquery' ), $this->version, false );
        
        // Enqueue jQuery UI CSS

        wp_localize_script(
            'wpspeedtestpro-server-performance',
            'wpspeedtestpro_performance',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_performance_nonce'),
                'testStatus' => get_option('wpspeedtestpro_performance_test_status', 'stopped')
            )
        );

    }

    /**
     * Render the server performance page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_server_performance() {
        include_once( 'partials/wpspeedtestpro-server-performance-display.php' );
    }

    // Add more methods as needed for server performance functionality
    public function ajax_performance_toggle_test() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'stopped';
        update_option('wpspeedtestpro_performance_test_status', $status);
        wp_send_json_success();
    }

    public function ajax_performance_run_test() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Run tests in the background
        $this->run_performance_tests();
        
        wp_send_json_success();
    }

    public function ajax_performance_get_results() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = $this->get_test_results();
        $industry_avg = $this->get_industry_averages();
        
        wp_send_json_success(array(
            'cpu_memory' => $results['cpu_memory'],
            'filesystem' => $results['filesystem'],
            'database' => $results['database'],
            'object_cache' => $results['object_cache'],
            'industry_avg' => $industry_avg
        ));
    }

    private function run_performance_tests() {
        $results = array(
            'cpu_memory' => $this->test_cpu_memory(),
            'filesystem' => $this->test_filesystem(),
            'database' => $this->test_database(),
            'object_cache' => $this->test_object_cache()
        );

        update_option('wpspeedtestpro_performance_test_results', $results);
        update_option('wpspeedtestpro_performance_test_status', 'stopped');
    }

    private function test_cpu_memory() {
        // Implement CPU & Memory test
        // This is a placeholder implementation
        sleep(5);
        return rand(1, 5);
    }

    private function test_filesystem() {
        // Implement Filesystem test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function test_database() {
        // Implement Database test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function test_object_cache() {
        // Implement Object Cache test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function get_test_results() {
        return get_option('wpspeedtestpro_performance_test_results', array(
            'cpu_memory' => 0,
            'filesystem' => 0,
            'database' => 0,
            'object_cache' => 0
        ));
    }

    private function get_industry_averages() {
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/performance-test-averages.json');
        
        if (is_wp_error($response)) {
            return array(
                'cpu_memory' => 2.5,
                'filesystem' => 2.5,
                'database' => 2.5,
                'object_cache' => 2.5
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return array(
                'cpu_memory' => 2.5,
                'filesystem' => 2.5,
                'database' => 2.5,
                'object_cache' => 2.5
            );
        }
        
        return $data;
    }
    
}