<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_i18n {

	/**
	 * Initialize internationalization functionality.
	 *
	 * @since    1.0.0
	 * @note     Since WordPress 4.6, plugins hosted on WordPress.org 
	 *           automatically load translations. No manual loading required.
	 */
	public function load_plugin_textdomain() {
		// Since WordPress 4.6, plugins hosted on WordPress.org
		// automatically load translations. No action needed.
		// Translation files should be placed in /languages/ directory
		// and follow the naming convention: speedtest-pro-{locale}.po/.mo
	}
}
