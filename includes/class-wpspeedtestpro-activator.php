<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        // Ensure the DB class is loaded
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpspeedtestpro-db.php';

        // Create an instance of the DB class
        $db = new Wpspeedtestpro_DB();

        // Call the create_table method
        $db->create_table();
        $db->create_benchmark_table();
        $db->speedvitals_create_tables();
        $db->create_pagespeed_tables();

        // Any other activation tasks can be added here

              // Schedule cron job for running scheduled tests
              if (!wp_next_scheduled('wpspeedtestpro_daily_pagespeed_check')) {
                wp_schedule_event(time(), 'daily', 'wpspeedtestpro_daily_pagespeed_check');
            }
    
            // Add default options
            add_option('wpspeedtestpro_pagespeed_settings', [
                'retention_days' => 90,
                'auto_test_new_content' => true,
                'email_notifications' => false,
                'notification_email' => get_option('admin_email'),
                'minimum_score_alert' => 80
            ]);
    }
}
