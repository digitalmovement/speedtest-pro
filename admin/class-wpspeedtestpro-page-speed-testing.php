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


        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add meta box for pages and posts
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        
        // Schedule event for running tests
        add_action('pagespeed_run_scheduled_tests', array($this, 'run_scheduled_tests'));
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
            'nonce' => wp_create_nonce('wpspeedtestpro-page-speed-testing-nonce')
        ));


    }

    public function display_page_speed_testing() {
        
        include(plugin_dir_path(__FILE__) . 'partials/wpspeedtestpro-page-speed-testing-display.php');
    }

  public function ajax_run_test() {
    check_ajax_referer('pagespeed_test_nonce', 'nonce');
    
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
    check_ajax_referer('pagespeed_test_nonce', 'nonce');
    
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
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

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
        check_ajax_referer('wpspeedtestpro_nonce', 'nonce');

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

    private function check_test_result($test_id) {
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
        $url = get_permalink($post->ID);
        $results = $this->get_latest_result($url, 'both');
        $has_results = !empty($results['desktop']) || !empty($results['mobile']);

        wp_nonce_field('pagespeed_meta_box', 'pagespeed_meta_box_nonce');
        ?>
        <div class="pagespeed-meta-box">
            <?php if ($has_results): ?>
                <div class="results-grid">
                    <div class="desktop-results">
                        <h4>Desktop</h4>
                        <div class="score <?php echo $this->get_score_class($results['desktop']->performance_score); ?>">
                            <?php echo $results['desktop']->performance_score; ?>%
                        </div>
                        <div class="last-tested">
                            <?php echo human_time_diff(strtotime($results['desktop']->test_date)) . ' ago'; ?>
                        </div>
                    </div>
                    <div class="mobile-results">
                        <h4>Mobile</h4>
                        <div class="score <?php echo $this->get_score_class($results['mobile']->performance_score); ?>">
                            <?php echo $results['mobile']->performance_score; ?>%
                        </div>
                        <div class="last-tested">
                            <?php echo human_time_diff(strtotime($results['mobile']->test_date)) . ' ago'; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p>No PageSpeed test results available.</p>
            <?php endif; ?>
            
            <button type="button" class="button run-pagespeed-test" data-url="<?php echo esc_attr($url); ?>">
                Run PageSpeed Test
            </button>
            <span class="spinner"></span>
        </div>
        <?php
    }


    public function ajax_get_scheduled_tests() {
        check_ajax_referer('pagespeed_test_nonce', 'nonce');
    
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
        check_ajax_referer('pagespeed_test_nonce', 'nonce');
    
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



}