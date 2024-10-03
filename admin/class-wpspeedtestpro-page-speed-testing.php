<?php

/**
 * The page speed testing functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The page speed testing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the page speed testing functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Page_Speed_Testing {

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
     * Register the stylesheets for the page speed testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-page-speed-testing', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-page-speed-testing.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the page speed testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name . '-page-speed-testing', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-page-speed-testing.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Render the page speed testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_page_speed_testing() {
        include_once( 'partials/wpspeedtestpro-admin-page-speed-testing-display.php' );
    }

    // Add more methods as needed for page speed testing functionality
}