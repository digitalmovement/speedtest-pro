<?php

class Wpspeedtestpro_Cron {

    public function __construct() {
    //    add_filter('cron_schedules', array($this, 'add_cron_interval'));
        add_filter('cron_schedules', array($this, 'register_cron_schedules'));

//        register_activation_hook( dirname( __FILE__ ), array($this, 'setup_cron'));
//        register_deactivation_hook( dirname( __FILE__ ), array($this, 'remove_cron'));    
    }

    /*public function add_cron_interval($schedules) {
        $schedules['wpspeedtestpro_fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => esc_html__('Every 15 minutes'),
        );
        return $schedules;
    }    
*/



    public function register_cron_schedules($schedules) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * 60, // 15 minutes in seconds
            'display'  => 'Every 15 Minutes'
        );
        return $schedules;
    }
    
    /**
     * Setup the cron job
     */
    public function setup_cron() {
        if (!wp_next_scheduled('wpspeedtestpro_check_scheduled_pagespeed_tests')) {
            wp_schedule_event(time(), 'fifteen_minutes', 'wpspeedtestpro_check_scheduled_pagespeed_tests');
        }
    }
    
    /**
     * Remove the cron job
     */
    public function remove_cron() {
        $timestamp = wp_next_scheduled('wpspeedtestpro_check_scheduled_pagespeed_tests');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpspeedtestpro_check_scheduled_pagespeed_tests');
        }
    }

    



}