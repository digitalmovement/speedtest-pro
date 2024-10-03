<?php

/**
 * The uptime monitoring functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The uptime monitoring functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the uptime monitoring functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Uptime_Monitoring {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $core;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version, $core ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
    }
    /**
     * Register the stylesheets for the uptime monitoring area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-uptime-monitoring', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-uptime-monitoring.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the uptime monitoring area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name . '-uptime-monitoring', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-uptime-monitoring.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Render the uptime monitoring page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_uptime_monitoring() {
        include_once( 'partials/wpspeedtestpro-admin-uptime-monitoring-display.php' );
    }

    // Add more methods as needed for uptime monitoring functionality
}