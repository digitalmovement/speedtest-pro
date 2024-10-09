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
 * This benchamarking code is inspired by: 
 * - www.php-benchmark-script.com  (Alessandro Torrisi)
 * - www.webdesign-informatik.de
 * - WPBenchmarking 
 *
 * Defines the plugin name, version, and hooks for the server performance functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Server_Performance {

    private $plugin_name;
    private $version;
    private $core;

    public function __construct( $plugin_name, $version, $core ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->add_hooks();
        $this->create_benchmark_table();
        $this->schedule_continuous_test();
    }

    public function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_performance_toggle_test', array($this, 'ajax_performance_toggle_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_run_test', array($this, 'ajax_performance_run_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_get_results', array($this, 'ajax_performance_get_results'));
        add_action('wp_ajax_wpspeedtestpro_start_continuous_test', array($this, 'ajax_start_continuous_test'));
        add_action('wp_ajax_wpspeedtestpro_stop_continuous_test', array($this, 'ajax_stop_continuous_test'));
        add_action('wp_ajax_wpspeedtestpro_get_next_test_time', array($this, 'ajax_get_next_test_time'));
        add_action('wpspeedtestpro_continuous_test', array($this, 'run_continuous_test'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
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
        wp_enqueue_script('chart-date-js', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js', array(), '3.7.0', true);

        


   
        wp_enqueue_script( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-server-performance.js', array( 'jquery' ), $this->version, false );
        
        $continuous_test_status = get_option('wpspeedtestpro_continuous_test_status', 'stopped');
        $continuous_test_start_time = get_option('wpspeedtestpro_continuous_test_start_time', 0);
        $current_time = current_time('timestamp');
        $time_remaining = max(0, 86400 - ($current_time - $continuous_test_start_time));

        $data = array(
            'continuousTestStatus' => $continuous_test_status,
            'timeRemaining' => $time_remaining,
        );


        wp_localize_script(
            'wpspeedtestpro-server-performance',
            'wpspeedtestpro_performance',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_performance_nonce'),
                'testStatus' => get_option('wpspeedtestpro_performance_test_status', 'stopped'),
                'continuousTestStatus' => get_option('wpspeedtestpro_continuous_test_status', 'stopped'),
                'wpspeedtestpro_continuous_data' => $data
            )
        );
    }
    private function create_benchmark_table() {
        $db = new Wpspeedtestpro_DB();
        $this->core->db->create_benchmark_table();
    }

    public function display_server_performance() {
/*        $continuous_test_status = get_option('wpspeedtestpro_continuous_test_status', 'stopped');
        $continuous_test_start_time = get_option('wpspeedtestpro_continuous_test_start_time', 0);
        $current_time = current_time('timestamp');
        $time_remaining = max(0, 86400 - ($current_time - $continuous_test_start_time));

        $data = array(
            'continuousTestStatus' => $continuous_test_status,
            'timeRemaining' => $time_remaining,
        );

        wp_localize_script('wpspeedtestpro-server-performance1', 'wpspeedtestpro_continuous_data', $data);
*/

        include_once( 'partials/wpspeedtestpro-server-performance-display.php' );
    }

    public function ajax_start_continuous_test() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        update_option('wpspeedtestpro_continuous_test_status', 'running');
        update_option('wpspeedtestpro_continuous_test_start_time', current_time('timestamp'));
        $this->schedule_continuous_test();

        wp_send_json_success();
    }

    public function ajax_stop_continuous_test() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        update_option('wpspeedtestpro_continuous_test_status', 'stopped');
        wp_clear_scheduled_hook('wpspeedtestpro_continuous_test');

        wp_send_json_success();
    }

    public function ajax_get_next_test_time() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $next_test_time = wp_next_scheduled('wpspeedtestpro_continuous_test');
        if ($next_test_time) {
            wp_send_json_success(date('Y-m-d H:i:s', $next_test_time));
        } else {
            wp_send_json_error('No scheduled test found');
        }
    }

    private function schedule_continuous_test() {
        $args = array( false );
        if (get_option('wpspeedtestpro_continuous_test_status') === 'running') {
            if (!wp_next_scheduled('wpspeedtestpro_continuous_test',$args)) {
                wp_schedule_event(time(), 'wpspeedtestpro_fifteen_minutes', 'wpspeedtestpro_continuous_test', $args);
            }
        }
    }

    

    public function run_continuous_test() {
        $start_time = get_option('wpspeedtestpro_continuous_test_start_time');
        $current_time = current_time('timestamp');
        
        if (($current_time - $start_time) >= 86400) { // 24 hours
            update_option('wpspeedtestpro_continuous_test_status', 'stopped');
            wp_clear_scheduled_hook('wpspeedtestpro_continuous_test');
            error_log('Continuous test completed after 24 hours.');
            return;
        }

        $result = $this->run_performance_tests();
        if ($result !== true) { 
            $this->log_message('Continuous test error: ' . $result);
        } else {
            $this->log_message('Continuous test executed successfully.');
        }
    }


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

        $result = $this->run_performance_tests();
        if ($result === true) {
            wp_send_json_success('Tests completed successfully');
        } else {
            wp_send_json_error($result);
        }
    }

    public function ajax_performance_get_results() {
        check_ajax_referer('wpspeedtestpro_performance_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = $this->get_test_results();
        $industry_avg = $this->get_industry_averages();
        
        wp_send_json_success(array(
            'latest_results' => $results['latest_results'],
            'math' => $results['math'],
            'string' => $results['string'],
            'loops' => $results['loops'],
            'conditionals' => $results['conditionals'],
            'mysql' => $results['mysql'],
            'wordpress_performance' => $results['wordpress_performance'],
            'industry_avg' => $industry_avg
        ));
    }

    private function run_performance_tests() {
        try {
            update_option('wpspeedtestpro_performance_test_status', 'running');
            
            $results = array(
                'latest_results' => array(
                    'math' => $this->test_math(),
                    'string' => $this->test_string(),
                    'loops' => $this->test_loops(),
                    'conditionals' => $this->test_conditionals(),
                    'mysql' => $this->test_mysql(),
                    'wordpress_performance' => $this->test_wordpress_performance()
                )
            );

            $save_result = $this->save_test_results($results['latest_results']);
            if ($save_result !== true) {
                throw new Exception('Failed to save test results: ' . $save_result);
            }

            $results['math'] = $this->get_historical_results('math');
            $results['string'] = $this->get_historical_results('string');
            $results['loops'] = $this->get_historical_results('loops');
            $results['conditionals'] = $this->get_historical_results('conditionals');
            $results['mysql'] = $this->get_historical_results('mysql');
            $results['wordpress_performance'] = $this->get_historical_results('wordpress_performance');

            update_option('wpspeedtestpro_performance_test_results', $results);
            update_option('wpspeedtestpro_performance_test_status', 'stopped');

            return true;
        } catch (Exception $e) {
            update_option('wpspeedtestpro_performance_test_status', 'error');
            return 'Error running performance tests: ' . $e->getMessage();
        }
    }

    private function test_math($count = 99999) {
        $time_start = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            sin($i);
            asin($i / $count);
            cos($i);
            acos($i / $count);
            tan($i);
            atan($i);
            abs($i);
            floor($i);
            exp($i % 10);
            is_finite($i);
            is_nan($i);
            sqrt(abs($i));
            log10($i + 1);
        }
    
        return $this->timer_delta($time_start);
    }

    private function test_string($count = 99999) {
        $time_start = microtime(true);
        $string = 'the quick brown fox jumps over the lazy dog';
        for ($i = 0; $i < $count; $i++) {
            addslashes($string);
            chunk_split($string);
            metaphone($string);
            strip_tags($string);
            md5($string);
            sha1($string);
            strtoupper($string);
            strtolower($string);
            strrev($string);
            strlen($string);
            soundex($string);
            ord($string);
        }
        return $this->timer_delta($time_start);
    }

    private function test_loops($count = 999999) {
        $time_start = microtime(true);
        for ($i = 0; $i < $count; ++$i);
        $i = 0;
        while ($i < $count) {
            ++$i;
        }
        return $this->timer_delta($time_start);
    }

    private function test_conditionals($count = 999999) {
        $time_start = microtime(true);
        for ($i = 0; $i < $count; $i++) {
            if ($i == -1) {
            } elseif ($i == -2) {
            } else if ($i == -3) {
            }
        }
        return $this->timer_delta($time_start);
    }

    private function test_mysql() {
        $time_start = microtime(true);
        global $wpdb;
        
        $query = "SELECT BENCHMARK(1000000, AES_ENCRYPT('WPSpeedTestPro',UNHEX(SHA2('benchmark',512))))";
        $wpdb->query($query);
        
        return $this->timer_delta($time_start);
    }

    private function test_wordpress_performance() {
        $time_start = microtime(true);
        global $wpdb;
        $table = $wpdb->prefix . 'options';
        $optionname = 'wpspeedtestpro_benchmark_';
        $count = 250;
        $dummytext = str_repeat('Lorem ipsum dolor sit amet ', 100);

        for ($x = 0; $x < $count; $x++) {
            $wpdb->insert($table, array('option_name' => $optionname . $x, 'option_value' => $dummytext));
            $wpdb->get_var("SELECT option_value FROM $table WHERE option_name='$optionname$x'");
            $wpdb->update($table, array('option_value' => 'updated_' . $dummytext), array('option_name' => $optionname . $x));
            $wpdb->delete($table, array('option_name' => $optionname . $x));
        }

        $time = $this->timer_delta($time_start);
        $queries = ($count * 4) / $time;
        return array('time' => $time, 'queries' => $queries);
    }

    private function timer_delta($time_start) {
        return number_format(microtime(true) - $time_start, 3);
    }

    private function get_test_results() {
        return get_option('wpspeedtestpro_performance_test_results', array(
            'latest_results' => array(
                'math' => 0,
                'string' => 0,
                'loops' => 0,
                'conditionals' => 0,
                'mysql' => 0,
                'wordpress_performance' => array('time' => 0, 'queries' => 0)
            ),
            'math' => array(),
            'string' => array(),
            'loops' => array(),
            'conditionals' => array(),
            'mysql' => array(),
            'wordpress_performance' => array()
        ));
    }

    private function get_industry_averages() {
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/performance-test-averages.json');
        
        $default_averages = array(
            'math' => 0.04,
            'string' => 0.2,
            'loops' => 0.01,
            'conditionals' => 0.01,
            'mysql' => 2.3,
            'wordpress_performance' => array(
                'time' => 0.5,
                'queries' => 3000
            )
        );
        
        if (is_wp_error($response)) {
            return $default_averages;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return $default_averages;
        }
        
        // If the data is already in the correct format, use it directly
        if (isset($data['math']) && isset($data['wordpress_performance'])) {
            $averages = $data;
        } 
        // If the data has an 'industry_avg' key, use that
        elseif (isset($data['industry_avg'])) {
            $averages = $data['industry_avg'];
        } 
        // Otherwise, assume the whole data is the averages
        else {
            $averages = $data;
        }
        
        // Ensure all required keys are present
        $required_keys = array('math', 'string', 'loops', 'conditionals', 'mysql', 'wordpress_performance');
        foreach ($required_keys as $key) {
            if (!isset($averages[$key])) {
                $averages[$key] = $default_averages[$key];
            }
        }
        
        // Ensure WordPress performance has both time and queries
        if (!isset($averages['wordpress_performance']['time'])) {
            $averages['wordpress_performance']['time'] = $default_averages['wordpress_performance']['time'];
        }
        if (!isset($averages['wordpress_performance']['queries'])) {
            $averages['wordpress_performance']['queries'] = $default_averages['wordpress_performance']['queries'];
        }
        
        return $averages;
    }


    private function save_test_results($results) {
        try {
            //$db = new Wpspeedtestpro_DB();
            $this->core->db->insert_benchmark_result($results);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*
    private function get_historical_results($test_type, $limit = 30) {
        try {
            $db = new Wpspeedtestpro_DB();
            return $db->get_benchmark_results($limit);
        } catch (Exception $e) {
            error_log('Error getting historical results: ' . $e->getMessage());
            return array();
        }
    }
*/
    private function get_historical_results($test_type, $limit = 30) {
        try {
            //$db = new Wpspeedtestpro_DB();
            $results = $this->core->db->get_benchmark_results($limit);
            
            return array_map(function($result) use ($test_type) {
                if ($test_type === 'wordpress_performance') {
                    return [
                        'test_date' => $result['test_date'],
                        'wordpress_performance' => [
                            'time' => $result['wordpress_performance_time'],
                            'queries' => $result['wordpress_performance_queries']
                        ]
                    ];
                } else {
                    return [
                        'test_date' => $result['test_date'],
                        $test_type => $result[$test_type]
                    ];
                }
            }, $results);
        } catch (Exception $e) {
            error_log('Error getting historical results: ' . $e->getMessage());
            return array();
        }
    }

    private function log_message($message) {
        $log_file = WP_CONTENT_DIR . '/wpspeedtestpro-performance.log';
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        error_log($log_message, 3, $log_file);
    }


}