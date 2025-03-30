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
        add_action('admin_init', array($this, 'handle_settings_saved'));

    }

    private function add_hooks() {
        add_action('wp_ajax_wpspeedtestpro_get_provider_packages', array($this, 'ajax_get_provider_packages'));
        add_action('wp_ajax_wpspeedtestpro_get_hosting_providers', array($this, 'ajax_get_hosting_providers'));
        add_action('wp_ajax_wpspeedtestpro_get_gcp_endpoints', array($this, 'ajax_get_gcp_endpoints'));
    }

    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speedtest-pro_page_wpspeedtestpro-settings';    
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
                'hosting_providers' => $this->core->api->get_hosting_providers_json(),
                'saved_country' => get_option('wpspeedtestpro_user_country') // Add this line
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
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array()
            )
        );

        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_selected_region',
            'sanitize_text_field'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_selected_provider',
            'absint'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_selected_package',
            'sanitize_text_field'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_allow_data_collection', 
            'boolval'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_uptimerobot_api_key',
            'sanitize_text_field'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_pagespeed_api_key',
            'sanitize_text_field'
        );
        
        register_setting(
            'wpspeedtestpro_settings_group', 
            'wpspeedtestpro_user_country',
            'sanitize_text_field'
        );

        // Add settings section
        add_settings_section(
            'wpspeedtestpro_section',
            'General Settings',
            null,
            'wpspeedtestpro-settings'
        );

            // Add the field to the settings page
        add_settings_field(
            'wpspeedtestpro_user_country',
            'User Base Country',    
            array($this, 'render_country_field'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section',
            array('before' => 'wpspeedtestpro_selected_region') // Add before GCP region
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
            'wpspeedtestpro_uptimerobot_api_key',
            'UptimeRobot API Key',
            array($this, 'uptimerobot_api_key_callback'),
            'wpspeedtestpro-settings',
            'wpspeedtestpro_section'
        );

/*        add_settings_section(
            'pagespeed_settings_section',
            'PageSpeed Insights Settings',
            null,
            'wpspeedtestpro-settings'
        );
*/
        add_settings_field(
            'pagespeed_api_key',
            'PageSpeed Insights API Key',
            array($this, 'render_pagespeed_api_key_field'),
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
        
    }

    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        $errors = array();

        // Sanitize User Country
        if (isset($input['wpspeedtestpro_user_country'])) {
            $country = sanitize_text_field($input['wpspeedtestpro_user_country']);
            // Verify it's a valid country code
            if (array_key_exists($country, $this->get_countries_list())) {
                $sanitized_input['wpspeedtestpro_user_country'] = $country;
            } else {
                $errors[] = 'Invalid country selected.';
            }
        }
        
        // Sanitize GCP Region
        if (isset($input['wpspeedtestpro_selected_region'])) {
            $region = sanitize_text_field($input['wpspeedtestpro_selected_region']);
            // Verify it's a valid GCP region
            $valid_regions = $this->core->api->get_gcp_endpoints();
            $valid_region_names = array_column($valid_regions, 'region_name');
            if (in_array($region, $valid_region_names)) {
                $sanitized_input['wpspeedtestpro_selected_region'] = $region;
            } else {
                $errors[] = 'Invalid GCP region selected.';
            }
        }
        
        // Sanitize Provider ID
        if (isset($input['wpspeedtestpro_selected_provider'])) {
            $provider_id = absint($input['wpspeedtestpro_selected_provider']);
            // Verify it's a valid provider
            $providers = $this->core->api->get_hosting_providers();
            $valid_provider_ids = array_column($providers, 'id');
            if (in_array($provider_id, $valid_provider_ids)) {
                $sanitized_input['wpspeedtestpro_selected_provider'] = $provider_id;
            } else {
                $errors[] = 'Invalid hosting provider selected.';
            }
        }
        
        // Sanitize Package ID
        if (isset($input['wpspeedtestpro_selected_package'])) {
            $package_id = sanitize_text_field($input['wpspeedtestpro_selected_package']);
            // Package validation would go here - needs to match the selected provider's packages
            $sanitized_input['wpspeedtestpro_selected_package'] = $package_id;
        }
        
        // Sanitize Data Collection Flag
        if (isset($input['wpspeedtestpro_allow_data_collection'])) {
            $sanitized_input['wpspeedtestpro_allow_data_collection'] = 
                (bool) $input['wpspeedtestpro_allow_data_collection'];
        }
        
        // Sanitize UptimeRobot API Key
        if (isset($input['wpspeedtestpro_uptimerobot_api_key'])) {
            $api_key = sanitize_text_field($input['wpspeedtestpro_uptimerobot_api_key']);
            if (empty($api_key) || $this->validate_uptimerobot_api_key($api_key)) {
                $sanitized_input['wpspeedtestpro_uptimerobot_api_key'] = $api_key;
            } else {
                $errors[] = 'Invalid UptimeRobot API key format.';
            }
        }
    
        // Sanitize PageSpeed API Key
        if (isset($input['wpspeedtestpro_pagespeed_api_key'])) {
            $api_key = sanitize_text_field($input['wpspeedtestpro_pagespeed_api_key']);
            if (empty($api_key) || $this->validate_pagespeed_api_key($api_key)) {
                $sanitized_input['wpspeedtestpro_pagespeed_api_key'] = $api_key;
            } else {
                $errors[] = 'Invalid PageSpeed API key format.';
            }
        }
    
        // If there are any errors, add them as error messages
        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error(
                    'wpspeedtestpro_messages',
                    'wpspeedtestpro_error',
                    $error,
                    'error'
                );
            }
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
                $region = esc_attr($endpoint['region']);
                echo '<option value="' . esc_attr($region) . '"' . selected($selected_region, $region, false) . '>';
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
        $selected_provider_id = get_option('wpspeedtestpro_selected_provider');
        $providers = $this->core->api->get_hosting_providers();
    
        if (!empty($providers)) {
            echo '<select id="wpspeedtestpro_selected_provider" name="wpspeedtestpro_selected_provider">';
            echo '<option value="">Select a provider</option>';
            foreach ($providers as $provider) {
                $provider_id = esc_attr($provider['id']);
                echo '<option value="' . esc_attr($provider_id) . '"' . selected($selected_provider_id, $provider_id, false) . '>';
                echo esc_html($provider['name']);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p class="wpspeedtestpro-error">No hosting providers available. Please check your internet connection or try again later.</p>';
        }
    }
    
    public function hosting_package_dropdown_callback() {
        $selected_provider_id = get_option('wpspeedtestpro_selected_provider');
        $selected_package_id = get_option('wpspeedtestpro_selected_package');
        $providers = $this->core->api->get_hosting_providers();
    
        echo '<select id="wpspeedtestpro_selected_package" name="wpspeedtestpro_selected_package">';
        echo '<option value="">Select a package</option>';
    
        if ($selected_provider_id && !empty($providers)) {
            foreach ($providers as $provider) {
                if ($provider['id'] == $selected_provider_id) {
                    foreach ($provider['packages'] as $package) {
                        $package_id = esc_attr($package['Package_ID']);
                        echo '<option value="' . esc_attr($package_id) . '"' . selected($selected_package_id, $package_id, false) . '>';
                        echo esc_html($package['type'] . ' - ' . $package['description']);
                        echo '</option>';
                    }
                    break;
                }
            }
        }
        echo '</select>';
    
        if (!$selected_provider_id) {
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

    public function ajax_get_gcp_endpoints() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        try {
            $gcp_endpoints = $this->core->api->get_gcp_endpoints();
            if (empty($gcp_endpoints)) {
                throw new Exception('No GCP endpoints returned from API');
            }
            return wp_send_json_success($gcp_endpoints);
        } catch (Exception $e) {
            error_log('Error fetching GCP endpoints: ' . $e->getMessage());
            // Return some default regions if API call fails
            return wp_send_json_error(array(
                array('region_name' => 'us-central1'),
                array('region_name' => 'europe-west1'),
                array('region_name' => 'asia-east1')
            ));
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
    
        $provider_id = isset($_POST['provider']) ? absint($_POST['provider']) : 0; // Convert to integer and sanitize
        $providers = $this->core->api->get_hosting_providers();
    
        $packages = array();
        foreach ($providers as $provider) {
            if ($provider['id'] === $provider_id) {
                $packages = $provider['packages'];
                break;
            }
        }
    
        wp_send_json_success($packages);
    }
    

    public function ajax_get_hosting_providers() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $hosting_providers = $this->core->api->get_hosting_providers();

        wp_send_json_success($hosting_providers);
    }
    


    
    public function uptimerobot_api_key_callback() {
        $api_key = sanitize_text_field(get_option('wpspeedtestpro_uptimerobot_api_key', ''));
        echo '<input type="text" id="wpspeedtestpro_uptimerobot_api_key" name="wpspeedtestpro_uptimerobot_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">Enter your UptimeRobot API key. You can find your API key in your <a href="https://dashboard.uptimerobot.com/integrations?rid=97f3dfd4e3a8a6" target="_blank">Uptime account settings</a>. <br /> Please create a <b>Main API key</b></p>';
    }

    public static function render_pagespeed_api_key_field() {
        $api_key = sanitize_text_field(get_option('wpspeedtestpro_pagespeed_api_key', ''));
        ?>
        <input type="text" 
               name="wpspeedtestpro_pagespeed_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <p class="description">
            Enter your Google PageSpeed Insights API key. 
            <a href="https://developers.google.com/speed/docs/insights/v5/get-started" 
               target="_blank">Get an API key</a>
        </p>
        <?php
    }

        // Add this method to render the country dropdown
    public function render_country_field() {
        $selected_country = get_option('wpspeedtestpro_user_country');
        ?>
        <select id="wpspeedtestpro_user_country" name="wpspeedtestpro_user_country">
            <option value="">Select a country</option>
            <?php
            // Add a complete list of countries
            $countries = $this->get_countries_list();

            foreach ($countries as $code => $name) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($code),
                    selected($selected_country, $code, false),
                    esc_html($name)
                );
            }
            ?>
        </select>
        <p class="description">Select the primary country where most of your users are located.</p>
        <?php
    }

    private function validate_uptimerobot_api_key($api_key) {
        // UptimeRobot API keys are typically 32 characters
        return (bool) preg_match('/^[a-zA-Z0-9]{32}$/', $api_key);
    }
    
    /**
     * Validate PageSpeed API key format
     * 
     * @param string $api_key The API key to validate
     * @return bool Whether the API key format is valid
     */
    private function validate_pagespeed_api_key($api_key) {
        // Google API keys are typically 39 characters
        return (bool) preg_match('/^AIza[0-9A-Za-z-_]{35}$/', $api_key);
    }
    
    /**
     * Get list of countries
     * 
     * @return array Array of country codes and names
     */
    private function get_countries_list() {
        return array(
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, Democratic Republic of the',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote D\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and McDonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea, Democratic People\'s Republic of',
            'KR' => 'Korea, Republic of',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'North Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States of',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen',
            'SZ' => 'Eswatini',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Türkiye',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Minor Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe'
        );
    }
    
    /**
     * Save individual setting
     * 
     * @param string $option_name The option name
     * @param mixed $value The option value
     * @return bool Whether the option was saved successfully
     */
    private function save_setting($option_name, $value) {
        if (get_option($option_name) !== false) {
            return update_option($option_name, $value);
        } else {
            return add_option($option_name, $value);
        }
    }

    public function handle_settings_saved() {
        if (!isset($_GET['page']) || !wp_verify_nonce(wp_create_nonce('wpspeedtestpro-settings-nonce'), 'wpspeedtestpro-settings-nonce')) {
            return;
        }
        
        if (
            isset($_GET['settings-updated']) && 
            sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true'
        ) {
            add_settings_error(
                'wpspeedtestpro_messages',
                'settings_updated',
                __('Settings saved.', 'wpspeedtestpro'),
                'updated'
            );
        }
    }
}

