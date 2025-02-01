<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpspeedtestpro.com
 * @since             1.0.0
 * @package           Wpspeedtestpro
 *
 * @wordpress-plugin
 * Plugin Name:       WP Speedtest Pro
 * Plugin URI:        https://wpspeedtestpro.com
 * Description:       An advanced plugin to test your WordPress Performance, including comprehensive server performance benchmarks.
 * Version:           1.1.1
 * Author:            WP Speedtest Pro Team
 * Author URI:        https://wpspeedtestpro.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpspeedtestpro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WPSPEEDTESTPRO_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpspeedtestpro-activator.php
 */
function activate_wpspeedtestpro() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpspeedtestpro-activator.php';
	Wpspeedtestpro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpspeedtestpro-deactivator.php
 */
function deactivate_wpspeedtestpro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpspeedtestpro-deactivator.php';
    Wpspeedtestpro_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpspeedtestpro' );
register_deactivation_hook( __FILE__, 'deactivate_wpspeedtestpro' );

function wpspeedtestpro_init() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpspeedtestpro-deactivator.php';
    Wpspeedtestpro_Deactivator::init();
}
add_action('init', 'wpspeedtestpro_init');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpspeedtestpro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpspeedtestpro() {

	$plugin = new Wpspeedtestpro();
	$plugin->run();
	//$admin = new Wpspeedtestpro_Admin( $plugin->get_plugin_name(), $plugin->get_version(), $plugin->get_core() );

}
run_wpspeedtestpro();
