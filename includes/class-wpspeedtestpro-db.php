<?php

class Wpspeedtestpro_DB {
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            region_name varchar(255) NOT NULL,
            latency float NOT NULL,
            latency_difference float DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY region_name (region_name),
            KEY test_time (test_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $this->create_benchmark_table();
    }

    public function create_benchmark_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            math float NOT NULL,
            string float NOT NULL,
            loops float NOT NULL,
            conditionals float NOT NULL,
            mysql float NOT NULL,
            wordpress_performance_time float NOT NULL,
            wordpress_performance_queries float NOT NULL,
            PRIMARY KEY  (id),
            KEY test_date (test_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function insert_result($region_name, $latency) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';

        // Fetch the latest result for the same region
        $latest_result = $wpdb->get_row($wpdb->prepare(
            "SELECT latency FROM $table_name WHERE region_name = %s ORDER BY test_time DESC LIMIT 1",
            $region_name
        ));

        // Calculate the latency difference
        $latency_difference = $latest_result ? ($latency - $latest_result->latency) : null;

        // Insert the new result with latency difference
        $wpdb->insert(
            $table_name,
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
        $table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';

        $wpdb->insert(
            $table_name,
            array(
                'test_date' => current_time('mysql'),
                'math' => $results['math'],
                'string' => $results['string'],
                'loops' => $results['loops'],
                'conditionals' => $results['conditionals'],
                'mysql' => $results['mysql'],
                'wordpress_performance_time' => $results['wordpress_performance']['time'],
                'wordpress_performance_queries' => $results['wordpress_performance']['queries']
            )
        );
    }

    public function get_latest_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY test_time DESC LIMIT 10", ARRAY_A);
    }

    public function get_latest_benchmark_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        return $wpdb->get_row("SELECT * FROM $table_name ORDER BY test_date DESC LIMIT 1", ARRAY_A);
    }

    public function get_benchmark_results($limit = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY test_date DESC LIMIT %d", $limit), ARRAY_A);
    }


    public function get_latest_results_by_region() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        
        $query = "
            SELECT r1.*
            FROM $table_name r1
            INNER JOIN (
                SELECT region_name, MAX(test_time) as max_time
                FROM $table_name
                GROUP BY region_name
            ) r2 ON r1.region_name = r2.region_name AND r1.test_time = r2.max_time
            ORDER BY r1.region_name
        ";

        return $wpdb->get_results($query);
    }

    public function delete_all_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        $wpdb->query("TRUNCATE TABLE $table_name");

        $benchmark_table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        $wpdb->query("TRUNCATE TABLE $benchmark_table_name");
    }

    public function purge_old_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE test_time < %s", $one_week_ago));

        $benchmark_table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        $wpdb->query($wpdb->prepare("DELETE FROM $benchmark_table_name WHERE test_date < %s", $one_week_ago));
    }

    public function get_fastest_and_slowest_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
    
        $query = "
            SELECT region_name, 
                   MIN(latency) AS fastest_latency, 
                   MAX(latency) AS slowest_latency
            FROM $table_name
            GROUP BY region_name
        ";
    
        return $wpdb->get_results($query);
    }

    public function get_results_by_time_range($time_range) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        
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
            SELECT * FROM $table_name
            WHERE test_time >= NOW() - INTERVAL $time_limit
            ORDER BY test_time ASC
        ");
    
        return $wpdb->get_results($query);
    }

    public function get_benchmark_results_by_time_range($time_range) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_benchmark_results';
        
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
            SELECT * FROM $table_name
            WHERE test_date >= NOW() - INTERVAL $time_limit
            ORDER BY test_date ASC
        ");
    
        return $wpdb->get_results($query);
    }

    public function speedvitals_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $test_results_table = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';
        $scheduled_tests_table = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        $sql = "CREATE TABLE $test_results_table (
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
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE $scheduled_tests_table (
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

    public function speedvitals_insert_test_result($result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        $wpdb->insert(
            $table_name,
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
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name ORDER BY test_date DESC LIMIT %d", $limit),
            ARRAY_A
        );
    }

    public function speedvitals_get_test_result($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );
    }

    public function speedvitals_schedule_test($url, $location, $device, $frequency) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        $next_run = $this->speedvitals_calculate_next_run($frequency);

        return $wpdb->insert(
            $table_name,
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
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY next_run ASC", ARRAY_A);
    }

    public function speedvitals_cancel_scheduled_test($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        return $wpdb->delete($table_name, array('id' => $id));
    }

    public function speedvitals_delete_old_results($days) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        $date = date('Y-m-d H:i:s', strtotime("-$days days"));

        return $wpdb->query(
            $wpdb->prepare("DELETE FROM $table_name WHERE test_date < %s", $date)
        );
    }

    public function speedvitals_update_test_result($id, $result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        return $wpdb->update(
            $table_name,
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
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_tests';

        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status NOT IN ('success', 'failed') ORDER BY test_date ASC",
            ARRAY_A
        );
    }

    public function speedvitals_update_scheduled_test($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        $scheduled_test = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        if (!$scheduled_test) {
            return false;
        }
        

        $next_run = $this->speedvitals_calculate_next_run($scheduled_test['frequency'], current_time('mysql'));

        return $wpdb->update(
            $table_name,
            array(
                'last_run' => current_time('mysql'),
                'next_run' => $next_run
            ),
            array('id' => $id)
        );
    }

    public function speedvitals_get_due_scheduled_tests() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpspeedtestpro_speedvitals_scheduled_tests';

        $current_time = current_time('mysql');

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE next_run <= %s", $current_time),
            ARRAY_A
        );
    }       

} // End of class