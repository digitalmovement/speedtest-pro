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
        $db->speedvitals_create_tables();

        // Any other activation tasks can be added here
    }
}
