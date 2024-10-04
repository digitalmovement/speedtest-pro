<?php

/**
 * The settings functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The settings functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the settings functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_Settings {

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
        $this->init_components();
    }

    private function init_components() {
        add_action('admin_init', array($this, 'register_settings'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
    }

    /**
     * Register the stylesheets for the settings area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-settings', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-settings.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the settings area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name . '-settings', plugin_dir_url( __FILE__ ) . 'js/wpspeedtestpro-settings.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_settings() {
        $this->enqueue_styles();
        $this->enqueue_scripts();
        include_once( 'partials/wpspeedtestpro-admin-settings-display.php' );
    }

    /**
     * Register settings for the plugin
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register a new setting for "wpspeedtestpro_settings"
        register_setting('wpspeedtestpro_settings', 'wpspeedtestpro_option');
        register_setting('wpspeedtestpro_settings', 'wpspeedtestpro_selected_region');

        register_setting('wpspeedtestpro_settings', 'wpspeedtestpro_selected_provider');
        register_setting('wpspeedtestpro_settings', 'wpspeedtestpro_selected_package');
        // Register new setting for anonymous data collection
        register_setting('wpspeedtestpro_settings', 'wpspeedtestpro_allow_data_collection', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'boolval'
        ));


        // Add a new section in the "Settings" page
        add_settings_section(
            'wpspeedtestpro_section', // Section ID
            'General Settings',                // Section title
            null,                              // Section callback (not needed)
            'wpspeedtestpro-settings' // Page slug
        );


        // Add a settings field (dropdown for GCP regions)
        add_settings_field(
            'wpspeedtestpro_selected_region', // Field ID
            'Select Closest GCP Region',               // Field title
            array($this, 'gcp_region_dropdown_callback'), // Callback to display the dropdown
            'wpspeedtestpro-settings',        // Page slug
            'wpspeedtestpro_section'          // Section ID (fixed to match)
        );

        add_settings_field(
            'wpspeedtestpro_selected_provider',
            'Select Hosting Provider',
            array($this, 'hosting_provider_dropdown_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

        add_settings_field(
            'wpspeedtestpro_selected_package',
            'Select Package',
            array($this, 'hosting_package_dropdown_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

              // Add a new field for anonymous data collection
        add_settings_field(
            'wpspeedtestpro_allow_data_collection',
            'Allow anonymous data collection',
             array($this, 'render_data_collection_field'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

    }

    // Callback to display the GCP region dropdown
    public function gcp_region_dropdown_callback() {

        $gcp_endpoints = $this->api->get_gcp_endpoints(); // Fetch GCP endpoints
        $selected_region = get_option('wpspeedtestpro_selected_region'); // Get selected region

        if (!empty($gcp_endpoints)) {
            echo '<select name="wpspeedtestpro_selected_region">';
            foreach ($gcp_endpoints as $endpoint) {
                $region_name = esc_attr($endpoint['region_name']);
                echo '<option value="' . $region_name . '"' . selected($selected_region, $region_name, false) . '>';
                echo esc_html($region_name);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>No GCP endpoints available.</p>';
        }
           // Explanation text
        echo '<p class="description">Please select the region closest to where most of your customers or visitors are based. </p>';
    }

   public function hosting_provider_dropdown_callback() {
        $providers = $this->api->get_hosting_providers();
        $selected_provider = get_option('wpspeedtestpro_selected_provider');

        if (!empty($providers)) {
            echo '<select id="wpspeedtestpro_selected_provider" name="wpspeedtestpro_selected_provider">';
            echo '<option value="">Select a provider</option>';
            foreach ($providers as $provider) {
                $provider_name = esc_attr($provider['name']);
                echo '<option value="' . $provider_name . '"' . selected($selected_provider, $provider_name, false) . '>';
                echo esc_html($provider_name);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>No hosting providers available.</p>';
        }
    }

    public function hosting_package_dropdown_callback() {
        $selected_provider = get_option('wpspeedtestpro_selected_provider');
        $selected_package = get_option('wpspeedtestpro_selected_package');
    
        echo '<select id="wpspeedtestpro_selected_package" name="wpspeedtestpro_selected_package">';
        echo '<option value="">Select a package</option>';
    
        if ($selected_provider) {
            $providers = $this->api->get_hosting_providers();
            foreach ($providers as $provider) {
                if ($provider['name'] === $selected_provider) {
                    foreach ($provider['packages'] as $package) {
                        $package_type = esc_attr($package['type']);
                        echo '<option value="' . $package_type . '"' . selected($selected_package, $package_type, false) . '>';
                        echo esc_html($package_type);
                        echo '</option>';
                    }
                    break;
                }
            }
        }
        echo '</select>';
    
        if (!$selected_provider) {
            echo '<p class="description">Please select a provider first.</p>';
        }
    }


    public function ajax_get_provider_packages() {
        check_ajax_referer('wpspeedtestpro_settings_nonce', 'nonce');

        $provider_name = sanitize_text_field($_POST['provider']);
        $providers = $this->api->get_hosting_providers();

        $packages = array();
        foreach ($providers as $provider) {
            if ($provider['name'] === $provider_name) {
                $packages = $provider['packages'];
                break;
            }
        }

        wp_send_json_success($packages);
    }

    public function render_data_collection_field() {
        $option = get_option('wpspeedtestpro_allow_data_collection', true);
        ?>
        <input type="checkbox" id="wpspeedtestpro_allow_data_collection" name="wpspeedtestpro_allow_data_collection" value="1" <?php checked($option, true); ?>>
        <label for="wpspeedtestpro_allow_data_collection">Allow anonymous data collection</label>
        <p class="description">Help improve our plugin by allowing anonymous data collection. <a href="https://wpspeedtestpro.com/privacy-policy" target="_blank">Learn more about our privacy policy</a>.</p>
        <?php
    }

    // Add more methods as needed for settings functionality
}