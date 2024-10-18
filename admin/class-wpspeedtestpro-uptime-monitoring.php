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
        add_action('admin_enqueue_scripts', array($this, 'uptimerobot_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'uptimerobot_enqueue_scripts'));
    }

    public function uptimerobot_enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-uptime-monitoring', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-uptime-monitoring.css', array(), $this->version, 'all');
    }

    public function uptimerobot_enqueue_scripts() {
        wp_enqueue_script($this->plugin_name . '-uptime-monitoring', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-uptime-monitoring.js', array('jquery', 'chart-js'), $this->version, false);
        wp_localize_script($this->plugin_name . '-uptime-monitoring', 'wpspeedtestpro_uptime', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_uptime_nonce'),
        ));
    }

    public function uptimerobot_display_monitoring() {
        if (!$this->uptimerobot_check_api_key()) {
            wp_redirect(admin_url('admin.php?page=wpspeedtestpro-settings'));
            exit;
        }

        include_once('partials/wpspeedtestpro-uptime-monitoring-display.php');
    }

    private function uptimerobot_check_api_key() {
        return !empty($this->api_key);
    }

    public function uptimerobot_setup_monitors() {
        $ping_filename = $this->uptimerobot_create_ping_file();
        if (!$ping_filename) {
            return false;
        }

        $ping_monitor = $this->uptimerobot_create_monitor($ping_filename, 'WP Speed Test Pro Ping Monitor');
        $cron_monitor = $this->uptimerobot_create_monitor(site_url('/wp-cron.php'), 'WP Speed Test Pro Cron Monitor');

        if ($ping_monitor && $cron_monitor) {
            update_option('wpspeedtestpro_uptimerobot_ping_id', $ping_monitor['id']);
            update_option('wpspeedtestpro_uptimerobot_cron_id', $cron_monitor['id']);
            return true;
        }

        return false;
    }

    private function uptimerobot_create_ping_file() {
        $upload_dir = wp_upload_dir();
        $random_string = $this->uptimerobot_generate_random_string(6);
        $filename = 'wpspeedtestpro_ping_' . $random_string . '.php';
        $filepath = $upload_dir['basedir'] . '/' . $filename;

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
            $string .= $characters[rand(0, strlen($characters) - 1)];
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
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['stat'] === 'ok') {
            return $data['monitor'];
        }

        return false;
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

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($data['stat'] === 'ok') {
            return $data['monitors'];
        }

        return false;
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
        check_ajax_referer('wpspeedtestpro_uptime_nonce', 'nonce');

        $monitor_data = $this->uptimerobot_get_monitor_data();

        if ($monitor_data) {
            wp_send_json_success($monitor_data);
        } else {
            wp_send_json_error('Failed to retrieve monitor data.');
        }
    }

    public function uptimerobot_setup_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_uptime_nonce', 'nonce');

        $setup_result = $this->uptimerobot_setup_monitors();

        if ($setup_result) {
            wp_send_json_success('Monitors set up successfully.');
        } else {
            wp_send_json_error('Failed to set up monitors.');
        }
    }

    public function uptimerobot_delete_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_uptime_nonce', 'nonce');

        $delete_result = $this->uptimerobot_delete_monitors();

        if ($delete_result) {
            wp_send_json_success('Monitors deleted successfully.');
        } else {
            wp_send_json_error('Failed to delete monitors.');
        }
    }

    public function uptimerobot_recreate_monitors_handler() {
        check_ajax_referer('wpspeedtestpro_uptime_nonce', 'nonce');

        $delete_result = $this->uptimerobot_delete_monitors();
        if (!$delete_result) {
            wp_send_json_error('Failed to delete existing monitors.');
            return;
        }

        $setup_result = $this->uptimerobot_setup_monitors();
        if ($setup_result) {
            wp_send_json_success('Monitors recreated successfully.');
        } else {
            wp_send_json_error('Failed to recreate monitors.');
        }
    }
}