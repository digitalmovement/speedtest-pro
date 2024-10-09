<?php

/**
 * The core functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 */

/**
 * The core functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/includes
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Core {

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    public $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    public $version;

    /**
     * The API instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wpspeedtestpro_API    $api    The API instance.
     */
    public $api;

    /**
     * The DB instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wpspeedtestpro_DB    $db    The DB instance.
     */
    public  $db;

    public $cron;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
        $this->init_api();
        $this->init_db();
        $this->init_cron();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for API functionality
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpspeedtestpro-api.php';

        /**
         * The class responsible for database operations
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpspeedtestpro-db.php';
    }

    /**
     * Initialize the API.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_api() {
        $this->api = new Wpspeedtestpro_API();
    }

    /**
     * Initialize the DB.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_db() {
        $this->db = new Wpspeedtestpro_DB();
    }

    private function init_cron() {
        $this->cron = new Wpspeedtestpro_Cron();
    }   
    /**
     * Get the API instance.
     *
     * @since    1.0.0
     * @return   Wpspeedtestpro_API    The API instance.
     */
    public function get_api() {
        return $this->api;
    }

    /**
     * Get the DB instance.
     *
     * @since    1.0.0
     * @return   Wpspeedtestpro_DB    The DB instance.
     */
    public function get_db() {
        return $this->db;
    }
}