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
        dbDelta($sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
        dbDelta($sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public function insert_result($region, $region_name, $latency) {
        global $wpdb;

        // Fetch the latest result for the same region
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $latest_result = $wpdb->get_row($wpdb->prepare(
            "SELECT latency FROM %i WHERE region_name = %s ORDER BY test_time DESC LIMIT 1",
            $this->hosting_benchmarking_table,
            $region_name
        )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // Calculate the latency difference
        $latency_difference = $latest_result ? ($latency - $latest_result->latency) : null;


        // Insert the new result with latency difference
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $this->hosting_benchmarking_table,
            array(
                'test_time' => current_time('mysql'),
                'region' => $region,
                'region_name' => $region_name,
                'latency' => $latency,
                'latency_difference' => $latency_difference
            )
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // Invalidate related caches
        wp_cache_delete('wpspeedtestpro_latest_results');
        wp_cache_delete('wpspeedtestpro_latest_results_by_region');
        wp_cache_delete('wpspeedtestpro_fastest_and_slowest_results');
        
        // For time range caches, you might want to delete all possible time ranges
        $time_ranges = ['24_hours', '7_days', '90_days'];
        foreach ($time_ranges as $range) {
            wp_cache_delete('wpspeedtestpro_results_' . $range);
        }
    }

    public function insert_benchmark_result($results) {
        global $wpdb;
       // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // Invalidate related caches
        wp_cache_delete('wpspeedtestpro_latest_benchmark_results');
        
        // Delete all possible benchmark results caches with different limits
        for ($i = 1; $i <= 100; $i++) {
            wp_cache_delete('wpspeedtestpro_benchmark_results_' . $i);
        }
        
        // For time range caches
        $time_ranges = ['24_hours', '7_days', '90_days'];
        foreach ($time_ranges as $range) {
            wp_cache_delete('wpspeedtestpro_benchmark_results_' . $range);
        }
    }

    public function get_latest_results() {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_latest_results';
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY test_time DESC LIMIT %d", $this->hosting_benchmarking_table, 10), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Cache the results for 1 hour (3600 seconds)
            wp_cache_set($cache_key, $results, '', 3600);
        }
        
        return $results;
    }

    public function get_latest_benchmark_results() {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_latest_benchmark_results';
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i ORDER BY test_date DESC LIMIT %d", $this->benchmark_results_table, 1), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $results, '', 3600);
        }
        
        return $results;
    }

    public function get_benchmark_results($limit = 30) {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_benchmark_results_' . $limit;
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY test_date DESC LIMIT %d", $this->benchmark_results_table, $limit), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $results, '', 3600);
        }
        
        return $results;
    }

    public function get_latest_results_by_region() {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_latest_results_by_region';
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
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

            $results = $wpdb->get_results($wpdb->prepare("%s", $query)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $results, '', 3600);
        }
        
        return $results;
    }

    public function delete_all_results() {
        global $wpdb;
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $this->hosting_benchmarking_table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $this->benchmark_results_table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        
        // Clear all caches
        wp_cache_flush();
    }

    public function purge_old_results() {
        global $wpdb;
        $one_week_ago = gmdate('Y-m-d H:i:s', strtotime('-1 week'));
        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE test_time < %s", $this->hosting_benchmarking_table, $one_week_ago)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE test_date < %s", $this->benchmark_results_table, $one_week_ago)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public function get_fastest_and_slowest_results() {
        global $wpdb;

        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_fastest_and_slowest_results1';
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            $query = "
                SELECT region_name,
                       MIN(latency) AS fastest_latency,
                       MAX(latency) AS slowest_latency
                FROM nml_wpspeedtestpro_hosting_benchmarking_results 
                GROUP BY region_name
            ";
        
            $results = $wpdb->get_results($wpdb->prepare("SELECT region_name,
                       MIN(latency) AS fastest_latency,
                       MAX(latency) AS slowest_latency
                FROM %i 
                GROUP BY region_name", $this->hosting_benchmarking_table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // Cache the results for 1 hour
            wp_cache_set($cache_key, $results, '', 3600);
        }
        
        return $results;
    }

    public function get_results_by_time_range($time_range) {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_results_' . $time_range;
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            // Validate time range using a whitelist approach
            $valid_intervals = [
                '24_hours' => 1,
                '7_days' => 7,
                '90_days' => 90,
            ];
            
            // Default to 24 hours if not a valid selection
            $interval_number = isset($valid_intervals[$time_range]) ? $valid_intervals[$time_range] : 1;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE test_time >= DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY test_time ASC",
                    $this->hosting_benchmarking_table,
                    $interval_number
                )
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            
            // Cache the results for 30 minutes (1800 seconds)
            wp_cache_set($cache_key, $results, '', 1800);
        }
        
        return $results;
    }

    public function get_benchmark_results_by_time_range($time_range) {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'wpspeedtestpro_benchmark_results_' . $time_range;
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            // Determine the time range - validate input
            $valid_intervals = [
                '24_hours' => 1,
                '7_days' => 7,
                '90_days' => 90,
            ];

            $interval_number = isset($valid_intervals[$time_range]) ? $valid_intervals[$time_range] : 1;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE test_date >= DATE_SUB(NOW(), INTERVAL CAST(%d AS UNSIGNED) DAY) ORDER BY test_date ASC",
                    $this->benchmark_results_table,
                    $interval_number
                )
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            
            // Cache the results for 30 minutes
            wp_cache_set($cache_key, $results, '', 1800);
        }
        
        return $results;
    }

    public function get_new_benchmark_results($last_id = 0) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE id > %d ORDER BY id ASC",
                $this->benchmark_results_table,
                $last_id
            ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
        dbDelta($sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }



    public function get_unsynced_data() {
        global $wpdb;
        
        // This data changes frequently, so use a shorter cache time (5 minutes)
        $cache_key = 'wpspeedtestpro_unsynced_data';
        $results = wp_cache_get($cache_key);
        
        if (false === $results) {
            // Get unsynced benchmark results using prepared statement
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $benchmark_results = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i WHERE synced = %d", $this->benchmark_results_table, 0),
                ARRAY_A
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            // Get unsynced hosting benchmarking results using prepared statement
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $hosting_results = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i WHERE synced = %d", $this->hosting_benchmarking_table, 0),
                ARRAY_A
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            // Get unsynced speedvitals results using prepared statement
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $pagespeed_results = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i WHERE synced = %d", $this->pagespeed_table, 0),
                ARRAY_A
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            $results = [
                'benchmark_results' => $benchmark_results,
                'hosting_results' => $hosting_results,
                'pagespeed_results' => $pagespeed_results
            ];
            
            // Cache for a shorter time since this data changes frequently
            wp_cache_set($cache_key, $results, '', 300);
        }
        
        return $results;
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
        
        // Update each ID individually to avoid interpolation issues
        foreach ($ids as $id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                ['synced' => 1],
                ['id' => $id],
                ['%d'],
                ['%d']
            );
        }
        
        // Invalidate the unsynced data cache
        wp_cache_delete('wpspeedtestpro_unsynced_data');
    }



} // End of class