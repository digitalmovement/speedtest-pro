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
    private $plugin_name;
    private $version;
    private $core;
    private $api_key;
    private $ping_monitor_id;
    private $cron_monitor_id;

    public function __construct($plugin_name, $version, $core) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->api_key = get_option('wpspeedtestpro_uptimerobot_api_key');
        $this->ping_monitor_id = get_option('wpspeedtestpro_uptimerobot_ping_id');
        $this->cron_monitor_id = get_option('wpspeedtestpro_uptimerobot_cron_id');

        $this->uptimerobot_init_hooks();
    }

    private function uptimerobot_init_hooks() {
        add_action('wp_ajax_wpspeedtestpro_uptimerobot_get_monitor_data', array($this, 'uptimerobot_get_monitor_data_handler'));
        add_action('wp_ajax_wpspeedtestpro_uptimerobot_setup_monitors', array($this, 'uptimerobot_setup_monitors_handler'));
        add_action('wp_ajax_wpspeedtestpro_uptimerobot_delete_monitors', array($this, 'uptimerobot_delete_monitors_handler'));
        add_action('wp_ajax_wpspeedtestpro_uptimerobot_recreate_monitors', array($this, 'uptimerobot_recreate_monitors_handler'));
        add_action('wp_ajax_wpspeedtestpro_dismiss_uptime_info', array($this, 'dismiss_uptime_info'));
        add_action('admin_enqueue_scripts', array($this, 'uptimerobot_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'uptimerobot_enqueue_scripts'));
    }

    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speedtest-pro_page_wpspeedtestpro-uptime-monitoring';    
        }
    }

    public function uptimerobot_enqueue_styles() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_style($this->plugin_name . '-uptime-monitoring', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-uptime-monitoring.css', array(), $this->version, 'all');
    }

    public function uptimerobot_enqueue_scripts() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }

        wp_enqueue_script($this->plugin_name . '-uptime-monitoring', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-uptime-monitoring.js', array('jquery', 'chart-js'), $this->version, false);
        wp_localize_script($this->plugin_name . '-uptime-monitoring', 'wpspeedtestpro_uptime', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
        ));

        wp_localize_script($this->plugin_name . '-uptime-monitoring', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce'),
        ));
    }

    public function display_uptime_monitoring() {
        if (!$this->uptimerobot_check_api_key()) {
            echo '<div class="notice notice-error"><p>Please add your UptimeRobot API Key <a href="' . esc_html(admin_url('admin.php?page=wpspeedtestpro-settings')) . '">settings page</a> before running tests.</p></div>';
            return;
        }

        include_once('partials/wpspeedtestpro-uptime-monitoring-display.php');
    }

    public function dismiss_uptime_info() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        update_option('wpspeedtestpro_uptime_info_dismissed', true);
        wp_send_json_success();
    }
    
    private function uptimerobot_check_api_key() {
        return !empty($this->api_key);
    }

    public function uptimerobot_setup_monitors() {
        $this_site_url = wp_parse_url(get_site_url(), PHP_URL_HOST);

        $ping_filename = $this->uptimerobot_create_ping_file();
        if (!$ping_filename) {
            return array('success' => false, 'message' => 'Failed to create ping file.');
        }

        $ping_monitor = $this->uptimerobot_create_monitor(site_url('/'.$ping_filename), 'WPSpeedTestPro Ping Monitor - '.$this_site_url);
        if (!$ping_monitor['success']) {
            return $ping_monitor; // Return the error message from create_monitor
        }

        $cron_monitor = $this->uptimerobot_create_monitor(site_url('/wp-cron.php'), 'WPSpeedTestPro Cron Monitor ' . $this_site_url); 
        if (!$cron_monitor['success']) {
            return $cron_monitor; // Return the error message from create_monitor
        }

        update_option('wpspeedtestpro_uptimerobot_ping_id', $ping_monitor['data']['id']);
        update_option('wpspeedtestpro_uptimerobot_cron_id', $cron_monitor['data']['id']);
        return array('success' => true, 'message' => 'Monitors set up successfully.');
    }

    private function uptimerobot_create_ping_file() {
        $wordpress_base_dir = ABSPATH;
        $random_string = $this->uptimerobot_generate_random_string(6);
        $filename = 'wpspeedtestpro_ping_' . $random_string . '.php';
        $filepath = $wordpress_base_dir . '/' . $filename;

        $content = '<?php echo "pong"; ?>';

        if (file_put_contents($filepath, $content) === false) {
            return false;
        }

        update_option('wpspeedtestpro_ping_filename', $filename);
        return $filename;
    }

    private function uptimerobot_generate_random_string($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    private function uptimerobot_create_monitor($url, $friendly_name) {
        $api_url = 'https://api.uptimerobot.com/v2/newMonitor';
        $body = array(
            'api_key' => $this->api_key,
            'format' => 'json',
            'type' => 1,
            'url' => $url,
            'friendly_name' => $friendly_name
        );

    
        $response = wp_remote_post($api_url, array(
            'body' => $body,
            'timeout' => 30
        ));
    
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'WordPress error: ' . $response->get_error_message());
        }
    
        $response_body = wp_remote_retrieve_body($response);

    
        $data = json_decode($response_body, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('success' => false, 'message' => 'JSON decode error: ' . json_last_error_msg());
        }
    
        if ($data['stat'] === 'ok') {
            return array('success' => true, 'data' => $data['monitor']);
        } else {
            if (isset($data['error'])) {
                if ($data['error']['type'] === 'already_exists') {
                    return array('success' => false, 'message' => 'Monitor already exists. Please delete it manually from UptimeRobot before creating a new one.');
                }
            }
            return array('success' => false, 'message' => 'Failed to create monitor: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
    }

    public function uptimerobot_get_monitor_data() {
        $api_url = 'https://api.uptimerobot.com/v2/getMonitors';
        $body = array(
            'api_key' => $this->api_key,
            'format' => 'json',
            'monitors' => $this->ping_monitor_id . '-' . $this->cron_monitor_id,
            'logs' => 1,
            'response_times' => 1,
            'response_times_average' => 1
        );
    
    
        $response = wp_remote_post($api_url, array(
            'body' => $body,
            'timeout' => 30
        ));
    
        if (is_wp_error($response)) {
            return false;
        }
    
        $response_body = wp_remote_retrieve_body($response);
    
        $data = json_decode($response_body, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
    
        if (isset($data['stat']) && $data['stat'] === 'ok' && isset($data['monitors']) && is_array($data['monitors'])) {
            $monitors = $data['monitors'];
            foreach ($monitors as &$monitor) {
                // Ensure response_times is always an array
                if (!isset($monitor['response_times']) || !is_array($monitor['response_times'])) {
                    $monitor['response_times'] = [];
                }
            }
            return $monitors;
        } else {
            return false;
        }
    }

    public function uptimerobot_delete_monitors() {
        $ping_deleted = $this->uptimerobot_delete_monitor($this->ping_monitor_id);
        $cron_deleted = $this->uptimerobot_delete_monitor($this->cron_monitor_id);

        if ($ping_deleted && $cron_deleted) {
            delete_option('wpspeedtestpro_uptimerobot_ping_id');
            delete_option('wpspeedtestpro_uptimerobot_cron_id');
            return true;
        }

        return false;
    }

    private function uptimerobot_delete_monitor($monitor_id) {
        $api_url = 'https://api.uptimerobot.com/v2/deleteMonitor';
        $body = array(
            'api_key' => $this->api_key,
            'format' => 'json',
            'id' => $monitor_id
        );

        $response = wp_remote_post($api_url, array(
            'body' => $body,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return ($data['stat'] === 'ok');
    }

    // AJAX Handlers
    public function uptimerobot_get_monitor_data_handler() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $monitor_data = $this->uptimerobot_get_monitor_data();

        if ($monitor_data) {
            wp_send_json_success($monitor_data);
        } else {
            wp_send_json_error('Failed to retrieve monitor data.');
        }
    }

    public function uptimerobot_setup_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $setup_result = $this->uptimerobot_setup_monitors();

        if ($setup_result['success']) {
            wp_send_json_success($setup_result['message']);
        } else {
            wp_send_json_error($setup_result['message']);
        }
    }

    public function uptimerobot_delete_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $delete_result = $this->uptimerobot_delete_monitors();

        if ($delete_result) {
            wp_send_json_success('Monitors deleted successfully.');
        } else {
            wp_send_json_error('Failed to delete monitors.');
        }
    }

    public function uptimerobot_recreate_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        /* $delete_result = $this->uptimerobot_delete_monitors();
        if (!$delete_result) {
            wp_send_json_error('Failed to delete existing monitors.');
            return;
        }
        */

        $setup_result = $this->uptimerobot_setup_monitors();
        if ($setup_result) {
            wp_send_json_success('Monitors recreated successfully.');
        } else {
            wp_send_json_error('Failed to recreate monitors.');
        }
    }
}