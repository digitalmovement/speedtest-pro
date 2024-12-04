<?php

class Wpspeedtestpro_PageSpeed_Insights {
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
        
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('wp_ajax_run_pagespeed_test', array($this, 'ajax_run_test'));
        add_action('wp_ajax_schedule_pagespeed_test', array($this, 'ajax_schedule_test'));
        add_action('wp_ajax_delete_old_pagespeed_results', array($this, 'ajax_delete_old_results'));
        add_action('wp_ajax_get_pagespeed_results', array($this, 'ajax_get_results'));
        add_action('wpspeedtestpro_daily_pagespeed_check', array($this, 'run_scheduled_tests'));
        
        // Add meta box for pages and posts
        add_action('add_meta_boxes', array($this, 'add_pagespeed_meta_box'));
        add_action('save_post', array($this, 'save_pagespeed_meta'));
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->pagespeed_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            test_date datetime DEFAULT CURRENT_TIMESTAMP,
            desktop_performance int(3),
            desktop_accessibility int(3),
            desktop_best_practices int(3),
            desktop_seo int(3),
            mobile_performance int(3),
            mobile_accessibility int(3),
            mobile_best_practices int(3),
            mobile_seo int(3),
            fcp_score decimal(5,2),
            lcp_score decimal(5,2),
            cls_score decimal(5,2),
            fid_score decimal(5,2),
            si_score decimal(5,2),
            tti_score decimal(5,2),
            full_report longtext,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY test_date (test_date)
        ) $charset_collate;";

        $sql .= "CREATE TABLE IF NOT EXISTS {$this->pagespeed_scheduled_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            frequency varchar(20) NOT NULL,
            last_run datetime DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            KEY next_run (next_run)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function run_test($url, $api_key = '') {
        $devices = ['mobile', 'desktop'];
        $results = [];

        foreach ($devices as $device) {
            $api_url = add_query_arg([
                'url' => esc_url($url),
                'strategy' => $device
            ], 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed');

            if (!empty($api_key)) {
                $api_url = add_query_arg('key', $api_key, $api_url);
            }

            $response = wp_remote_get($api_url);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data)) {
                continue;
            }

            $results[$device] = $this->parse_pagespeed_response($data);
        }

        if (!empty($results)) {
            $this->save_test_results($url, $results);
        }

        return $results;
    }

    private function parse_pagespeed_response($data) {
        $categories = $data['lighthouseResult']['categories'];
        $audits = $data['lighthouseResult']['audits'];
        
        return [
            'performance' => $categories['performance']['score'] * 100,
            'accessibility' => $categories['accessibility']['score'] * 100,
            'best_practices' => $categories['best-practices']['score'] * 100,
            'seo' => $categories['seo']['score'] * 100,
            'fcp' => $audits['first-contentful-paint']['score'] * 100,
            'lcp' => $audits['largest-contentful-paint']['score'] * 100,
            'cls' => $audits['cumulative-layout-shift']['score'] * 100,
            'fid' => $audits['max-potential-fid']['score'] * 100,
            'si' => $audits['speed-index']['score'] * 100,
            'tti' => $audits['interactive']['score'] * 100
        ];
    }

    private function save_test_results($url, $results) {
        global $wpdb;

        $data = [
            'url' => $url,
            'test_date' => current_time('mysql'),
            'desktop_performance' => $results['desktop']['performance'],
            'desktop_accessibility' => $results['desktop']['accessibility'],
            'desktop_best_practices' => $results['desktop']['best_practices'],
            'desktop_seo' => $results['desktop']['seo'],
            'mobile_performance' => $results['mobile']['performance'],
            'mobile_accessibility' => $results['mobile']['accessibility'],
            'mobile_best_practices' => $results['mobile']['best_practices'],
            'mobile_seo' => $results['mobile']['seo'],
            'fcp_score' => $results['desktop']['fcp'],
            'lcp_score' => $results['desktop']['lcp'],
            'cls_score' => $results['desktop']['cls'],
            'fid_score' => $results['desktop']['fid'],
            'si_score' => $results['desktop']['si'],
            'tti_score' => $results['desktop']['tti'],
            'full_report' => json_encode($results)
        ];

        $wpdb->insert($this->pagespeed_table, $data);
        return $wpdb->insert_id;
    }

    public function schedule_test($url, $frequency) {
        global $wpdb;
        
        $next_run = $this->calculate_next_run($frequency);
        
        return $wpdb->insert($this->pagespeed_scheduled_table, [
            'url' => $url,
            'frequency' => $frequency,
            'next_run' => $next_run
        ]);
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

    public function run_scheduled_tests() {
        global $wpdb;
        
        $now = current_time('mysql');
        $scheduled_tests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->pagespeed_scheduled_table} 
            WHERE active = 1 AND next_run <= %s",
            $now
        ));

        $api_key = get_option('wpspeedtestpro_pagespeed_api_key');

        foreach ($scheduled_tests as $test) {
            $this->run_test($test->url, $api_key);
            
            // Update next run time
            $next_run = $this->calculate_next_run($test->frequency);
            $wpdb->update(
                $this->pagespeed_scheduled_table,
                ['last_run' => $now, 'next_run' => $next_run],
                ['id' => $test->id]
            );
        }
    }

    public function get_results($url = '', $limit = 10) {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->pagespeed_table}";
        if (!empty($url)) {
            $query .= $wpdb->prepare(" WHERE url = %s", $url);
        }
        $query .= " ORDER BY test_date DESC LIMIT " . intval($limit);
        
        return $wpdb->get_results($query);
    }

    public function delete_old_results($days) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->pagespeed_table} WHERE test_date < %s",
            $cutoff_date
        ));
    }

    public function register_shortcode() {
        add_shortcode('pagespeed_results', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'url' => get_permalink(),
            'show_graph' => 'true'
        ], $atts);

        $results = $this->get_results($atts['url'], 1);
        if (empty($results)) {
            return '<p>No PageSpeed results available for this page.</p>';
        }

        $result = $results[0];
        ob_start();
        ?>
        <div class="pagespeed-results">
            <div class="pagespeed-summary">
                <h3>PageSpeed Insights Results</h3>
                <div class="pagespeed-scores">
                    <div class="desktop-scores">
                        <h4>Desktop</h4>
                        <ul>
                            <li>Performance: <?php echo $result->desktop_performance; ?>%</li>
                            <li>Accessibility: <?php echo $result->desktop_accessibility; ?>%</li>
                            <li>Best Practices: <?php echo $result->desktop_best_practices; ?>%</li>
                            <li>SEO: <?php echo $result->desktop_seo; ?>%</li>
                        </ul>
                    </div>
                    <div class="mobile-scores">
                        <h4>Mobile</h4>
                        <ul>
                            <li>Performance: <?php echo $result->mobile_performance; ?>%</li>
                            <li>Accessibility: <?php echo $result->mobile_accessibility; ?>%</li>
                            <li>Best Practices: <?php echo $result->mobile_best_practices; ?>%</li>
                            <li>SEO: <?php echo $result->mobile_seo; ?>%</li>
                        </ul>
                    </div>
                </div>
                <p class="last-tested">Last tested: <?php echo date('F j, Y g:i a', strtotime($result->test_date)); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_pagespeed_meta_box() {
        add_meta_box(
            'pagespeed_insights',
            'PageSpeed Insights',
            array($this, 'render_meta_box'),
            ['post', 'page'],
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        $results = $this->get_results(get_permalink($post->ID), 1);
        if (empty($results)) {
            echo '<p>No PageSpeed results available.</p>';
        } else {
            $result = $results[0];
            ?>
            <div class="pagespeed-meta-box">
                <p><strong>Desktop Performance:</strong> <?php echo $result->desktop_performance; ?>%</p>
                <p><strong>Mobile Performance:</strong> <?php echo $result->mobile_performance; ?>%</p>
                <p><em>Last tested: <?php echo date('F j, Y', strtotime($result->test_date)); ?></em></p>
            </div>
            <?php
        }
        
        wp_nonce_field('pagespeed_test_nonce', 'pagespeed_test_nonce');
        ?>
        <button type="button" class="button" id="run-pagespeed-test">
            Run PageSpeed Test
        </button>
        <?php
    }

    public function save_pagespeed_meta($post_id) {
        if (!isset($_POST['pagespeed_test_nonce']) || 
            !wp_verify_nonce($_POST['pagespeed_test_nonce'], 'pagespeed_test_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Any additional meta saving logic can go here
    }
}