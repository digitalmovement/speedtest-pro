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
        $this->pagespeed_table = $wpdb->prefix . 'wpspeedtestpro_pagespeed_results';
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
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->pagespeed_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            location varchar(50) NOT NULL,
            device varchar(50) NOT NULL,
            test_date datetime DEFAULT CURRENT_TIMESTAMP,
            performance_score int(3),
            accessibility_score int(3),
            best_practices_score int(3),
            seo_score int(3),
            fcp int(11),
            lcp int(11),
            cls decimal(5,3),
            si int(11),
            tti int(11),
            tbt int(11),
            full_report longtext,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY test_date (test_date)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$this->pagespeed_scheduled_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            frequency varchar(20) NOT NULL,
            last_run datetime DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function run_test($url, $api_key = '', $device = 'desktop') {
        $api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        
        $params = array(
            'url' => esc_url($url),
            'strategy' => $device
        );

        if (!empty($api_key)) {
            $params['key'] = $api_key;
        }

        $request_url = add_query_arg($params, $api_url);
        $response = wp_remote_get($request_url);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || isset($data['error'])) {
            return array(
                'success' => false,
                'message' => isset($data['error']['message']) ? $data['error']['message'] : 'Invalid response from PageSpeed API'
            );
        }

        // Parse and save results
        $results = $this->parse_results($data);
        $this->save_results($url, $device, $results, $data);

        return array(
            'success' => true,
            'data' => $results
        );
    }

    private function parse_results($data) {
        $lighthouse = $data['lighthouseResult'];
        $categories = $lighthouse['categories'];
        $audits = $lighthouse['audits'];

        return array(
            'performance_score' => round($categories['performance']['score'] * 100),
            'accessibility_score' => round($categories['accessibility']['score'] * 100),
            'best_practices_score' => round($categories['best-practices']['score'] * 100),
            'seo_score' => round($categories['seo']['score'] * 100),
            'fcp' => $audits['first-contentful-paint']['numericValue'],
            'lcp' => $audits['largest-contentful-paint']['numericValue'],
            'cls' => $audits['cumulative-layout-shift']['numericValue'],
            'si' => $audits['speed-index']['numericValue'],
            'tti' => $audits['interactive']['numericValue'],
            'tbt' => $audits['total-blocking-time']['numericValue']
        );
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

    private function get_score_class($score) {
        if ($score >= 90) return 'good';
        if ($score >= 50) return 'average';
        return 'poor';
    }
}