<?php

/**
 * The server information functionality of the plugin.
 * 
 * Based on the work from Server Info - By Usman Ali Qureshi
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
        add_action('wp_ajax_wpspeedtestpro_dismiss_serverinfo_info', array($this, 'dismiss_serverinfo_info'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    private function is_this_the_right_plugin_page() {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speedtest-pro_page_wpspeedtestpro-server-information';    
        }
    }

    public function enqueue_styles() {
        if (!$this->is_this_the_right_plugin_page()) {
      //      return;
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

        wp_localize_script($this->plugin_name . '-server-information', 'wpspeedtestpro_serverinfo', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));

        wp_localize_script($this->plugin_name . '-server-information', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));


    }

    public function display_server_information() {
        include_once('partials/wpspeedtestpro-server-information-display.php');
    }

    public function dismiss_serverinfo_info() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        update_option('wpspeedtestpro_serverinfo_info_dismissed', true);
        wp_send_json_success();
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
            'php_version' => function_exists('phpversion')  ? phpversion() : 'N/A' ,
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
        $client_version = mysqli_get_client_info() ? mysqli_get_client_info() : 'N/A';

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
        $active_theme = wp_get_theme() ? wp_get_theme() : 'N/A';
        $plugins = get_plugins() ? get_plugins() : array();
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
            'memory_limit' => WP_MEMORY_LIMIT ? WP_MEMORY_LIMIT : 'N/A',
            'max_memory_limit' => WP_MAX_MEMORY_LIMIT ? WP_MAX_MEMORY_LIMIT : 'N/A',
            'debug_mode' => WP_DEBUG ? 'Enabled' : 'Disabled'
        );

        return $info;
    }
}



class Wpspeedtestpro_Php_Info {
    
    private function is_phpinfo_available() {
        ob_start();
        $exists = function_exists('phpinfo');
        if ($exists) {
            try {
                phpinfo();
                $output = ob_get_contents();
                ob_end_clean();
                return !empty($output);
            } catch (Exception $e) {
                ob_end_clean();
                return false;
            }
        }
        ob_end_clean();
        return false;
    }

    public function get_php_info() {
        if ($this->is_phpinfo_available()) {
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            
            // Convert phpinfo HTML to be WordPress-friendly
            $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
            $phpinfo = str_replace('<table>', '<table class="wp-list-table widefat fixed striped">', $phpinfo);
            return $phpinfo;
        } else {
            return $this->get_soft_php_info();
        }
    }

    private function get_soft_php_info() {
        $info = array();
        
        // Basic PHP Information
        $info[] = $this->create_section_row('PHP Version', phpversion());
        $info[] = $this->create_section_row('System', php_uname());
        $info[] = $this->create_section_row('Server API', php_sapi_name());
        $info[] = $this->create_section_row('Configuration File', php_ini_loaded_file());
        $info[] = $this->create_section_row('Zend Version', zend_version());
        
        // Memory Information
        $info[] = $this->create_section_row('Memory Limit', ini_get('memory_limit'));
        $info[] = $this->create_section_row('Max Execution Time', ini_get('max_execution_time'));
        $info[] = $this->create_section_row('Upload Max Filesize', ini_get('upload_max_filesize'));
        $info[] = $this->create_section_row('Post Max Size', ini_get('post_max_size'));
        
        // Extensions Information
        $extensions = get_loaded_extensions();
        sort($extensions);
        $info[] = $this->create_section_row('Loaded Extensions', implode(', ', $extensions));
        
        // Error Reporting
        $error_reporting = ini_get('error_reporting');
        $error_levels = array();
        if ($error_reporting & E_ERROR) $error_levels[] = 'E_ERROR';
        if ($error_reporting & E_WARNING) $error_levels[] = 'E_WARNING';
        if ($error_reporting & E_PARSE) $error_levels[] = 'E_PARSE';
        if ($error_reporting & E_NOTICE) $error_levels[] = 'E_NOTICE';
        $info[] = $this->create_section_row('Error Reporting', implode(', ', $error_levels));
        
        // Session Information
        $info[] = $this->create_section_row('Session Save Handler', ini_get('session.save_handler'));
        $info[] = $this->create_section_row('Session Save Path', ini_get('session.save_path'));
        
        // Output Buffering
        $info[] = $this->create_section_row('Output Buffering', ini_get('output_buffering'));
        
        // Create the table
        $table = '<table class="wp-list-table widefat fixed striped">';
        $table .= '<thead><tr><th>Directive</th><th>Value</th></tr></thead><tbody>';
        foreach ($info as $row) {
            $table .= $row;
        }
        $table .= '</tbody></table>';
        
        return $table;
    }
    
    private function create_section_row($name, $value) {
        return sprintf(
            '<tr><td>%s</td><td>%s</td></tr>',
            esc_html($name),
            esc_html($value)
        );
    }
}