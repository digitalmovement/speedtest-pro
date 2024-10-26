<?php

/**
 * The server information functionality of the plugin.
 */
class Wpspeedtestpro_Server_Information {

    private $plugin_name;
    private $version;
    private $core;

    public function __construct($plugin_name, $version, $core) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->core = $core;
        $this->init_components();
    }

    private function init_components() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    private function is_this_the_right_plugin_page() {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speed-test-pro_page_wpspeedtestpro-server-information';    
        }
    }

    public function enqueue_styles() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style($this->plugin_name . '-server-information', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-server-information.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script($this->plugin_name . '-server-information', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-server-information.js', array('jquery', 'jquery-ui-tabs'), $this->version, false);
    }

    public function display_server_information() {
        include_once('partials/wpspeedtestpro-server-information-display.php');
    }

    public function get_server_info() {
        global $wpdb;
        $info = array();

        // Hosting Server Information
        $info['hosting'] = array(
            'operating_system' => function_exists('php_uname') ? php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m') : 'N/A',
            'server_hostname' => function_exists('php_uname') ? php_uname('n') : 'N/A',
            'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'N/A',
            'server_protocol' => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'N/A',
            'server_admin' => isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : '[no address given]',
            'server_port' => isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 'N/A',
            'web_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A',
            'php_version' => phpversion() . ' ' . ((PHP_INT_SIZE * 8 === 64) ? '(Supports 64bit values)' : '(Does not support 64bit values)'),
            'php_memory_limit' => ini_get('memory_limit'),
            'cgi_version' => isset($_SERVER['GATEWAY_INTERFACE']) ? $_SERVER['GATEWAY_INTERFACE'] : 'N/A'
        );

        // Database Information
        if (is_resource($wpdb->dbh)) {
            $extension = 'mysql';
        } elseif (is_object($wpdb->dbh)) {
            $extension = get_class($wpdb->dbh);
        } else {
            $extension = null;
        }

        $server_version = $wpdb->get_var('SELECT VERSION()');
        $client_version = mysqli_get_client_info();

        $info['database'] = array(
            'extension' => $extension,
            'server_version' => $server_version,
            'client_version' => $client_version,
            'database_user' => $wpdb->dbuser,
            'database_host' => $wpdb->dbhost,
            'database_name' => $wpdb->dbname,
            'table_prefix' => $wpdb->prefix,
            'database_charset' => $wpdb->charset,
            'database_collate' => $wpdb->collate
        );

        // WordPress Information
        $active_theme = wp_get_theme();
        $plugins = get_plugins();
        $active_plugins = array();
        $inactive_plugins = array();

        foreach ($plugins as $plugin_path => $plugin) {
            if (is_plugin_active($plugin_path)) {
                $active_plugins[$plugin['Name']] = $plugin['Author'];
            } else {
                $inactive_plugins[$plugin['Name']] = $plugin['Author'];
            }
        }

        $info['wordpress'] = array(
            'active_theme' => sprintf('%s (%s)', $active_theme->name, $active_theme->stylesheet),
            'active_plugins' => $active_plugins,
            'inactive_plugins' => $inactive_plugins,
            'memory_limit' => WP_MEMORY_LIMIT,
            'max_memory_limit' => WP_MAX_MEMORY_LIMIT,
            'debug_mode' => WP_DEBUG ? 'Enabled' : 'Disabled'
        );

        return $info;
    }
}