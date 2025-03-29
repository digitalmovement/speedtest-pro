<?php

class Wpspeedtestpro_DB {
    private $hosting_benchmarking_table;
    private $benchmark_results_table;
    private $pagespeed_table;
    private $pagespeed_scheduled_table;


    public function __construct() {
        global $wpdb;
        $this->hosting_benchmarking_table        = $wpdb->prefix . 'wpspeedtestpro_hosting_benchmarking_results';
        $this->benchmark_results_table           = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        $this->pagespeed_table                   = $wpdb->prefix . 'wpspeedtestpro_pagespeed_results';
        $this->pagespeed_scheduled_table         = $wpdb->prefix . 'wpspeedtestpro_pagespeed_scheduled';
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->hosting_benchmarking_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            region varchar(20) NOT NULL,
            region_name varchar(255) NOT NULL,
            latency float NOT NULL,
            latency_difference float DEFAULT NULL,
            synced tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY region (region),
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

    public function insert_result($region, $region_name, $latency) {
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
                'region' => $region,
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
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->hosting_benchmarking_table} ORDER BY test_time DESC LIMIT %d", 10), ARRAY_A);
    }

    public function get_latest_benchmark_results() {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->benchmark_results_table} ORDER BY test_date DESC LIMIT %d", 1), ARRAY_A);
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

        return $wpdb->get_results($wpdb->prepare("%s", $query));
    }

    public function delete_all_results() {
        global $wpdb;
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $this->hosting_benchmarking_table));
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $this->benchmark_results_table));
        
    }

    public function purge_old_results() {
        global $wpdb;
        $one_week_ago = gmdate('Y-m-d H:i:s', strtotime('-1 week'));
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
    
       // Add appropriate parameters
        $results = $wpdb->get_results($wpdb->prepare("%s", $query));
        return $results;
    }

    public function get_results_by_time_range($time_range) {
        global $wpdb;
        
        // Validate time range using a whitelist approach
        $valid_intervals = [
            '24_hours' => 1,
            '7_days' => 7,
            '90_days' => 90,
        ];
        
        // Default to 24 hours if not a valid selection
        $interval_number = isset($valid_intervals[$time_range]) ? $valid_intervals[$time_range] : 1;
        
        // Create a safe query using wpdb's built-in methods
        $table_name = $wpdb->prefix . 'wpspeedtestpro_hosting_benchmarking_results'; // Use prefix properly
        
        // Use CAST to ensure numeric value without quotes
        $query = "
            SELECT * FROM {$this->hosting_benchmarking_table}
            WHERE test_time >= DATE_SUB(NOW(), INTERVAL CAST(%d AS UNSIGNED) DAY)
            ORDER BY test_time ASC
        ";
        
        $safe_query = $wpdb->prepare($query, $interval_number); // Add appropriate parameters
        $results = $wpdb->get_results($safe_query);

        return $results;
    }

    public function get_benchmark_results_by_time_range($time_range) {
        global $wpdb;
        
        // Determine the time range - validate input
        $valid_intervals = [
            '24_hours' => 1,
            '7_days' => 7,
            '90_days' => 90,
        ];

        $interval_number = isset($valid_intervals[$time_range]) ? $valid_intervals[$time_range] : 1;
   
        $query = "
            SELECT * FROM {$this->benchmark_results_table}
            WHERE test_date >= DATE_SUB(NOW(), INTERVAL CAST(%d AS UNSIGNED) DAY)
            ORDER BY test_time ASC
             ";
        $safe_query = $wpdb->prepare($query, $parameters); // Add appropriate parameters
        $results = $wpdb->get_results($safe_query);

        return $results;
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
            synced tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY test_date (test_date),
            KEY synced (synced)
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
        dbDelta($sql);
    }



    public function get_unsynced_data() {
        global $wpdb;
        
        // Get unsynced benchmark results using prepared statement
        $benchmark_results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->benchmark_results_table} WHERE synced = %d", 0),
            ARRAY_A
        );

        // Get unsynced hosting benchmarking results using prepared statement
        $hosting_results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->hosting_benchmarking_table} WHERE synced = %d", 0),
            ARRAY_A
        );

        // Get unsynced speedvitals results using prepared statement
        $pagespeed_results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->pagespeed_table} WHERE synced = %d", 0),
            ARRAY_A
        );

        return [
            'benchmark_results' => $benchmark_results,
            'hosting_results' => $hosting_results,
            'pagespeed_results' => $pagespeed_results
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
            case 'pagespeed':
                $table = $this->pagespeed_table;
                break;
        }
        
        if (empty($table)) return;
        
        // Use placeholders for each ID and prepare the query properly
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET synced = 1 WHERE id IN ($placeholders)", $ids));
    }



} // End of class