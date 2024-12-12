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
        $this->schedule_continuous_test();
    }

    public function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_performance_toggle_test', array($this, 'ajax_performance_toggle_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_run_test', array($this, 'ajax_performance_run_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_get_results', array($this, 'ajax_performance_get_results'));
        add_action('wp_ajax_wpspeedtestpro_performance_start_continuous_test', array($this, 'ajax_performance_start_continuous_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_stop_continuous_test', array($this, 'ajax_performance_stop_continuous_test'));
        add_action('wp_ajax_wpspeedtestpro_performance_get_next_test_time', array($this, 'ajax_performance_get_next_test_time'));
        add_action('wp_ajax_wpspeedtestpro_dismiss_performance_info', array($this, 'dismiss_performance_info'));
        
        add_action('wpspeedtestpro_continuous_test', array($this, 'run_continuous_test'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
     
     }

    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speed-test-pro_page_wpspeedtestpro-server-performance';    
        }
    }


    public function enqueue_styles() {
        if ($this->is_this_the_right_plugin_page()) {
            wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css', array(), null);
            wp_enqueue_style( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-server-performance.css', array(), $this->version, 'all' );
        }
    }

    /**
     * Register the JavaScript for the server performance area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        if ($this->is_this_the_right_plugin_page()) {
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
                    'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                    'testStatus' => get_option('wpspeedtestpro_performance_test_status', 'stopped'),
                    'continuousTestStatus' => get_option('wpspeedtestpro_continuous_test_status', 'stopped'),
                    'wpspeedtestpro_continuous_data' => $data
                )
            );

            wp_localize_script($this->plugin_name . '-server-performance', 'wpspeedtestpro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
            ));

            

        }
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

    public function dismiss_performance_info() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        update_option('wpspeedtestpro_performance_info_dismissed', true);
        wp_send_json_success();
    }


    public function ajax_performance_start_continuous_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
       if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        update_option('wpspeedtestpro_continuous_test_status', 'running');
        update_option('wpspeedtestpro_continuous_test_start_time', current_time('timestamp'));
        $this->schedule_continuous_test();

        wp_send_json_success();
    }

    public function ajax_performance_stop_continuous_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        update_option('wpspeedtestpro_continuous_test_status', 'stopped');
        wp_clear_scheduled_hook('wpspeedtestpro_continuous_test');

        wp_send_json_success();
    }

    public function ajax_performance_get_next_test_time() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
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
        if (get_option('wpspeedtestpro_continuous_test_status') === 'running') {
            if (!wp_next_scheduled('wpspeedtestpro_continuous_test')) {
                wp_schedule_event(time(), 'wpspeedtestpro_fifteen_minutes', 'wpspeedtestpro_continuous_test');
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
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'stopped';
        update_option('wpspeedtestpro_performance_test_status', $status);
        wp_send_json_success();
    }

    public function ajax_performance_run_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
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
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
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
            'speed_test' => $results['speed_test'],
            'industry_avg' => $industry_avg
        ));
    }

    private function run_performance_tests() {
        try {
            update_option('wpspeedtestpro_performance_test_status', 'running');
            
            $current_date = date('Y-m-d H:i:s');

            // Run existing performance tests
            $results = array(
                'latest_results' => array(
                    'test_date' => $current_date,  
                    'math' => $this->test_math(),
                    'string' => $this->test_string(),
                    'loops' => $this->test_loops(),
                    'conditionals' => $this->test_conditionals(),
                    'mysql' => $this->test_mysql(),
                    'wordpress_performance' => $this->test_wordpress_performance()
                )
            );
    
            // Run speed tests
            $speed_test = new Wpspeedtestpro_Speed_Test();
            $speed_results = $speed_test->run_speed_tests();
            if ($speed_results) {
                $results['latest_results']['speed_test'] = $speed_results;
            }
    
            $save_result = $this->save_test_results($results['latest_results']);
            if ($save_result !== true) {
                throw new Exception('Failed to save test results: ' . $save_result);
            }
    
            // Get historical results
            $results['math'] = $this->get_historical_results('math');
            $results['string'] = $this->get_historical_results('string');
            $results['loops'] = $this->get_historical_results('loops');
            $results['conditionals'] = $this->get_historical_results('conditionals');
            $results['mysql'] = $this->get_historical_results('mysql');
            $results['wordpress_performance'] = $this->get_historical_results('wordpress_performance');
            $results['speed_test'] = $this->get_historical_results('speed_test');
            $results['test_date'] = $current_date; 

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
                'test_date' => date('Y-m-d H:i:s'), 
                'math' => 0,
                'string' => 0,
                'loops' => 0,
                'conditionals' => 0,
                'mysql' => 0,
                'wordpress_performance' => array('time' => 0, 'queries' => 0),
                'speed_test' => array(
                    'upload_10k' => 0,
                    'upload_100k' => 0,
                    'upload_1mb' => 0,
                    'upload_10mb' => 0,
                    'download_10k' => 0,
                    'download_100k' => 0,
                    'download_1mb' => 0,
                    'download_10mb' => 0,
                    'ping_latency' => 0,
                    'ip_address' => '',
                    'location' => ''
                )
            ),
            'test_date' => date('Y-m-d H:i:s'),
            'math' => array(),
            'string' => array(),
            'loops' => array(),
            'conditionals' => array(),
            'mysql' => array(),
            'wordpress_performance' => array(),
            'speed_test' => array()
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
            ),
            'speed_tests' => array(
                'download' => array(
                    '10K' => array(
                        'excellent' => 100,
                        'good' => 50,
                        'average' => 25,
                        'poor' => 10,
                        'latency' => array(
                            'excellent' => 50,
                            'good' => 100,
                            'average' => 200,
                            'poor' => 300
                        )
                    ),
                    '100K' => array(
                        'excellent' => 95,
                        'good' => 45,
                        'average' => 20,
                        'poor' => 8,
                        'latency' => array(
                            'excellent' => 75,
                            'good' => 150,
                            'average' => 250,
                            'poor' => 350
                        )
                    ),
                    '1MB' => array(
                        'excellent' => 90,
                        'good' => 40,
                        'average' => 15,
                        'poor' => 5,
                        'latency' => array(
                            'excellent' => 100,
                            'good' => 200,
                            'average' => 300,
                            'poor' => 400
                        )
                    ),
                    '10MB' => array(
                        'excellent' => 85,
                        'good' => 35,
                        'average' => 10,
                        'poor' => 3,
                        'latency' => array(
                            'excellent' => 150,
                            'good' => 250,
                            'average' => 350,
                            'poor' => 450
                        )
                    )
                ),
                'upload' => array(
                    '10K' => array(
                        'excellent' => 50,
                        'good' => 25,
                        'average' => 10,
                        'poor' => 5,
                        'latency' => array(
                            'excellent' => 50,
                            'good' => 100,
                            'average' => 200,
                            'poor' => 300
                        )
                    ),
                    '100K' => array(
                        'excellent' => 45,
                        'good' => 20,
                        'average' => 8,
                        'poor' => 4,
                        'latency' => array(
                            'excellent' => 75,
                            'good' => 150,
                            'average' => 250,
                            'poor' => 350
                        )
                    ),
                    '1MB' => array(
                        'excellent' => 40,
                        'good' => 15,
                        'average' => 6,
                        'poor' => 3,
                        'latency' => array(
                            'excellent' => 100,
                            'good' => 200,
                            'average' => 300,
                            'poor' => 400
                        )
                    ),
                    '10MB' => array(
                        'excellent' => 35,
                        'good' => 10,
                        'average' => 4,
                        'poor' => 2,
                        'latency' => array(
                            'excellent' => 150,
                            'good' => 250,
                            'average' => 350,
                            'poor' => 450
                        )
                    )
                )
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
        
        // If the data has an 'industry_avg' key, use that
        if (isset($data['industry_avg'])) {
            $averages = $data['industry_avg'];
        } 
        // Otherwise, assume the whole data is the averages
        else {
            $averages = $data;
        }
        
        // Ensure all required keys are present
        foreach ($default_averages as $key => $value) {
            if (!isset($averages[$key])) {
                $averages[$key] = $default_averages[$key];
            }
        }
        
        return $averages;
    }


    private function save_test_results($results) {
        try {
            $this->core->db->insert_benchmark_result($results);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    private function get_historical_results($test_type, $limit = 30) {
        try {
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
                } elseif ($test_type === 'speed_test') {
                    return [
                        'test_date' => $result['test_date'],
                        'speed_test' => [
                            'upload_10k' => $result['upload_10k'],
                            'upload_100k' => $result['upload_100k'],
                            'upload_1mb' => $result['upload_1mb'],
                            'upload_10mb' => $result['upload_10mb'],
                            'download_10k' => $result['download_10k'],
                            'download_100k' => $result['download_100k'],
                            'download_1mb' => $result['download_1mb'],
                            'download_10mb' => $result['download_10mb'],
                            'ping_latency' => $result['ping_latency'],
                            'ip_address' => $result['ip_address'],
                            'location' => $result['location']
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


class Wpspeedtestpro_Speed_Test {
    private $trace_url = 'https://www.cloudflare.com/cdn-cgi/trace';
    private $upload_url = 'https://h3.speed.cloudflare.com/__up';
    private $download_url = 'https://h3.speed.cloudflare.com/__down';

    public function run_speed_tests() {
        $trace_info = $this->get_trace_info();
        if (!$trace_info) {
            return false;
        }

        $results = array(
            'ip_address' => $trace_info['ip'],
            'location' => $trace_info['loc'],
            'ping_latency' => $this->test_ping(),
            'upload_10k' => $this->test_upload(10000),
            'upload_100k' => $this->test_upload(100000),
            'upload_1mb' => $this->test_upload(1000000),
            'upload_10mb' => $this->test_upload(10000000),
            'download_10k' => $this->test_download(10000),
            'download_100k' => $this->test_download(100000),
            'download_1mb' => $this->test_download(1000000),
            'download_10mb' => $this->test_download(10000000)
        );

        return $results;
    }

    private function get_trace_info() {
        $response = wp_remote_get($this->trace_url);
        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $lines = explode("\n", $body);
        $trace_info = array();

        foreach ($lines as $line) {
            if (empty($line)) continue;
            list($key, $value) = explode('=', $line, 2);
            $trace_info[$key] = trim($value);
        }

        return $trace_info;
    }

    private function test_ping() {
        $start_time = microtime(true);
        $response = wp_remote_get($this->trace_url);
        $end_time = microtime(true);

        if (is_wp_error($response)) {
            return null;
        }

        return ($end_time - $start_time) * 1000; // Convert to milliseconds
    }

    private function test_upload($bytes) {
        $meas_id = uniqid();
        $data = str_repeat('X', $bytes);
        $url = $this->upload_url . "?measId={$meas_id}";

        $start_time = microtime(true);
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30
        ));
        $end_time = microtime(true);

        if (is_wp_error($response)) {
            return null;
        }

        $time_taken = $end_time - $start_time;
        return ($bytes / $time_taken) / 1000000; // Convert to MB/s
    }

    private function test_download($bytes) {
        $meas_id = uniqid();
        $url = $this->download_url . "?measId={$meas_id}&bytes={$bytes}";

        $start_time = microtime(true);
        $response = wp_remote_get($url, array(
            'timeout' => 30
        ));
        $end_time = microtime(true);

        if (is_wp_error($response)) {
            return null;
        }

        $time_taken = $end_time - $start_time;
        return ($bytes / $time_taken) / 1000000; // Convert to MB/s
    }
}
