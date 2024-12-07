<?php

class Wpspeedtestpro_DB {
    private $hosting_benchmarking_table;
    private $benchmark_results_table;
    private $speedvitals_tests_table;
    private $speedvitals_scheduled_tests_table;
    private $pagespeed_table;
    private $pagespeed_scheduled_table;


    public function __construct() {
        global $wpdb;
        $this->hosting_benchmarking_table        = $wpdb->prefix . 'wpspeedtestpro_hosting_benchmarking_results';
        $this->benchmark_results_table           = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        $this->speedvitals_tests_table           = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';
        $this->speedvitals_scheduled_tests_table = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';
        $this->pagespeed_table                   = $wpdb->prefix . 'wpspeedtestpro_pagespeed_results';
        $this->pagespeed_scheduled_table         = $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled';
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->hosting_benchmarking_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            region_name varchar(255) NOT NULL,
            latency float NOT NULL,
            latency_difference float DEFAULT NULL,
            synced tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY region_name (region_name),
            KEY test_time (test_time),
            KEY synced (synced)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function create_benchmark_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->benchmark_results_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            math float NOT NULL,
            string float NOT NULL,
            loops float NOT NULL,
            conditionals float NOT NULL,
            mysql float NOT NULL,
            wordpress_performance_time float NOT NULL,
            wordpress_performance_queries float NOT NULL,
            upload_10k float NULL,
            upload_100k float NULL,
            upload_1mb float NULL,
            upload_10mb float NULL,
            download_10k float NULL,
            download_100k float NULL,
            download_1mb float NULL,
            download_10mb float NULL,
            ping_latency float NULL,
            ip_address varchar(45) NULL,
            location varchar(2) NULL,
            synced tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY test_date (test_date),
            KEY synced (synced)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function insert_result($region_name, $latency) {
        global $wpdb;

        // Fetch the latest result for the same region
        $latest_result = $wpdb->get_row($wpdb->prepare(
            "SELECT latency FROM {$this->hosting_benchmarking_table} WHERE region_name = %s ORDER BY test_time DESC LIMIT 1",
            $region_name
        ));

        // Calculate the latency difference
        $latency_difference = $latest_result ? ($latency - $latest_result->latency) : null;

        // Insert the new result with latency difference
        $wpdb->insert(
            $this->hosting_benchmarking_table,
            array(
                'test_time' => current_time('mysql'),
                'region_name' => $region_name,
                'latency' => $latency,
                'latency_difference' => $latency_difference
            )
        );
    }

    public function insert_benchmark_result($results) {
        global $wpdb;

        $wpdb->insert(
            $this->benchmark_results_table,
            array(
                'test_date' => current_time('mysql'),
                'math' => $results['math'],
                'string' => $results['string'],
                'loops' => $results['loops'],
                'conditionals' => $results['conditionals'],
                'mysql' => $results['mysql'],
                'wordpress_performance_time' => $results['wordpress_performance']['time'],
                'wordpress_performance_queries' => $results['wordpress_performance']['queries'],
                'upload_10k' => $results['speed_test']['upload_10k'],
                'upload_100k' => $results['speed_test']['upload_100k'],
                'upload_1mb' => $results['speed_test']['upload_1mb'],
                'upload_10mb' => $results['speed_test']['upload_10mb'],
                'download_10k' => $results['speed_test']['download_10k'],
                'download_100k' => $results['speed_test']['download_100k'],
                'download_1mb' => $results['speed_test']['download_1mb'],
                'download_10mb' => $results['speed_test']['download_10mb'],
                'ping_latency' => $results['speed_test']['ping_latency'],
                'ip_address' => $results['speed_test']['ip_address'],
                'location' => $results['speed_test']['location']
            )
        );
    }

    public function get_latest_results() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->hosting_benchmarking_table} ORDER BY test_time DESC LIMIT 10", ARRAY_A);
    }

    public function get_latest_benchmark_results() {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$this->benchmark_results_table} ORDER BY test_date DESC LIMIT 1", ARRAY_A);
    }

    public function get_benchmark_results($limit = 30) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->benchmark_results_table} ORDER BY test_date DESC LIMIT %d", $limit), ARRAY_A);
    }

    public function get_latest_results_by_region() {
        global $wpdb;
        
        $query = "
            SELECT r1.*
            FROM {$this->hosting_benchmarking_table} r1
            INNER JOIN (
                SELECT region_name, MAX(test_time) as max_time
                FROM {$this->hosting_benchmarking_table}
                GROUP BY region_name
            ) r2 ON r1.region_name = r2.region_name AND r1.test_time = r2.max_time
            ORDER BY r1.region_name
        ";

        return $wpdb->get_results($query);
    }

    public function delete_all_results() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->hosting_benchmarking_table}");
        $wpdb->query("TRUNCATE TABLE {$this->benchmark_results_table}");
    }

    public function purge_old_results() {
        global $wpdb;
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->hosting_benchmarking_table} WHERE test_time < %s", $one_week_ago));
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->benchmark_results_table} WHERE test_date < %s", $one_week_ago));
    }

    public function get_fastest_and_slowest_results() {
        global $wpdb;
    
        $query = "
            SELECT region_name, 
                   MIN(latency) AS fastest_latency, 
                   MAX(latency) AS slowest_latency
            FROM {$this->hosting_benchmarking_table}
            GROUP BY region_name
        ";
    
        return $wpdb->get_results($query);
    }

    public function get_results_by_time_range($time_range) {
        global $wpdb;
        
        // Determine the time range
        switch($time_range) {
            case '24_hours':
                $time_limit = '1 DAY';
                break;
            case '7_days':
                $time_limit = '7 DAY';
                break;
            case '90_days':
                $time_limit = '90 DAY';
                break;
            default:
                $time_limit = '1 DAY'; // Default to 24 hours
        }
    
        $query = $wpdb->prepare("
            SELECT * FROM {$this->hosting_benchmarking_table}
            WHERE test_time >= NOW() - INTERVAL $time_limit
            ORDER BY test_time ASC
        ");
    
        return $wpdb->get_results($query);
    }

    public function get_benchmark_results_by_time_range($time_range) {
        global $wpdb;
        
        // Determine the time range
        switch($time_range) {
            case '24_hours':
                $time_limit = '1 DAY';
                break;
            case '7_days':
                $time_limit = '7 DAY';
                break;
            case '90_days':
                $time_limit = '90 DAY';
                break;
            default:
                $time_limit = '1 DAY'; // Default to 24 hours
        }
    
        $query = $wpdb->prepare("
            SELECT * FROM {$this->benchmark_results_table}
            WHERE test_date >= NOW() - INTERVAL $time_limit
            ORDER BY test_date ASC
        ");
    
        return $wpdb->get_results($query);
    }

    public function get_new_benchmark_results($last_id = 0) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->benchmark_results_table} WHERE id > %d ORDER BY id ASC",
                $last_id
            ),
            ARRAY_A
        );
    }


    public function speedvitals_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->speedvitals_tests_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            location varchar(50) NOT NULL,
            device varchar(50) NOT NULL,
            test_id varchar(50) NOT NULL,
            test_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) NOT NULL,
            performance_score int(3),
            first_contentful_paint int(11),
            speed_index int(11),
            largest_contentful_paint int(11),
            time_to_interactive int(11),
            total_blocking_time int(11),
            cumulative_layout_shift float,
            report_url varchar(255),
            synced tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY synced (synced)
        ) $charset_collate;

        CREATE TABLE {$this->speedvitals_scheduled_tests_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            location varchar(50) NOT NULL,
            device varchar(50) NOT NULL,
            frequency varchar(20) NOT NULL,
            last_run datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            next_run datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    public function create_pagespeed_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->pagespeed_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            location varchar(50) NOT NULL,
            device varchar(50) NOT NULL,
            test_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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
        
        CREATE TABLE  {$this->pagespeed_scheduled_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            frequency varchar(20) NOT NULL,
            last_run datetime DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      //  dbDelta($sql);
    }



    public function speedvitals_insert_test_result($result) {
        global $wpdb;

        $wpdb->insert(
            $this->speedvitals_tests_table,
            array(
                'url' => $result['url'],
                'location' => $result['location'],
                'device' => $result['device'],
                'test_id' => $result['id'],
                'test_date' => current_time('mysql'),
                'status' => $result['status'],
                'performance_score' => $result['metrics']['performance_score'] ?? null,
                'first_contentful_paint' => $result['metrics']['first_contentful_paint'] ?? null,
                'speed_index' => $result['metrics']['speed_index'] ?? null,
                'largest_contentful_paint' => $result['metrics']['largest_contentful_paint'] ?? null,
                'time_to_interactive' => $result['metrics']['time_to_interactive'] ?? null,
                'total_blocking_time' => $result['metrics']['total_blocking_time'] ?? null,
                'cumulative_layout_shift' => $result['metrics']['cumulative_layout_shift'] ?? null,
                'report_url' => $result['report_url'] ?? null
            )
        );

        return $wpdb->insert_id;
    }

    public function speedvitals_get_test_results($limit = 20) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->speedvitals_tests_table} ORDER BY test_date DESC LIMIT %d", $limit),
            ARRAY_A
        );
    }

    public function speedvitals_get_test_result($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->speedvitals_tests_table} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    public function speedvitals_schedule_test($url, $location, $device, $frequency) {
        global $wpdb;

        $next_run = $this->speedvitals_calculate_next_run($frequency);

        return $wpdb->insert(
            $this->speedvitals_scheduled_tests_table,
            array(
                'url' => $url,
                'location' => $location,
                'device' => $device,
                'frequency' => $frequency,
                'last_run' => current_time('mysql'),
                'next_run' => $next_run
            )
        );
    }

    private function speedvitals_calculate_next_run($frequency, $last_run = null) {
        $now = new DateTime();
        $last_run = $last_run ? new DateTime($last_run) : $now;
    
        switch ($frequency) {
            case 'daily':
                $next_run = clone $last_run;
                $next_run->modify('+1 day');
                $next_run->setTime(0, 0, 0); // Set to midnight
    
                // If next run is in the past, set it to the next day
                if ($next_run <= $now) {
                    $next_run->modify('+1 day');
                }
                break;
    
            case 'weekly':
                $next_run = clone $last_run;
                $next_run->modify('+1 week');
                $next_run->setTime(0, 0, 0); // Set to midnight
    
                // If next run is in the past, set it to the next week
                if ($next_run <= $now) {
                    $next_run->modify('+1 week');
                }
                break;
    
            default:
                return null;
        }
    
        return $next_run->format('Y-m-d H:i:s');
    }

    public function speedvitals_get_scheduled_tests() {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$this->speedvitals_scheduled_tests_table} ORDER BY next_run ASC", ARRAY_A);
    }

    public function speedvitals_cancel_scheduled_test($id) {
        global $wpdb;

        return $wpdb->delete($this->speedvitals_scheduled_tests_table, array('id' => $id));
    }

    public function speedvitals_delete_old_results($days) {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-$days days"));

        return $wpdb->query(
            $wpdb->prepare("DELETE FROM {$this->speedvitals_tests_table} WHERE test_date < %s", $date)
        );
    }

    public function speedvitals_update_test_result($id, $result) {
        global $wpdb;

        return $wpdb->update(
            $this->speedvitals_tests_table,
            array(
                'status' => $result['status'],
                'performance_score' => $result['metrics']['performance_score'] ?? null,
                'first_contentful_paint' => $result['metrics']['first_contentful_paint'] ?? null,
                'speed_index' => $result['metrics']['speed_index'] ?? null,
                'largest_contentful_paint' => $result['metrics']['largest_contentful_paint'] ?? null,
                'time_to_interactive' => $result['metrics']['time_to_interactive'] ?? null,
                'total_blocking_time' => $result['metrics']['total_blocking_time'] ?? null,
                'cumulative_layout_shift' => $result['metrics']['cumulative_layout_shift'] ?? null,
                'report_url' => $result['report_url'] ?? null
            ),
            array('id' => $id)
        );
    }

    public function speedvitals_get_pending_tests() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->speedvitals_tests_table} WHERE status NOT IN ('success', 'failed') ORDER BY test_date ASC",
            ARRAY_A
        );
    }

    public function speedvitals_update_scheduled_test($id) {
        global $wpdb;

        $scheduled_test = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->speedvitals_scheduled_tests_table} WHERE id = %d", $id), ARRAY_A);

        if (!$scheduled_test) {
            return false;
        }
        
        $next_run = $this->speedvitals_calculate_next_run($scheduled_test['frequency'], current_time('mysql'));

        return $wpdb->update(
            $this->speedvitals_scheduled_tests_table,
            array(
                'last_run' => current_time('mysql'),
                'next_run' => $next_run
            ),
            array('id' => $id)
        );
    }

    public function speedvitals_get_due_scheduled_tests() {
        global $wpdb;

        $current_time = current_time('mysql');

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->speedvitals_scheduled_tests_table} WHERE next_run <= %s", $current_time),
            ARRAY_A
        );
    }       

    public function get_unsynced_data() {
        global $wpdb;
        
        // Get unsynced benchmark results
        $benchmark_results = $wpdb->get_results(
            "SELECT * FROM {$this->benchmark_results_table} WHERE synced = 0",
            ARRAY_A
        );

        // Get unsynced hosting benchmarking results
        $hosting_results = $wpdb->get_results(
            "SELECT * FROM {$this->hosting_benchmarking_table} WHERE synced = 0",
            ARRAY_A
        );

        // Get unsynced speedvitals results
        $speedvitals_results = $wpdb->get_results(
            "SELECT * FROM {$this->speedvitals_tests_table} WHERE synced = 0 AND status = 'success'",
            ARRAY_A
        );

        return [
            'benchmark_results' => $benchmark_results,
            'hosting_results' => $hosting_results,
            'speedvitals_results' => $speedvitals_results
        ];
    }

    // Method to mark records as synced
    public function mark_as_synced($table_type, $ids) {
        if (empty($ids)) return;
        
        global $wpdb;
        $table = '';
        
        switch($table_type) {
            case 'benchmark':
                $table = $this->benchmark_results_table;
                break;
            case 'hosting':
                $table = $this->hosting_benchmarking_table;
                break;
            case 'speedvitals':
                $table = $this->speedvitals_tests_table;
                break;
        }
        
        if (empty($table)) return;
        
        $ids_string = implode(',', array_map('intval', $ids));
        $wpdb->query("UPDATE {$table} SET synced = 1 WHERE id IN ({$ids_string})");
    }



} // End of class