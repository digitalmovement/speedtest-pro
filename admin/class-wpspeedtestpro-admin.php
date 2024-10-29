<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Admin {

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

    /**
     * The core functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Wpspeedtestpro_Core    $core    The core functionality instance.
     */
    private $core;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Wpspeedtestpro_Core    $core    The core functionality instance.
     */

    private $latency_testing;
    private $ssl_testing;
    private $settings;
    private $server_performance;
    private $uptime_monitoring;
    private $page_speed_testing;

    private $server_information;

    public function __construct( $plugin_name, $version, $core ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;

        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for the Admin facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-dashboard.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-server-information.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-latency-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-server-performance.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-ssl-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-uptime-monitoring.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-page-speed-testing.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpspeedtestpro-settings.php';

        $this->latency_testing      = new Wpspeedtestpro_Latency_Testing( $this->plugin_name, $this->version, $this->core );
        $this->settings             = new Wpspeedtestpro_Settings( $this->plugin_name, $this->version, $this->core );
        $this->ssl_testing          = new Wpspeedtestpro_SSL_Testing( $this->plugin_name, $this->version, $this->core );
        $this->server_performance   = new Wpspeedtestpro_Server_Performance( $this->plugin_name, $this->version, $this->core );
        $this->uptime_monitoring    = new Wpspeedtestpro_Uptime_Monitoring( $this->plugin_name, $this->version, $this->core );
        $this->page_speed_testing   = new Wpspeedtestpro_Page_Speed_Testing( $this->plugin_name, $this->version, $this->core );
        $this->server_information = new Wpspeedtestpro_Server_Information($this->plugin_name, $this->version, $this->core);
   

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-admin.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true );
        wp_enqueue_script('chart-date-js', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js', array(), '3.7.0', true);

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        
        // Enqueue jQuery UI CSS
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_server_performance_scripts' ) );
    }

    /**
     * Enqueue scripts specifically for the server performance page.
     *
     * @since    1.0.0
     */
    // The below function is not used in the plugin - canditate for removal
     public function enqueue_server_performance_scripts($hook) {
        if ($hook === 'wpspeedtestpro_page_' . $this->plugin_name . '-server-performance') {
            wp_enqueue_script( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-server-performance.js', array( 'jquery', 'chart-js' ), $this->version, false );
            wp_enqueue_style( $this->plugin_name . '-server-performance', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-server-performance.css', array(), $this->version, 'all' );
        }
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_menu_page( 'WP Speed Test Pro', 'WP Speed Test Pro', 'manage_options', $this->plugin_name, array($this, 'display_plugin_dashboard_page'), 'dashicons-performance', 99 );
       
        //add_submenu_page( $this->plugin_name, 'Dashboard', 'Dashboard', 'manage_options', $this->plugin_name, array($this, 'display_plugin_dashboard_page') );
        add_submenu_page( $this->plugin_name, 'Server Information','Server Information','manage_options',$this->plugin_name . '-server-information',array($this, 'display_plugin_server_information_page'));
        add_submenu_page( $this->plugin_name, 'Server Performance', 'Server Performance', 'manage_options', $this->plugin_name . '-server-performance', array($this, 'display_plugin_server_performance_page') );
        add_submenu_page( $this->plugin_name, 'Latency Testing', 'Latency Testing', 'manage_options', $this->plugin_name . '-latency-testing', array($this, 'display_plugin_latency_testing_page') );
        add_submenu_page( $this->plugin_name, 'SSL Testing', 'SSL Testing', 'manage_options', $this->plugin_name . '-ssl-testing', array($this, 'display_plugin_ssl_testing_page') );
        add_submenu_page( $this->plugin_name, 'Uptime Monitoring', 'Uptime Monitoring', 'manage_options', $this->plugin_name . '-uptime-monitoring', array($this, 'display_plugin_uptime_monitoring_page') );
        add_submenu_page( $this->plugin_name, 'Page Speed Testing', 'Page Speed Testing', 'manage_options', $this->plugin_name . '-page-speed-testing', array($this, 'display_plugin_page_speed_testing_page') );
        add_submenu_page( $this->plugin_name, 'Settings', 'Settings', 'manage_options', $this->plugin_name . '-settings', array($this, 'display_plugin_settings_page') );
    }

    /**
     * Render the dashboard page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_dashboard_page() {
        $dashboard = new Wpspeedtestpro_Dashboard( $this->plugin_name, $this->version, $this->core );
        $dashboard->display_dashboard();
    }

    public function display_plugin_server_information_page() {
        $this->server_information->display_server_information();
    }


    public function change_plugin_icon() {
        echo '<style>
            #adminmenu #toplevel_page_wpspeedtestpro  div.wp-menu-image {
                background-image: url(' . plugins_url('/admin/assets/icon.svg', __FILE__) . ');
                background-size: contain;
            }
            #adminmenu #toplevel_page_wpspeedtestpro-slug img {
                display: none;
            }
        </style>';
    }

    /**
     * Render the latency testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_latency_testing_page() {
        $this->latency_testing->display_latency_testing();
    }

    /**
     * Render the server performance page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_server_performance_page() {
        $this->server_performance->display_server_performance();
    }

    /**
     * Render the SSL testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_ssl_testing_page() {
        $this->ssl_testing->display_ssl_testing();
    }

    /**
     * Render the uptime monitoring page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_uptime_monitoring_page() {
        $this->uptime_monitoring->display_uptime_monitoring();
    }

    /**
     * Render the page speed testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_page_speed_testing_page() {
        $this->page_speed_testing->display_page_speed_testing();
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
       $this->settings->display_settings();
    }
}