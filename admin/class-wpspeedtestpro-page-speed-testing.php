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
            return $screen && $screen->id === 'wp-speedtest-pro_page_wpspeedtestpro-page-speed-testing';    
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

    $url = isset($_POST['url']) ? esc_url_raw(sanitize_url(wp_unslash($_POST['url']))) : '';
    $device = isset($_POST['device']) ? sanitize_text_field(wp_unslash($_POST['device'])) : 'desktop';
    $frequency = isset($_POST['frequency']) ? sanitize_text_field(wp_unslash($_POST['frequency'])) : 'once';

    if (empty($url)) {
        wp_send_json_error('URL is required');
        return;
    }

    if (!wp_http_validate_url($url)) {
        wp_send_json_error('Invalid URL format');
        return;
    }


    // Start tests based on device selection
    if ($device === 'both') {
        // Initiate both tests
        $desktop_test = $this->initiate_pagespeed_test($url, 'desktop');
        $mobile_test = $this->initiate_pagespeed_test($url, 'mobile');

        if (!$desktop_test['success'] || !$mobile_test['success']) {
            $error_message = sprintf(
                /* translators: 1: Desktop test error message  */
                __('Failed to initiate tests - %s', 'wpspeedtestpro'),
                isset($desktop_test['error']) ? sanitize_text_field($desktop_test['error']) : ''
            );
            
            // Check if the error message contains 429 (Too Many Requests)
            if (isset($desktop_test['error']) && strpos($desktop_test['error'], '429') !== false) {
                $error_message .= ' - ' . __('Your website has been restricted by Google, please enter a PageSpeed Insight API key in the setting page.', 'wpspeedtestpro');
            }
            
            wp_send_json_error($error_message);
            return;
        }

        // Store test IDs in transient for status checking
        set_transient('wpspeedtestpro_pagespeed_test_' . md5($url), [
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

        set_transient('wpspeedtestpro_pagespeed_test_' . md5($url), [
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

    $url = isset($_POST['url']) ? sanitize_url(wp_unslash($_POST['url'])) : '';
    if (empty($url)) {
        wp_send_json_error('URL is required');
        return;
    }

    $test_data = get_transient('wpspeedtestpro_pagespeed_test_' . md5($url));
    if (!$test_data) {
        wp_send_json_error('No test found for this URL');
        return;
    }

    // Check if test has been running too long
    if (time() - $test_data['start_time'] > 120) { // 2 minutes timeout
        delete_transient('wpspeedtestpro_pagespeed_test_' . md5($url));
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

            delete_transient('wpspeedtestpro_pagespeed_test_' . md5($url));
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

            delete_transient('wpspeedtestpro_pagespeed_test_' . md5($url));
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

        $days = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 30;

        if ($days < 1) {
            wp_send_json_error('Invalid number of days');
            return;
        }

        global $wpdb;
        
        // Calculate the date threshold
        $threshold_date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Delete records older than the threshold
     
        // phpcs:ignore 
        $results = $wpdb->get_results($wpdb->prepare(
            "DELETE FROM %i WHERE test_date < %s",
            $this->pagespeed_table,
            $threshold_date
        ));

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

        $schedule_id = isset($_POST['schedule_id']) ? intval(wp_unslash($_POST['schedule_id'])) : 0;

        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

        $schedule_id = isset($_POST['schedule_id']) ? intval(wp_unslash($_POST['schedule_id'])) : 0;

        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }

        global $wpdb;
        
        // Get the scheduled test details
        $cache_key = 'wpspeedtestpro_scheduled_test_' . $schedule_id;
        $scheduled_test = wp_cache_get($cache_key);
        
        if (false === $scheduled_test) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $scheduled_test = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM %i WHERE id = %d",
                $this->pagespeed_scheduled_table,
                $schedule_id
            ));
            
            if ($scheduled_test) {
                wp_cache_set($cache_key, $scheduled_test, '', 3600);
            }
        }

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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
    
        $schedule_id = isset($_POST['schedule_id']) ? intval(wp_unslash($_POST['schedule_id'])) : 0;
    
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

            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if (($status_code !== 200) && ($status_code !== 400)) {
            
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $status_code
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API response';

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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching    
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            $this->pagespeed_table,
            $test_id
        ));
    
        if (!$result) {
            wp_send_json_error('Test result not found');
            return;
        }
    
        // Get the full report data - safely decode JSON with strict type checking
        $full_report = json_decode($result->full_report, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid report format');
            return;
        }

        // Define clean_text function inside a closure to avoid global namespace pollution
        $clean_text = function($text) {
            if (!is_string($text)) {
                return '';
            }
            
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
            $text = wp_strip_all_tags($text);
            
            // Remove extra whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            
            // Trim and sanitize
            return sanitize_text_field(trim($text));
        };
    
        // Clean up audits with proper sanitization
        $audits = isset($full_report['lighthouseResult']['audits']) && is_array($full_report['lighthouseResult']['audits']) 
            ? $full_report['lighthouseResult']['audits'] 
            : [];
            
        $sanitized_audits = [];
        foreach ($audits as $key => $audit) {
            if (!is_array($audit)) {
                continue;
            }
            
            $sanitized_audits[$key] = [
                'title' => isset($audit['title']) ? $clean_text($audit['title']) : '',
                'description' => isset($audit['description']) ? $clean_text($audit['description']) : '',
                'displayValue' => isset($audit['displayValue']) ? $clean_text($audit['displayValue']) : '',
                'score' => isset($audit['score']) && is_numeric($audit['score']) ? floatval($audit['score']) : 0
            ];
        }
    
        // Format the response with detailed metrics - all values properly sanitized
        $response = [
            'basic_info' => [
                'url' => esc_url($result->url),
                'device' => ucfirst(sanitize_text_field($result->device)),
                'test_date' => gmdate('F j, Y g:i a', strtotime($result->test_date))
            ],
            'scores' => [
                'performance' => [
                    'score' => intval($result->performance_score),
                    'class' => sanitize_html_class($this->get_score_class($result->performance_score))
                ],
                'accessibility' => [
                    'score' => intval($result->accessibility_score),
                    'class' => sanitize_html_class($this->get_score_class($result->accessibility_score))
                ],
                'best_practices' => [
                    'score' => intval($result->best_practices_score),
                    'class' => sanitize_html_class($this->get_score_class($result->best_practices_score))
                ],
                'seo' => [
                    'score' => intval($result->seo_score),
                    'class' => sanitize_html_class($this->get_score_class($result->seo_score))
                ]
            ],
            'metrics' => [
                'First Contentful Paint' => $this->format_timing(floatval($result->fcp)),
                'Largest Contentful Paint' => $this->format_timing(floatval($result->lcp)),
                'Cumulative Layout Shift' => number_format(floatval($result->cls), 3),
                'Total Blocking Time' => $this->format_timing(floatval($result->tbt)),
                'Speed Index' => $this->format_timing(floatval($result->si)),
                'Time to Interactive' => $this->format_timing(floatval($result->tti))
            ],
            'audits' => $sanitized_audits
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

            return null;
        }
    }
    private function save_results($url, $device, $results, $full_data) {
        global $wpdb;

        // Sanitize inputs before saving to database
        $sanitized_url = esc_url_raw($url);
        $sanitized_device = sanitize_text_field($device);
        
        // Ensure results is an array with expected structure
        $sanitized_results = [];
        if (is_array($results)) {
            // Sanitize numeric values
            foreach (['performance_score', 'accessibility_score', 'best_practices_score', 'seo_score'] as $score) {
                $sanitized_results[$score] = isset($results[$score]) ? intval($results[$score]) : 0;
            }
            
            // Sanitize float values
            foreach (['fcp', 'lcp', 'tbt', 'si', 'tti'] as $metric) {
                $sanitized_results[$metric] = isset($results[$metric]) ? floatval($results[$metric]) : 0;
            }
            
            // Special handling for CLS which should be a small decimal
            $sanitized_results['cls'] = isset($results['cls']) ? floatval($results['cls']) : 0;
        }

        $data = array_merge(
            array(
                'url' => $sanitized_url,
                'device' => $sanitized_device,
                'test_date' => current_time('mysql'),
                'full_report' => json_encode($full_data)
            ),
            $sanitized_results
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($this->pagespeed_table, $data);
        
        // Clear any cached results for this URL
        wp_cache_delete('wpspeedtestpro_pagespeed_' . md5($data['url'] . '_desktop'), '');
        wp_cache_delete('wpspeedtestpro_pagespeed_' . md5($data['url'] . '_mobile'), '');
        
        return $result;
    }

    public function get_latest_result($url, $device = 'both') {
        global $wpdb;

        if ($device === 'both') {
            $results = array();
            
            // Get desktop result
            $cache_key = 'wpspeedtestpro_pagespeed_' . md5($url . '_desktop');
            $desktop = wp_cache_get($cache_key);
            
            if (false === $desktop) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $desktop = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM %i  
                    WHERE url = %s AND device = 'desktop' 
                    ORDER BY test_date DESC LIMIT 1",
                    $this->pagespeed_table,
                    $url
                ));
                
                if ($desktop) {
                    wp_cache_set($cache_key, $desktop, '', 3600); // Cache for 1 hour
                }
            }

            // Get mobile result
            $cache_key = 'wpspeedtestpro_pagespeed_' . md5($url . '_mobile');
            $mobile = wp_cache_get($cache_key);
            
            if (false === $mobile) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $mobile = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM %i 
                    WHERE url = %s AND device = 'mobile' 
                    ORDER BY test_date DESC LIMIT 1",
                    $this->pagespeed_table,
                    $url
                ));
                
                if ($mobile) {
                    wp_cache_set($cache_key, $mobile, '', 3600); // Cache for 1 hour
                }
            }

            return array(
                'desktop' => $desktop,
                'mobile' => $mobile
            );
        }

        $cache_key = 'wpspeedtestpro_pagespeed_' . md5($url . '_' . $device);
        $result = wp_cache_get($cache_key);
        
        if (false === $result) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM %i 
                WHERE url = %s AND device = %s 
                ORDER BY test_date DESC LIMIT 1",
                $this->pagespeed_table,
                $url,
                $device
            ));
            
            if ($result) {
                wp_cache_set($cache_key, $result, '', 3600); // Cache for 1 hour
            }
        }
        
        return $result;
    }

    public function schedule_test($url, $frequency) {
        global $wpdb;
        
        $next_run = $this->calculate_next_run($frequency);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
                return gmdate('Y-m-d H:i:s', strtotime('+1 day', strtotime($now)));
            case 'weekly':
                return gmdate('Y-m-d H:i:s', strtotime('+1 week', strtotime($now)));
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
                            <div class="score <?php echo esc_attr($this->get_score_class($results['desktop']->performance_score ?? 0)); ?>">
                                <?php echo isset($results['desktop']->performance_score) ? esc_html($results['desktop']->performance_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Accessibility</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['desktop']->accessibility_score ?? 0)); ?>">
                                <?php echo isset($results['desktop']->accessibility_score) ? esc_html($results['desktop']->accessibility_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Best Practices</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['desktop']->best_practices_score ?? 0)); ?>">
                                <?php echo isset($results['desktop']->best_practices_score) ? esc_html($results['desktop']->best_practices_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">SEO</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['desktop']->seo_score ?? 0)); ?>">
                                <?php echo isset($results['desktop']->seo_score) ? esc_html($results['desktop']->seo_score . '%') : '--'; ?>
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
                            <div class="score <?php echo esc_attr($this->get_score_class($results['mobile']->performance_score ?? 0)); ?>">
                                <?php echo isset($results['mobile']->performance_score) ? esc_html($results['mobile']->performance_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Accessibility</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['mobile']->accessibility_score ?? 0)); ?>">
                                <?php echo isset($results['mobile']->accessibility_score) ? esc_html($results['mobile']->accessibility_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Best Practices</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['mobile']->best_practices_score ?? 0)); ?>">
                                <?php echo isset($results['mobile']->best_practices_score) ? esc_html($results['mobile']->best_practices_score . '%') : '--'; ?>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">SEO</span>
                            <div class="score <?php echo esc_attr($this->get_score_class($results['mobile']->seo_score ?? 0)); ?>">
                                <?php echo isset($results['mobile']->seo_score) ? esc_html($results['mobile']->seo_score . '%') : '--'; ?>
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
                    <?php echo esc_attr($post_status !== 'publish' ? 'disabled' : '')  ; ?>>
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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            "SELECT * FROM %i 
            ORDER BY next_run ASC",
            $this->pagespeed_scheduled_table
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
                'last_run' => $test->last_run ? gmdate('F j, Y g:i a', strtotime($test->last_run)) : 'Never',
                'next_run' => $test->next_run ? gmdate('F j, Y g:i a', strtotime($test->next_run)) : 'Not scheduled',
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
        $page = isset($_POST['page']) ? absint(wp_unslash($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $offset = ($page - 1) * $per_page;
    
        // Get total count for pagination
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM %i", $this->pagespeed_table);
    
        // Get results with pagination
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared  
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i
                ORDER BY test_date DESC
                LIMIT %d OFFSET %d",
                $this->pagespeed_table,
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
                'test_date' => gmdate('F j, Y g:i a', strtotime($test->test_date)),
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
    
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats = $wpdb->get_row("
            SELECT 
                AVG(performance_score) as avg_performance,
                AVG(accessibility_score) as avg_accessibility,
                AVG(best_practices_score) as avg_best_practices,
                AVG(seo_score) as avg_seo,
                MIN(performance_score) as min_performance,
                MAX(performance_score) as max_performance,
                COUNT(*) as total_tests
            FROM %i
            %i
        ", $this->pagespeed_table, $where);
    
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
    
        $date_limit = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        $where[] = $wpdb->prepare("test_date >= %s", $date_limit);
    
        $where_clause = "WHERE " . implode(" AND ", $where);
    
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(test_date) as date,
                AVG(%s) as value
            FROM %i
            %i
            GROUP BY DATE(test_date)
            ORDER BY date ASC",
            $metric,
            $this->pagespeed_table,
            $where_clause
        ));
    }

    /**
     * Handle scheduled tests
     */
    public function handle_scheduled_pagespeed_tests() {
        global $wpdb;

        // Get all tests that need to be run
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $scheduled_tests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i 
                WHERE next_run <= %s 
                AND (last_run IS NULL OR last_run != %s)",
                $this->pagespeed_scheduled_table,
                current_time('mysql'),
                current_time('mysql', 'DATE')
            )
        );

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
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

            } else {
                // Log the error
             
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
                    display: block!important;
                }
                .pagespeed-test-status {
                    color: #666;
                    font-style: italic;
/*                    display: inline-block; */
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
                .unpublished-notice {
                color: #999;
                font-style: italic;
                font-size: 11px;
                margin-left: 5px;
                vertical-align: middle;
                display: block;
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
    
            $post_status = get_post_status($post_id);
            $url = get_permalink($post_id);
            $results = $this->get_latest_result($url, 'both');
    
            echo '<div class="pagespeed-scores" data-post-id="' . esc_attr($post_id) . '" data-url="' . esc_attr($url) . '" data-status="' . esc_attr($post_status) . '">';
            
            if (empty($results['desktop']) && empty($results['mobile'])) {
                echo wp_kses_post($this->render_indicator('no-test', 'No test', true));
            } else {
                // Display Desktop Score
                if (!empty($results['desktop'])) {
                    $desktop_score = $results['desktop']->performance_score;
                    $desktop_class = $this->get_score_class($desktop_score);
                    echo '<div class="pagespeed-device">';
                    echo wp_kses_post($this->render_indicator($desktop_class, $desktop_score, false, 'desktop'));
                    echo '</div>';  
                }
    
                // Display Mobile Score
                if (!empty($results['mobile'])) {
                    $mobile_score = $results['mobile']->performance_score;
                    $mobile_class = $this->get_score_class($mobile_score);
                    echo '<div class="pagespeed-device">';
                    echo wp_kses_post($this->render_indicator($mobile_class, $mobile_score, false, 'mobile'));
                    echo '</div>';
                }
            }

        // Only show test button if post is published
            if ($post_status === 'publish') {
                echo '<button type="button" class="button button-small quick-test-button">Run Test</button>';
            } else {
                echo '<span class="unpublished-notice">Must be published to test</span>';
            }
            
            echo '<div class="pagespeed-test-status"></div>';
            echo '</div>';
        }
        
        
        /**
         * Helper function to render traffic light indicator
         */
        private function render_indicator($class, $score, $is_no_test = false, $device = '') {
            if ($is_no_test) {
                return sprintf(
                    '<span class="no-test-indicator pagespeed-indicator %s"></span><span class="pagespeed-score no-test-text">%s</span>',
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
                plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-page-speed-testing-list.js',
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