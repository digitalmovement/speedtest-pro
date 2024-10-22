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
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version, $core) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->init_components();
        $this->add_hooks(); // Make sure add_hooks is called
    }
    
    private function init_components() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    private function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_get_provider_packages', array($this, 'ajax_get_provider_packages'));
        add_action('wp_ajax_ssl_register_user', array($this, 'ajax_ssl_register_user'));
        add_action('wp_ajax_ssl_login_user', array($this, 'ajax_ssl_login_user'));
    }

    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speed-test-pro_page_wpspeedtestpro-settings';    
        }
    }


    /**
     * Register the stylesheets for the settings area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if ($this->is_this_the_right_plugin_page()) {
            wp_enqueue_style( $this->plugin_name . '-settings', plugin_dir_url( __FILE__ ) . 'css/wpspeedtestpro-settings.css', array(), $this->version, 'all' );
        }
    }

    /**
     * Register the JavaScript for the settings area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if ($this->is_this_the_right_plugin_page()) {
            wp_enqueue_script($this->plugin_name . '-settings', 
                plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-settings.js', 
                array('jquery'), 
                $this->version, 
                false
            );
            
            wp_localize_script($this->plugin_name . '-settings', 'wpspeedtestpro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'selected_region' => get_option('wp_hosting_benchmarking_selected_region'),
                'hosting_providers' => $this->core->api->get_hosting_providers_json()
            ));
        }
    }
    


    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_settings() {
     //   $this->enqueue_styles();
     //   $this->enqueue_scripts();
       include_once( 'partials/wpspeedtestpro-settings-display.php' );
    }

    /**
     * Register settings for the plugin
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings
 
        register_setting(
            'wpspeedtestpro_settings_group',
            'wpspeedtestpro_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );

        register_setting('wpspeedtestpro_settings_group', 'wpspeedtestpro_selected_region');
        register_setting('wpspeedtestpro_settings_group', 'wpspeedtestpro_selected_provider');
        register_setting('wpspeedtestpro_settings_group', 'wpspeedtestpro_selected_package');
        register_setting('wpspeedtestpro_settings_group', 'wpspeedtestpro_allow_data_collection', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'boolval'
        ));
        register_setting( 'wpspeedtestpro_settings_group', 'wpspeedtestpro_speedvitals_api_key' );
        register_setting( 'wpspeedtestpro_settings_group', 'wpspeedtestpro_uptimerobot_api_key' );
    
        // Add settings section
        add_settings_section(
            'wpspeedtestpro_section',
            'General Settings',
            null,
            'wpspeedtestpro-settings'
        );

        // Add settings fields
        add_settings_field(
            'wpspeedtestpro_selected_region',
            'Select Closest GCP Region',
            array($this, 'gcp_region_dropdown_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
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

        add_settings_field(
            'wpspeedtestpro_allow_data_collection',
            'Allow anonymous data collection',
            array($this, 'render_data_collection_field'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

        add_settings_field(
            'wpspeedtestpro_speedvitals_api_key',
            'SpeedVitals API Key',
            array($this, 'speedvitals_api_key_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

        add_settings_field(
            'wpspeedtestpro_uptimerobot_api_key',
            'UptimeRobot API Key',
            array($this, 'uptimerobot_api_key_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

        add_settings_field(
            'wpspeedtestpro_ssl_user_email',
            'SSL Credentials',
            array($this, 'ssl_register_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

    }

    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        if (isset($input['wpspeedtestpro_selected_region'])) {
            $sanitized_input['wpspeedtestpro_selected_region'] = sanitize_text_field($input['wpspeedtestpro_selected_region']);
        }
        
        if (isset($input['wpspeedtestpro_selected_provider'])) {
            $sanitized_input['wpspeedtestpro_selected_provider'] = sanitize_text_field($input['wpspeedtestpro_selected_provider']);
        }
        
        if (isset($input['wpspeedtestpro_selected_package'])) {
            $sanitized_input['wpspeedtestpro_selected_package'] = sanitize_text_field($input['wpspeedtestpro_selected_package']);
        }
        
        if (isset($input['wpspeedtestpro_allow_data_collection'])) {
            $sanitized_input['wpspeedtestpro_allow_data_collection'] = (bool) $input['wpspeedtestpro_allow_data_collection'];
        }
        
        if (isset($input['wpspeedtestpro_speedvitals_api_key'])) {
            $sanitized_input['wpspeedtestpro_speedvitals_api_key'] = sanitize_text_field($input['wpspeedtestpro_speedvitals_api_key']);
        }

        if (isset($input['wpspeedtestpro_uptimerobot_api_key'])) {
            $sanitized_input['wpspeedtestpro_uptimerobot_api_key'] = sanitize_text_field($input['wpspeedtestpro_uptimerobot_api_key']);
        }

        return $sanitized_input;
    }



    // Callback to display the GCP region dropdown
    public function gcp_region_dropdown_callback() {
        $selected_region = get_option('wpspeedtestpro_selected_region');
        $gcp_endpoints = $this->core->api->get_gcp_endpoints();

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
            echo '<p>No GCP endpoints available. Please check your internet connection or try again later.</p>';
        }
        echo '<p class="description">Please select the region closest to where most of your customers or visitors are based.</p>';
    }

    public function hosting_provider_dropdown_callback() {
        $selected_provider = get_option('wpspeedtestpro_selected_provider');
        $providers = $this->core->api->get_hosting_providers();

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
            echo '<p class="wpspeedtestpro-error">No hosting providers available. Please check your internet connection or try again later.</p>';
        }
    }

    public function hosting_package_dropdown_callback() {
        $selected_provider = get_option('wpspeedtestpro_selected_provider');
        $selected_package = get_option('wpspeedtestpro_selected_package');
        $providers = $this->core->api->get_hosting_providers();
    
        echo '<select id="wpspeedtestpro_selected_package" name="wpspeedtestpro_selected_package">';
        echo '<option value="">Select a package</option>';
    
        if ($selected_provider && !empty($providers)) {
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

    public function render_data_collection_field() {
        $option = get_option('wpspeedtestpro_allow_data_collection', true);
        ?>
        <input type="checkbox" id="wpspeedtestpro_allow_data_collection" name="wpspeedtestpro_allow_data_collection" value="1" <?php checked($option, true); ?>>
        <label for="wpspeedtestpro_allow_data_collection">Allow anonymous data collection</label>
        <p class="description">Help improve our plugin by allowing anonymous data collection. <a href="https://wpspeedtestpro.com/privacy-policy" target="_blank">Learn more about our privacy policy</a>.</p>
        <?php
    }

    private function get_gcp_endpoints() {
        try {
            $gcp_endpoints = $this->core->api->get_gcp_endpoints();
            if (empty($gcp_endpoints)) {
                throw new Exception('No GCP endpoints returned from API');
            }
            return $gcp_endpoints;
        } catch (Exception $e) {
            error_log('Error fetching GCP endpoints: ' . $e->getMessage());
            // Return some default regions if API call fails
            return array(
                array('region_name' => 'us-central1'),
                array('region_name' => 'europe-west1'),
                array('region_name' => 'asia-east1')
            );
        }
    }

    private function get_hosting_providers() {
        try {
            $providers = $this->core->api->get_hosting_providers();
            if (empty($providers)) {
                throw new Exception('No hosting providers returned from API');
            }
            return $providers;
        } catch (Exception $e) {
            error_log('Error fetching hosting providers: ' . $e->getMessage());
            // Return some default providers if API call fails
            return array(
                array('name' => 'Provider A', 'packages' => array(array('type' => 'Basic'), array('type' => 'Pro'))),
                array('name' => 'Provider B', 'packages' => array(array('type' => 'Starter'), array('type' => 'Business')))
            );
        }
    }

    public function ajax_get_provider_packages() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $provider_name = sanitize_text_field($_POST['provider']);
        $providers = $this->core->api->get_hosting_providers();

        $packages = array();
        foreach ($providers as $provider) {
            if ($provider['name'] === $provider_name) {
                $packages = $provider['packages'];
                break;
            }
        }

        wp_send_json_success($packages);
    }
    public function ajax_ssl_register_user() {
        // Verify nonce
        if (!check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token.');
            return;
        }
    
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'email', 'organization');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("$field is required.");
                return;
            }
        }
    
        // Sanitize input
        $user_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'organization' => sanitize_text_field($_POST['organization'])
        );
    
        // Validate email
        if (!is_email($user_data['email'])) {
            wp_send_json_error('Invalid email address.');
            return;
        }
    
        try {
            // Attempt to register user via API
            $api_response = $this->core->api->register_ssl_user(
                $user_data['first_name'],
                $user_data['last_name'],
                $user_data['email'],
                $user_data['organization']
            );
    
            if ($api_response['success']) {
                update_option('wpspeedtestpro_user_ssl_email', $user_data['email']);
                wp_send_json_success('Registration successful!');
            } else {
                wp_send_json_error($api_response['message'] ?? 'Registration failed.');
            }
        } catch (Exception $e) {
            wp_send_json_error('An error occurred during registration. Please try again.');
        }
    }
    
    public function ajax_ssl_login_user() {
        // Verify nonce
        if (!check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token.');
            return;
        }
    
        // Validate email
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address.');
            return;
        }
    
        try {
            // You might want to verify the email exists in your system here
            update_option('wpspeedtestpro_user_ssl_email', $email);
            wp_send_json_success('Email Saved! ');
        } catch (Exception $e) {
            wp_send_json_error('An error occurred during login. Please try again.');
        }
    }



    public function speedvitals_api_key_callback() {
        $api_key = get_option('wpspeedtestpro_speedvitals_api_key');
        echo '<input type="text" id="wpspeedtestpro_speedvitals_api_key" name="wpspeedtestpro_speedvitals_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">Enter your SpeedVitals API key. You can find your API key in your <a href="https://app.speedvitals.com/account" target="_blank">SpeedVitals account settings</a>.</p>';
    }

    public function uptimerobot_api_key_callback() {
        $api_key = get_option('wpspeedtestpro_uptimerobot_api_key');
        echo '<input type="text" id="wpspeedtestpro_uptimerobot_api_key" name="wpspeedtestpro_uptimerobot_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">Enter your UptimeRobot API key. You can find your API key in your <a href="https://dashboard.uptimerobot.com/integrations?rid=97f3dfd4e3a8a6" target="_blank">Uptime account settings</a>. <br /> Please create a <b>Main API key</b></p>';
    }

    public function ssl_register_callback() {
        $user_email = get_option('wpspeedtestpro_user_ssl_email', '');
    
        // If we have a saved email, default to login view, otherwise show register
        $default_view = !empty($user_email) ? 'login' : 'register';
        
        echo '<div id="user-auth-form">';
        echo '<select id="auth-action">';
        echo '<option value="login" ' . selected($default_view, 'login', false) . '>Login</option>';
        echo '<option value="register" ' . selected($default_view, 'register', false) . '>Register</option>';
        echo '</select>';
        
        // Add proper spacing and styling
        echo '<div class="auth-fields" style="margin-top: 10px;">';
        
        // Registration fields - hidden by default if we have an email
        $reg_style = ($default_view === 'login') ? 'display:none;' : '';
        echo '<input type="text" id="first-name" placeholder="First Name" style="' . $reg_style . 'margin-bottom: 5px; width: 100%; max-width: 25em;">';
        echo '<input type="text" id="last-name" placeholder="Last Name" style="' . $reg_style . 'margin-bottom: 5px; width: 100%; max-width: 25em;">';
        echo '<input type="text" id="organization" placeholder="Organization" style="' . $reg_style . 'margin-bottom: 5px; width: 100%; max-width: 25em;">';
        
        // Email field - always visible with saved value
        echo '<input type="email" id="email" placeholder="Email" value="' . esc_attr($user_email) . '" style="margin-bottom: 5px; width: 100%; max-width: 25em;">';
        
        echo '</div>';
        
        // Submit button
        echo '<button id="auth-submit" class="button button-secondary">' . 
             ($default_view === 'login' ? 'Login' : 'Register') . 
             '</button>';
        
        // Message container
        echo '<p id="auth-message" style="display: none;"></p>';
        echo '</div>';
        
        // Description
        echo '<p class="description">The information for SSL testing is sent to SSLLabs.com. ' .
             '<a href="https://www.qualys.com/company/privacy" target="_blank">View their Privacy Policy</a>.</p>';
    }   
}

