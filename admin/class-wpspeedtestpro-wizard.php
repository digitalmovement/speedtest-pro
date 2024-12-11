<?php
/**
 * The setup wizard functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

class Wpspeedtestpro_Wizard {
    private $plugin_name;
    private $version;
    private $core;

    public function __construct($plugin_name, $version, $core) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;

        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_footer', array($this, 'render_wizard'));
        
        // AJAX handlers
        add_action('wp_ajax_wpspeedtestpro_save_wizard_settings', array($this, 'save_wizard_settings'));
        add_action('wp_ajax_wpspeedtestpro_get_wizard_data', array($this, 'get_wizard_data'));
        add_action('wp_ajax_wpspeedtestpro_dismiss_wizard', array($this, 'dismiss_wizard'));
    }

    public function enqueue_styles() {
        if (!$this->should_show_wizard()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-wizard', 
            plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-wizard.css',
            array(),
            $this->version
        );
    }

    public function enqueue_scripts() {
        if (!$this->should_show_wizard()) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-wizard',
            plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-wizard.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name . '-wizard',
            'wpspeedtestpro_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'hosting_packages_nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
                'regions' => $this->core->api->get_gcp_endpoints(),
                'providers' => $this->core->api->get_hosting_providers()
            )
        );
    }

    private function should_show_wizard() {
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Check if we're on a WP Speed Test Pro admin page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpspeedtestpro') === false) {
            return false;
        }

        // Check if wizard has been completed or dismissed
        return !get_option('wpspeedtestpro_setup_completed', false);
    }

    public function render_wizard() {
        if (!$this->should_show_wizard()) {
            return;
        }

        include plugin_dir_path(__FILE__) . 'partials/wpspeedtestpro-wizard-display.php';
    }

    public function save_wizard_settings() {
        check_ajax_referer('wpspeedtestpro_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $settings = array(
            'gcp_region' => sanitize_text_field($_POST['region']),
            'hosting_provider' => sanitize_text_field($_POST['provider']),
            'hosting_package' => sanitize_text_field($_POST['package']),
            'allow_data_collection' => isset($_POST['allow_data_collection']) ? 
                (bool)$_POST['allow_data_collection'] : false,
            'uptimerobot_api_key' => sanitize_text_field($_POST['uptimerobot_key'])
        );

        // Save settings
        update_option('wpspeedtestpro_selected_region', $settings['gcp_region']);
        update_option('wpspeedtestpro_selected_provider', $settings['hosting_provider']);
        update_option('wpspeedtestpro_selected_package', $settings['hosting_package']);
        update_option('wpspeedtestpro_allow_data_collection', $settings['allow_data_collection']);
        
        if (!empty($settings['uptimerobot_api_key'])) {
            update_option('wpspeedtestpro_uptimerobot_api_key', $settings['uptimerobot_api_key']);
        }

        // Mark wizard as completed
        update_option('wpspeedtestpro_setup_completed', true);

        wp_send_json_success('Settings saved successfully');
    }

    public function get_wizard_data() {
        check_ajax_referer('wpspeedtestpro_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $data = array(
            'gcp_region' => get_option('wpspeedtestpro_selected_region'),
            'hosting_provider' => get_option('wpspeedtestpro_selected_provider'),
            'hosting_package' => get_option('wpspeedtestpro_selected_package'),
            'allow_data_collection' => get_option('wpspeedtestpro_allow_data_collection', true),
            'uptimerobot_api_key' => get_option('wpspeedtestpro_uptimerobot_api_key')
        );

        wp_send_json_success($data);
    }

    public function dismiss_wizard() {
        check_ajax_referer('wpspeedtestpro_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        update_option('wpspeedtestpro_setup_completed', true);
        wp_send_json_success('Wizard dismissed');
    }
}