<?php

class Wpspeedtestpro_Cron {
    public function __construct() {
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
    }

    public function add_cron_interval($schedules) {
        $schedules['wpspeedtestpro_fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => esc_html__('Every 15 minutes'),
        );
        return $schedules;
    }    


}