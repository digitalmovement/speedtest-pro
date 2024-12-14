<?php

class Wpspeedtestpro_PageSpeed {
    private $plugin_name;
    private $version;
    private $core;
    private $pagespeed_table;
    private $pagespeed_scheduled_table;

    public function __construct($plugin_name, $version, $core) {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->pagespeed_table           = $wpdb->prefix . 'wpspeedtestpro_pagespeed_results';
        $this->pagespeed_scheduled_table = $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled';

        $this->init();
        $this->init_list_tables();

    }

     

    private function init() {
        // Admin AJAX handlers
        add_action('wp_ajax_pagespeed_run_test', array($this, 'ajax_run_test'));
        add_action('wp_ajax_pagespeed_get_test_status', array($this, 'ajax_get_test_status'));
        add_action('wp_ajax_pagespeed_cancel_scheduled_test', array($this, 'ajax_cancel_scheduled_test'));
        add_action('wp_ajax_pagespeed_delete_old_results', array($this, 'ajax_delete_old_results'));
        add_action('wp_ajax_pagespeed_get_latest_result', array($this, 'ajax_get_latest_result'));
        add_action('wp_ajax_pagespeed_get_scheduled_tests', array($this, 'ajax_get_scheduled_tests'));
        add_action('wp_ajax_pagespeed_get_test_results', array($this, 'ajax_get_test_results'));
        add_action('wp_ajax_pagespeed_check_test_status', array($this, 'ajax_check_test_status'));
        add_action('wp_ajax_pagespeed_run_scheduled_test', array($this, 'ajax_run_scheduled_test'));
        add_action('wp_ajax_pagespeed_check_scheduled_test_status', array($this, 'ajax_check_scheduled_test_status'));
        add_action('wp_ajax_pagespeed_get_test_details', array($this, 'ajax_get_test_details'));

        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
        add_filter('heartbeat_received', array($this, 'handle_heartbeat'), 10, 2);
    
 
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add meta box for pages and posts
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_meta_scripts'));

        // Schedule event for running tests
        add_action('wpspeedtestpro_check_scheduled_pagespeed_tests', array($this, 'handle_scheduled_pagespeed_tests'));
   
    }


    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speed-test-pro_page_wpspeedtestpro-page-speed-testing';    
        }
    }

    /**
     * Register the stylesheets for the page speed testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_style($this->plugin_name . '-page-speed-testing', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-page-speed-testing.css', array(), $this->version, 'all');

    }

    public function enqueue_scripts() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }

        wp_enqueue_script($this->plugin_name . '-page-speed-testing', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-page-speed-testing.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name . '-page-speed-testing', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));
    }


    public function enqueue_meta_scripts($hook) {
        
        // Only load on post/page edit screens
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
    
        wp_enqueue_style($this->plugin_name . '-page-speed-testing', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-page-speed-testing-meta.css', array(), $this->version, 'all');

        wp_enqueue_script($this->plugin_name . '-page-speed-testing', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-page-speed-testing-meta.js', array('jquery'), $this->version, false);

        wp_localize_script($this->plugin_name . '-page-speed-testing', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));

    }


    public function display_page_speed_testing() {
        
        include(plugin_dir_path(__FILE__) . 'partials/wpspeedtestpro-page-speed-testing-display.php');
    }

  public function ajax_run_test() {
    check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
        return;
    }

    $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
    $device = isset($_POST['device']) ? sanitize_text_field($_POST['device']) : 'desktop';
    $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'once';

    if (empty($url)) {
        wp_send_json_error('URL is required');
        return;
    }

    // Start tests based on device selection
    if ($device === 'both') {
        // Initiate both tests
        $desktop_test = $this->initiate_pagespeed_test($url, 'desktop');
        $mobile_test = $this->initiate_pagespeed_test($url, 'mobile');

        if (!$desktop_test['success'] || !$mobile_test['success']) {
            wp_send_json_error('Failed to initiate tests');
            return;
        }

        // Store test IDs in transient for status checking
        set_transient('pagespeed_test_' . md5($url), [
            'desktop_id' => $desktop_test['test_id'],
            'mobile_id' => $mobile_test['test_id'],
            'frequency' => $frequency,
            'url' => $url,
            'status' => 'running',
            'start_time' => time()
        ], 3600); // 1 hour expiration

    } else {
        // Initiate single device test
        $test = $this->initiate_pagespeed_test($url, $device);
        
        if (!$test['success']) {
            wp_send_json_error('Failed to initiate test');
            return;
        }

        set_transient('pagespeed_test_' . md5($url), [
            'test_id' => $test['test_id'],
            'device' => $device,
            'frequency' => $frequency,
            'url' => $url,
            'status' => 'running',
            'start_time' => time()
        ], 3600);
    }

    wp_send_json_success(['status' => 'initiated']);
}

public function ajax_check_test_status() {
    check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
        return;
    }

    $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
    if (empty($url)) {
        wp_send_json_error('URL is required');
        return;
    }

    $test_data = get_transient('pagespeed_test_' . md5($url));
    if (!$test_data) {
        wp_send_json_error('No test found for this URL');
        return;
    }

    // Check if test has been running too long
    if (time() - $test_data['start_time'] > 120) { // 2 minutes timeout
        delete_transient('pagespeed_test_' . md5($url));
        wp_send_json_error('Test timeout');
        return;
    }

    $results = [];
    $all_complete = true;

    if (isset($test_data['desktop_id']) && isset($test_data['mobile_id'])) {
        // Check both desktop and mobile tests
        $desktop_result = $this->check_test_result($test_data['desktop_id']);
        $mobile_result = $this->check_test_result($test_data['mobile_id']);

        if ($desktop_result['status'] === 'complete' && $mobile_result['status'] === 'complete') {
            $results['desktop'] = $desktop_result['data'];
            $results['mobile'] = $mobile_result['data'];

            // Save results to database
            $this->save_results($url, 'desktop', $desktop_result['data'], $desktop_result['raw_data']);
            $this->save_results($url, 'mobile', $mobile_result['data'], $mobile_result['raw_data']);

            // Schedule if needed
            if ($test_data['frequency'] !== 'once') {
                $this->schedule_test($url, $test_data['frequency']);
            }

            delete_transient('pagespeed_test_' . md5($url));
        } else {
            $all_complete = false;
        }
    } else {
        // Check single device test
        $result = $this->check_test_result($test_data['test_id']);
        
        if ($result['status'] === 'complete') {
            $results[$test_data['device']] = $result['data'];
            
            // Save results
            $this->save_results($url, $test_data['device'], $result['data'], $result['raw_data']);

            // Schedule if needed
            if ($test_data['frequency'] !== 'once') {
                $this->schedule_test($url, $test_data['frequency']);
            }

            delete_transient('pagespeed_test_' . md5($url));
        } else {
            $all_complete = false;
        }
    }

    if ($all_complete) {
        wp_send_json_success([
            'status' => 'complete',
            'results' => $results
        ]);
    } else {
        wp_send_json_success([
            'status' => 'running'
        ]);
    }
}

    /**
     * Delete old latency test results
     * 
     * @since 1.0.0
     */
    public function ajax_delete_old_results() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;

        if ($days < 1) {
            wp_send_json_error('Invalid number of days');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_hosting_benchmarking_results';
        
        // Calculate the date threshold
        $threshold_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Delete records older than the threshold
        $query = $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE test_time < %s",
            $threshold_date
        );

        $result = $wpdb->query($query);

        if ($result === false) {
            wp_send_json_error('Failed to delete old results');
            return;
        }

        wp_send_json_success(array(
            'message' => sprintf(
                'Successfully deleted results older than %d days (%d records deleted)', 
                $days, 
                $result
            )
        ));
    }

    /**
     * Cancel a scheduled test
     * 
     * @since 1.0.0
     */

    public function ajax_cancel_scheduled_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;

        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled';

        $result = $wpdb->delete(
            $table_name,
            array('id' => $schedule_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error('Failed to cancel scheduled test');
            return;
        }

        wp_send_json_success(array(
            'message' => 'Scheduled test cancelled successfully'
        ));
    }

    /**
 * Run scheduled tests via AJAX
 * 
 * @since 1.0.0
 */
    public function ajax_run_scheduled_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;

        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }

        global $wpdb;
        
        // Get the scheduled test details
        $scheduled_test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->pagespeed_scheduled_table} WHERE id = %d",
            $schedule_id
        ));

        if (!$scheduled_test) {
            wp_send_json_error('Scheduled test not found');
            return;
        }

        // Run the test for both desktop and mobile
        $desktop_test = $this->initiate_pagespeed_test($scheduled_test->url, 'desktop');
        $mobile_test = $this->initiate_pagespeed_test($scheduled_test->url, 'mobile');

        if (!$desktop_test['success'] || !$mobile_test['success']) {
            wp_send_json_error('Failed to initiate tests');
            return;
        }

        // Update the last run time
        $wpdb->update(
            $this->pagespeed_scheduled_table,
            array(
                'last_run' => current_time('mysql'),
                'next_run' => $this->calculate_next_run($scheduled_test->frequency)
            ),
            array('id' => $schedule_id),
            array('%s', '%s'),
            array('%d')
        );

        // Store test IDs in transient
        set_transient('pagespeed_scheduled_test_' . $schedule_id, [
            'desktop_id' => $desktop_test['test_id'],
            'mobile_id' => $mobile_test['test_id'],
            'url' => $scheduled_test->url,
            'status' => 'running',
            'start_time' => time()
        ], 3600);

        wp_send_json_success([
            'message' => 'Tests initiated successfully',
            'test_ids' => [
                'desktop' => $desktop_test['test_id'],
                'mobile' => $mobile_test['test_id']
            ]
        ]);
    }

    public function ajax_check_scheduled_test_status() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
    
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    
        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }
    
        $test_data = get_transient('pagespeed_scheduled_test_' . $schedule_id);
        
        if (!$test_data) {
            wp_send_json_error('No test found for this schedule');
            return;
        }
    
        // Check if test has been running too long (2 minutes timeout)
        if (time() - $test_data['start_time'] > 120) {
            delete_transient('pagespeed_scheduled_test_' . $schedule_id);
            wp_send_json_error('Test timeout');
            return;
        }
    
        $all_complete = true;
        $results = [];
    
        // Check desktop result
        $desktop_result = $this->check_test_result($test_data['desktop_id']);
        if ($desktop_result['status'] !== 'complete') {
            $all_complete = false;
        } else {
            $results['desktop'] = $desktop_result['data'];
        }
    
        // Check mobile result
        $mobile_result = $this->check_test_result($test_data['mobile_id']);
        if ($mobile_result['status'] !== 'complete') {
            $all_complete = false;
        } else {
            $results['mobile'] = $mobile_result['data'];
        }
    
        if ($all_complete) {
            // Save results
            $this->save_results($test_data['url'], 'desktop', $desktop_result['data'], $desktop_result['raw_data']);
            $this->save_results($test_data['url'], 'mobile', $mobile_result['data'], $mobile_result['raw_data']);
            
            // Clean up transient
            delete_transient('pagespeed_scheduled_test_' . $schedule_id);
            
            wp_send_json_success([
                'status' => 'complete',
                'results' => $results
            ]);
        } else {
            wp_send_json_success([
                'status' => 'running'
            ]);
        }
    }



    private function initiate_pagespeed_test($url, $device) {
        $api_key = get_option('wpspeedtestpro_pagespeed_api_key', '');
        $api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        
        $params = array(
            'url' => esc_url($url),
            'strategy' => $device,
            'category' => "ACCESSIBILITY&category=BEST_PRACTICES&category=PERFORMANCE&category=PWA&category=SEO"
        );

        if (!empty($api_key)) {
            $params['key'] = $api_key;
        }

        $request_url = add_query_arg($params, $api_url);
        error_log('PageSpeed API Request URL: ' . $request_url);

        // Increase timeout and configure request arguments
        $args = array(
            'timeout' => 60, // Increase timeout to 60 seconds
            'sslverify' => true,
            'headers' => array(
                'Accept' => 'application/json'
            ),
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        );

        $response = wp_remote_get($request_url, $args);

        if (is_wp_error($response)) {
            error_log('PageSpeed API Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('PageSpeed API HTTP Error: ' . $status_code);
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $status_code
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API response';
            error_log('PageSpeed API Response Error: ' . $error_message);
            return [
                'success' => false,
                'error' => $error_message
            ];
        }

        // Process successful response
        $result = [
            'id' => wp_generate_uuid4(),
            'data' => $data,
            'timestamp' => time()
        ];

        // Store the result in a transient
        set_transient('pagespeed_test_result_' . $result['id'], $result, 3600);

        return [
            'success' => true,
            'test_id' => $result['id']
        ];
    }

    public function ajax_get_test_details() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
    
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
    
        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
            return;
        }
    
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->pagespeed_table} WHERE id = %d",
            $test_id
        ));
    
        if (!$result) {
            wp_send_json_error('Test result not found');
            return;
        }
    
        // Get the full report data
        $full_report = json_decode($result->full_report, true);
    
        // Helper function to clean text
        function clean_text($text) {
            // Remove markdown bold syntax
            $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
            
            // Remove markdown links but keep the text
            $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);
            
            // Remove markdown backticks
            $text = str_replace('`', '', $text);
            
            // Remove markdown list symbols
            $text = preg_replace('/^\s*[\-\*]\s+/m', '', $text);
            
            // Remove HTML comments
            $text = preg_replace('/<!--.*?-->/s', '', $text);
            
            // Convert HTML entities to their characters
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Strip any remaining HTML tags
            $text = strip_tags($text);
            
            // Remove extra whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            
            // Trim
            $text = trim($text);
            
            return $text;
        }
    
        // Clean up audits
        $audits = $full_report['lighthouseResult']['audits'] ?? [];
        foreach ($audits as $key => &$audit) {
            if (isset($audit['title'])) {
                $audit['title'] = clean_text($audit['title']);
            }
            if (isset($audit['description'])) {
                $audit['description'] = clean_text($audit['description']);
            }
            if (isset($audit['displayValue'])) {
                $audit['displayValue'] = clean_text($audit['displayValue']);
            }
        }
        unset($audit); // Break the reference
    
        // Format the response with detailed metrics
        $response = [
            'basic_info' => [
                'url' => esc_url($result->url),
                'device' => ucfirst($result->device),
                'test_date' => wp_date('F j, Y g:i a', strtotime($result->test_date))
            ],
            'scores' => [
                'performance' => [
                    'score' => $result->performance_score,
                    'class' => $this->get_score_class($result->performance_score)
                ],
                'accessibility' => [
                    'score' => $result->accessibility_score,
                    'class' => $this->get_score_class($result->accessibility_score)
                ],
                'best_practices' => [
                    'score' => $result->best_practices_score,
                    'class' => $this->get_score_class($result->best_practices_score)
                ],
                'seo' => [
                    'score' => $result->seo_score,
                    'class' => $this->get_score_class($result->seo_score)
                ]
            ],
            'metrics' => [
                'First Contentful Paint' => $this->format_timing($result->fcp),
                'Largest Contentful Paint' => $this->format_timing($result->lcp),
                'Cumulative Layout Shift' => number_format($result->cls, 3),
                'Total Blocking Time' => $this->format_timing($result->tbt),
                'Speed Index' => $this->format_timing($result->si),
                'Time to Interactive' => $this->format_timing($result->tti)
            ],
            'audits' => $audits
        ];
    
        wp_send_json_success($response);
    }    private function check_test_result($test_id) {
        $result = get_transient('pagespeed_test_result_' . $test_id);
        
        if (!$result) {
            return [
                'status' => 'error',
                'error' => 'Test result not found'
            ];
        }

        // Process the stored result
        $data = $result['data'];
        $parsed_results = $this->parse_results($data);

        return [
            'status' => 'complete',
            'data' => $parsed_results,
            'raw_data' => $data
        ];
    }

    private function parse_results($data) {
        try {
            $lighthouse = $data['lighthouseResult'];
            $categories = $lighthouse['categories'];
            $audits = $lighthouse['audits'];

            return [
                'performance_score' => round($categories['performance']['score'] * 100),
                'accessibility_score' => round($categories['accessibility']['score'] * 100),
                'best_practices_score' => round($categories['best-practices']['score'] * 100),
                'seo_score' => round($categories['seo']['score'] * 100),
                'fcp' => isset($audits['first-contentful-paint']['numericValue']) ? 
                        $audits['first-contentful-paint']['numericValue'] : null,
                'lcp' => isset($audits['largest-contentful-paint']['numericValue']) ? 
                        $audits['largest-contentful-paint']['numericValue'] : null,
                'cls' => isset($audits['cumulative-layout-shift']['numericValue']) ? 
                        $audits['cumulative-layout-shift']['numericValue'] : null,
                'si' => isset($audits['speed-index']['numericValue']) ? 
                        $audits['speed-index']['numericValue'] : null,
                'tti' => isset($audits['interactive']['numericValue']) ? 
                        $audits['interactive']['numericValue'] : null,
                'tbt' => isset($audits['total-blocking-time']['numericValue']) ? 
                        $audits['total-blocking-time']['numericValue'] : null
            ];
        } catch (Exception $e) {
            error_log('Error parsing PageSpeed results: ' . $e->getMessage());
            error_log('Raw data: ' . print_r($data, true));
            return null;
        }
    }
    private function save_results($url, $device, $results, $full_data) {
        global $wpdb;

        $data = array_merge(
            array(
                'url' => $url,
                'device' => $device,
                'test_date' => current_time('mysql'),
                'full_report' => json_encode($full_data)
            ),
            $results
        );

        return $wpdb->insert($this->pagespeed_table, $data);
    }

    public function get_latest_result($url, $device = 'both') {
        global $wpdb;

        if ($device === 'both') {
            $results = array();
            
            // Get desktop result
            $desktop = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->pagespeed_table} 
                WHERE url = %s AND device = 'desktop' 
                ORDER BY test_date DESC LIMIT 1",
                $url
            ));

            // Get mobile result
            $mobile = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->pagespeed_table} 
                WHERE url = %s AND device = 'mobile' 
                ORDER BY test_date DESC LIMIT 1",
                $url
            ));

            return array(
                'desktop' => $desktop,
                'mobile' => $mobile
            );
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->pagespeed_table} 
            WHERE url = %s AND device = %s 
            ORDER BY test_date DESC LIMIT 1",
            $url,
            $device
        ));
    }

    public function schedule_test($url, $frequency) {
        global $wpdb;
        
        $next_run = $this->calculate_next_run($frequency);
        
        return $wpdb->insert(
            $this->pagespeed_scheduled_table,
            array(
                'url' => $url,
                'frequency' => $frequency,
                'last_run' => current_time('mysql'),
                'next_run' => $next_run
            )
        );
    }

    private function calculate_next_run($frequency) {
        $now = current_time('mysql');
        
        switch ($frequency) {
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day', strtotime($now)));
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week', strtotime($now)));
            default:
                return null;
        }
    }

    public function handle_post_status_change($new_status, $old_status, $post) {
        // Only proceed if transitioning to published
        if ($new_status === 'publish' && $old_status !== 'publish') {
            // Get the post URL
            $url = get_permalink($post);
            
            // Store this information for the JavaScript to pick up
            update_post_meta($post->ID, '_pagespeed_test_enabled', true);
            update_post_meta($post->ID, '_pagespeed_test_url', $url);
        }
    }
    
    public function handle_heartbeat($response, $data) {
        if (!empty($data['check_post_status'])) {
            // Get current post ID
            $post_id = get_the_ID();
            if ($post_id) {
                $post_status = get_post_status($post_id);
                $response['post_status'] = $post_status;
                
                if ($post_status === 'publish') {
                    $response['post_url'] = get_permalink($post_id);
                }
            }
        }
        return $response;
    }

    

    public function add_meta_box() {
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'pagespeed_results',
                'PageSpeed Results',
                array($this, 'render_meta_box'),
                $post_type,
                'side'
            );
        }
    }

    public function render_meta_box($post) {
        // Get post status and URL
        $post_status = get_post_status($post);
        $url = get_permalink($post->ID);
        $test_enabled = get_post_meta($post->ID, '_pagespeed_test_enabled', true);
     
        // Get latest results
        $results = $this->get_latest_result($url, 'both');
        $has_results = !empty($results['desktop']) || !empty($results['mobile']);
    
        wp_nonce_field('wpspeedtestpro_ajax_nonce', 'wpspeedtestpro_ajax_nonce'); 
        ?>
        <div class="pagespeed-meta-box" 
             data-post-id="<?php echo esc_attr($post->ID); ?>"
             data-test-enabled="<?php echo esc_attr($test_enabled ? '1' : '0'); ?>">
    
            <div class="test-status" style="display: none;"></div>
            
            <div class="results-grid" <?php if (!$has_results): ?> style="display: none;" <?php endif; ?>>
                <!-- Desktop Results -->
                <div class="device-results">
                    <h4>Desktop</h4>
                    <div class="scores-grid">
                        <div class="score-item">
                            <span class="score-label">Performance</span>
                            <div class="score <?php echo $this->get_score_class($results['desktop']->performance_score ?? 0); ?>">
                                <?php echo isset($results['desktop']->performance_score) ? $results['desktop']->performance_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Accessibility</span>
                            <div class="score <?php echo $this->get_score_class($results['desktop']->accessibility_score ?? 0); ?>">
                                <?php echo isset($results['desktop']->accessibility_score) ? $results['desktop']->accessibility_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Best Practices</span>
                            <div class="score <?php echo $this->get_score_class($results['desktop']->best_practices_score ?? 0); ?>">
                                <?php echo isset($results['desktop']->best_practices_score) ? $results['desktop']->best_practices_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">SEO</span>
                            <div class="score <?php echo $this->get_score_class($results['desktop']->seo_score ?? 0); ?>">
                                <?php echo isset($results['desktop']->seo_score) ? $results['desktop']->seo_score . '%' : '--'; ?>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Mobile Results -->
                <div class="device-results">
                    <h4>Mobile</h4>
                    <div class="scores-grid">
                        <div class="score-item">
                            <span class="score-label">Performance</span>
                            <div class="score <?php echo $this->get_score_class($results['mobile']->performance_score ?? 0); ?>">
                                <?php echo isset($results['mobile']->performance_score) ? $results['mobile']->performance_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Accessibility</span>
                            <div class="score <?php echo $this->get_score_class($results['mobile']->accessibility_score ?? 0); ?>">
                                <?php echo isset($results['mobile']->accessibility_score) ? $results['mobile']->accessibility_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Best Practices</span>
                            <div class="score <?php echo $this->get_score_class($results['mobile']->best_practices_score ?? 0); ?>">
                                <?php echo isset($results['mobile']->best_practices_score) ? $results['mobile']->best_practices_score . '%' : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">SEO</span>
                            <div class="score <?php echo $this->get_score_class($results['mobile']->seo_score ?? 0); ?>">
                                <?php echo isset($results['mobile']->seo_score) ? $results['mobile']->seo_score . '%' : '--'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <?php if ($post_status !== 'publish'): ?>
                <div class="notice notice-warning inline">
                    <p>Please publish this post before running PageSpeed tests.</p>
                </div>
            <?php endif; ?>
    
            <button type="button" class="button run-pagespeed-test" 
                    data-url="<?php echo esc_attr($url); ?>"
                    data-post-status="<?php echo esc_attr($post_status); ?>"
                    <?php echo $post_status !== 'publish' ? 'disabled' : ''; ?>>
                Run PageSpeed Test
            </button>
        </div>
        <?php
    }

    public function ajax_get_scheduled_tests() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
    
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$this->pagespeed_scheduled_table} 
            ORDER BY next_run ASC"
        );
    
        if ($results === false) {
            wp_send_json_error('Database error occurred');
            return;
        }
    
        $formatted_results = array_map(function($test) {
            return array(
                'id' => $test->id,
                'url' => $test->url,
                'frequency' => $test->frequency,
                'last_run' => $test->last_run ? wp_date('F j, Y g:i a', strtotime($test->last_run)) : 'Never',
                'next_run' => $test->next_run ? wp_date('F j, Y g:i a', strtotime($test->next_run)) : 'Not scheduled',
                'status' => $this->get_schedule_status($test)
            );
        }, $results);
    
        wp_send_json_success($formatted_results);
    }
    
    /**
     * Helper function to determine schedule status
     */
    private function get_schedule_status($test) {
        if (empty($test->next_run)) {
            return 'inactive';
        }
    
        $next_run = strtotime($test->next_run);
        $now = current_time('timestamp');
    
        if ($next_run < $now) {
            return 'overdue';
        }
    
        return 'active';
    }


    /**
     * Add this to the Wpspeedtestpro_PageSpeed class
     */
    
    public function ajax_get_test_results() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
    
        global $wpdb;
        
        // Get the page number and results per page
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $offset = ($page - 1) * $per_page;
    
        // Get total count for pagination
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$this->pagespeed_table}");
    
        // Get results with pagination
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->pagespeed_table}
                ORDER BY test_date DESC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
    
        if ($results === false) {
            wp_send_json_error('Database error occurred');
            return;
        }
    
        $formatted_results = array_map(function($test) {
            // Format scores with color indicators
            $performance = [
                'score' => $test->performance_score,
                'class' => $this->get_score_class($test->performance_score)
            ];
    
            $accessibility = [
                'score' => $test->accessibility_score,
                'class' => $this->get_score_class($test->accessibility_score)
            ];
    
            $best_practices = [
                'score' => $test->best_practices_score,
                'class' => $this->get_score_class($test->best_practices_score)
            ];
    
            $seo = [
                'score' => $test->seo_score,
                'class' => $this->get_score_class($test->seo_score)
            ];
    
            // Format Core Web Vitals
            $core_vitals = [
                'fcp' => $this->format_timing($test->fcp),
                'lcp' => $this->format_timing($test->lcp),
                'cls' => number_format($test->cls, 3),
                'tbt' => $this->format_timing($test->tbt),
                'si' => $this->format_timing($test->si),
                'tti' => $this->format_timing($test->tti)
            ];
    
            return [
                'id' => $test->id,
                'url' => $test->url,
                'device' => ucfirst($test->device),
                'test_date' => wp_date('F j, Y g:i a', strtotime($test->test_date)),
                'performance' => $performance,
                'accessibility' => $accessibility,
                'best_practices' => $best_practices,
                'seo' => $seo,
                'core_vitals' => $core_vitals,
                'full_report' => json_decode($test->full_report, true)
            ];
        }, $results);
    
        $response = [
            'results' => $formatted_results,
            'pagination' => [
                'total_items' => (int) $total_items,
                'total_pages' => ceil($total_items / $per_page),
                'current_page' => $page,
                'per_page' => $per_page
            ]
        ];
    
        wp_send_json_success($response);
    }
    
    /**
     * Helper function to determine score class
     */
    private function get_score_class($score) {
        if ($score >= 90) {
            return 'good';
        } elseif ($score >= 50) {
            return 'average';
        }
        return 'poor';
    }
    
    /**
     * Helper function to format timing values
     */
    private function format_timing($value) {
        if ($value === null) {
            return 'N/A';
        }
    
        // Convert to seconds if greater than 1000ms
        if ($value >= 1000) {
            return number_format($value / 1000, 2) . 's';
        }
    
        // Keep as milliseconds if less than 1000ms
        return number_format($value, 0) . 'ms';
    }
    
    /**
     * Add this handler to the init() function
     */

        // ... existing init code ...
       
    /**
     * Helper function to get summary statistics
     */
    public function get_summary_stats($url = null) {
        global $wpdb;
    
        $where = $url ? $wpdb->prepare("WHERE url = %s", $url) : "";
    
        $stats = $wpdb->get_row("
            SELECT 
                AVG(performance_score) as avg_performance,
                AVG(accessibility_score) as avg_accessibility,
                AVG(best_practices_score) as avg_best_practices,
                AVG(seo_score) as avg_seo,
                MIN(performance_score) as min_performance,
                MAX(performance_score) as max_performance,
                COUNT(*) as total_tests
            FROM {$this->pagespeed_table}
            {$where}
        ");
    
        if ($stats) {
            return [
                'averages' => [
                    'performance' => round($stats->avg_performance),
                    'accessibility' => round($stats->avg_accessibility),
                    'best_practices' => round($stats->avg_best_practices),
                    'seo' => round($stats->avg_seo)
                ],
                'performance_range' => [
                    'min' => $stats->min_performance,
                    'max' => $stats->max_performance
                ],
                'total_tests' => $stats->total_tests
            ];
        }
    
        return null;
    }
    
    /**
     * Get trend data for a specific metric
     */
    public function get_trend_data($metric, $days = 30, $url = null) {
        global $wpdb;
    
        $where = ['1=1'];
        if ($url) {
            $where[] = $wpdb->prepare("url = %s", $url);
        }
    
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $where[] = $wpdb->prepare("test_date >= %s", $date_limit);
    
        $where_clause = "WHERE " . implode(" AND ", $where);
    
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(test_date) as date,
                AVG(%s) as value
            FROM {$this->pagespeed_table}
            {$where_clause}
            GROUP BY DATE(test_date)
            ORDER BY date ASC",
            $metric
        ));
    }

    /**
     * Handle scheduled tests
     */
    public function handle_scheduled_pagespeed_tests() {
        global $wpdb;

        // Get all tests that need to be run
        $scheduled_tests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->pagespeed_scheduled_table} 
                WHERE next_run <= %s 
                AND (last_run IS NULL OR last_run != %s)",
                current_time('mysql'),
                current_time('mysql', 'DATE')
            )
        );

        error_log('Speed Scheduled tests found: ' . count($scheduled_tests));

        if (empty($scheduled_tests)) {
            return;
        }

        foreach ($scheduled_tests as $test) {
            // Check if we've already run this test today
//            if ($test->last_run && date('Y-m-d', strtotime($test->last_run)) === current_time('Y-m-d')) {
//                continue;
//            }

            // Run tests for both desktop and mobile
            $desktop_test = $this->initiate_pagespeed_test($test->url, 'desktop');
            $mobile_test = $this->initiate_pagespeed_test($test->url, 'mobile');

            if ($desktop_test['success'] && $mobile_test['success']) {
                // Check desktop results
                $desktop_result = $this->check_test_result($desktop_test['test_id']);
                if ($desktop_result['status'] === 'complete') {
                    $this->save_results($test->url, 'desktop', $desktop_result['data'], $desktop_result['raw_data']);
                }

                // Check mobile results
                $mobile_result = $this->check_test_result($mobile_test['test_id']);
                if ($mobile_result['status'] === 'complete') {
                    $this->save_results($test->url, 'mobile', $mobile_result['data'], $mobile_result['raw_data']);
                }

                // Update last run and next run times
                $wpdb->update(
                    $this->pagespeed_scheduled_table,
                    array(
                        'last_run' => current_time('mysql'),
                        'next_run' => $this->calculate_next_run($test->frequency)
                    ),
                    array('id' => $test->id),
                    array('%s', '%s'),
                    array('%d')
                );

                // Log the successful test
                error_log(sprintf(
                    'PageSpeed scheduled test completed for URL: %s, Schedule ID: %d',
                    $test->url,
                    $test->id
                ));
            } else {
                // Log the error
                error_log(sprintf(
                    'PageSpeed scheduled test failed for URL: %s, Schedule ID: %d',
                    $test->url,
                    $test->id
                ));
            }

            // Add a small delay between tests to avoid API rate limits
            sleep(2);
        }
    }



        /**
         * Initialize list table functionality
         */
        private function init_list_tables() {
            // Add columns to post types
            add_filter('manage_posts_columns', array($this, 'add_pagespeed_column'));
            add_filter('manage_pages_columns', array($this, 'add_pagespeed_column'));
            
            // Populate columns for post types
            add_action('manage_posts_custom_column', array($this, 'populate_pagespeed_column'), 10, 2);
            add_action('manage_pages_custom_column', array($this, 'populate_pagespeed_column'), 10, 2);
            
            // Add column styles
            add_action('admin_head', array($this, 'add_column_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_list_scripts'));

        }

        /**
         * Add PageSpeed column to post/page list tables
         */
        public function add_pagespeed_column($columns) {
            $columns['pagespeed_score'] = 'PageSpeed';
            return $columns;
        }

        /**
         * Add styles for the traffic light system
         */
        public function add_column_styles() {
            ?>
            <style>
                .column-pagespeed_score {
                    width: 140px;
                }
                .pagespeed-indicator {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 5px;
                    vertical-align: middle;
                }
                .pagespeed-score {
                    display: inline-block;
                    vertical-align: middle;
                }
                .pagespeed-indicator.no-test {
                    background-color: #ccc;
                }
                .pagespeed-indicator.good {
                    background-color: #0a0;
                }
                .pagespeed-indicator.average {
                    background-color: #fa3;
                }
                .pagespeed-indicator.poor {
                    background-color: #e33;
                }
                .pagespeed-device {
                    display: inline-block;
                    margin-right: 10px;
                    white-space: nowrap;
                }
                .pagespeed-device i {
                    width: 16px;
                    margin-right: 4px;
                    color: #666;
                }
                .pagespeed-score {
                    font-weight: 500;
                }
                .quick-test-button {
                    padding: 2px 8px;
                    font-size: 11px;
                    margin-top: 4px;
                    display: inline-block;
                }
                .pagespeed-test-status {
                    color: #666;
                    font-style: italic;
                    display: inline-block;
                    margin-left: 5px;
                    vertical-align: middle;
                }
                .pagespeed-test-status .spinner {
                    float: none;
                    margin: 0 4px 0 0;
                }
                .pagespeed-scores {
                    white-space: nowrap;
                }
            </style>
            <?php
        }
        /**
         * Populate PageSpeed column with traffic light indicators and quick test button
         */
        public function populate_pagespeed_column($column_name, $post_id) {
            if ($column_name !== 'pagespeed_score') {
                return;
            }
        
            $url = get_permalink($post_id);
            $results = $this->get_latest_result($url, 'both');
        
            echo '<div class="pagespeed-scores" data-post-id="' . esc_attr($post_id) . '" data-url="' . esc_attr($url) . '">';
            
            if (empty($results['desktop']) && empty($results['mobile'])) {
                echo $this->render_indicator('no-test', 'No test', true);
            } else {
                // Display Desktop Score
                if (!empty($results['desktop'])) {
                    $desktop_score = $results['desktop']->performance_score;
                    $desktop_class = $this->get_score_class($desktop_score);
                    echo '<div class="pagespeed-device">';
                    echo $this->render_indicator($desktop_class, $desktop_score, false, 'desktop');
                    echo '</div>';
                }
        
                // Display Mobile Score
                if (!empty($results['mobile'])) {
                    $mobile_score = $results['mobile']->performance_score;
                    $mobile_class = $this->get_score_class($mobile_score);
                    echo '<div class="pagespeed-device">';
                    echo $this->render_indicator($mobile_class, $mobile_score, false, 'mobile');
                    echo '</div>';
                }
            }
        
            // Add quick test button and status
            echo '<button type="button" class="button button-small quick-test-button">Run Test</button>';
            echo '<div class="pagespeed-test-status"></div>';
            echo '</div>';
        }
        
        
        /**
         * Helper function to render traffic light indicator
         */
        private function render_indicator($class, $score, $is_no_test = false, $device = '') {
            if ($is_no_test) {
                return sprintf(
                    '<span class="pagespeed-indicator %s"></span><span class="pagespeed-score no-test-text">%s</span>',
                    esc_attr($class),
                    'No test'
                );
            }
    
            $icon_class = $device === 'desktop' ? 'fa-desktop' : 'fa-mobile-screen';
            
            return sprintf(
                '<i class="fas %s"></i><span class="pagespeed-indicator %s"></span><span class="pagespeed-score">%s%%</span>',
                esc_attr($icon_class),
                esc_attr($class),
                esc_html($score)
            );
        }
    

        public function enqueue_list_scripts($hook) {
            if (!in_array($hook, ['edit.php', 'edit-pages.php'])) {
                return;
            }
        
            wp_enqueue_script(
                'wpspeedtestpro-list-testing',
                plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-list-testing.js',
                array('jquery'),
                $this->version,
                true
            );
        
            wp_localize_script('wpspeedtestpro-list-testing', 'wpspeedtestpro_list', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
            ));
        }
} // end class