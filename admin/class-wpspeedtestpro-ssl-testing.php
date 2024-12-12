<?php

/**
 * The SSL testing functionality of the plugin.
 *
 * @link       https://wpspeedtestpro.com
 * @since      1.0.0
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 */

/**
 * The SSL testing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the SSL testing functionality.
 *
 * @package    Wpspeedtestpro
 * @subpackage Wpspeedtestpro/admin
 * @author     WP Speedtest Pro Team <support@wpspeedtestpro.com>
 */
class Wpspeedtestpro_SSL_Testing {

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

    private $ssl_cached_results = 'wpspeedtestpro_ssl_results';
    private $in_progress_key = 'wpspeedtestpro_ssl_test_in_progress';

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
    }

    private function init_components() {
        add_action('wp_ajax_start_ssl_test', array($this, 'start_ssl_test'));
        add_action('wp_ajax_check_ssl_test_status', array($this, 'check_ssl_test_status'));
        add_action('wp_ajax_wpspeedtestpro_dismiss_ssl_info', array($this, 'dismiss_ssl_info'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

    }

    private function is_this_the_right_plugin_page() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return $screen && $screen->id === 'wp-speed-test-pro_page_wpspeedtestpro-ssl-testing';    
        }
    }

    /**
     * Register the stylesheets for the SSL testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_style($this->plugin_name . '-ssl-testing', plugin_dir_url(__FILE__) . 'css/wpspeedtestpro-ssl-testing.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the SSL testing area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if (!$this->is_this_the_right_plugin_page()) {
            return;
        }
        wp_enqueue_script($this->plugin_name . '-ssl-testing', plugin_dir_url(__FILE__) . 'js/wpspeedtestpro-ssl-testing.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name . '-ssl-testing', 'wpspeedtestpro_ssl', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));
        wp_localize_script($this->plugin_name . '-ssl-testing', 'wpspeedtestpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpspeedtestpro_ajax_nonce')
        ));
        
    }

    /**
     * Render the SSL testing page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_ssl_testing() {
        $user_email = get_option('wpspeedtestpro_user_ssl_email');
        if (empty($user_email)) {
            echo '<div class="notice notice-error"><p>No SSL Labs API key available - deactivate this plugin and reactivate it - If this continues, please contact the plugin support</p></div>';
            return;
        }
        $cached_result = get_transient($this->ssl_cached_results);
        include_once('partials/wpspeedtestpro-ssl-testing-display.php');
    }

    public function dismiss_ssl_info() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');
        update_option('wpspeedtestpro_ssl_info_dismissed', true);
        wp_send_json_success();
    }

    public function start_ssl_test() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        delete_transient($this->ssl_cached_results);

        $registered_user_email = get_option('wpspeedtestpro_user_ssl_email');
        $email = isset($registered_user_email) ? $registered_user_email : 'default@example.com';

        $result = $this->core->api->test_ssl_certificate(home_url(), $email);


        if (is_array($result) && isset($result['status']) && $result['status'] !== 'READY') {
            set_transient($this->in_progress_key, $result, 360 * MINUTE_IN_SECONDS);
            wp_send_json_success(array('status' => 'in_progress', 'message' => 'SSL test initiated. Please wait.'));
        } elseif (is_array($result) && !isset($result['error'])) {
            $this->cache_ssl_results($result);
            wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($result)));
        } else {
            wp_send_json_error('Failed to start SSL test: ' . $result['error']);
        }
    }

    public function check_ssl_test_status() {
        check_ajax_referer('wpspeedtestpro_ajax_nonce', 'nonce');

        $registered_user_email = get_option('wpspeedtestpro_user_ssl_email');
        $email = isset($registered_user_email) ? $registered_user_email : 'default@example.com';

        $result = $this->core->api->test_ssl_certificate(home_url(), $email,"done");
        $in_progress_result = get_transient($this->in_progress_key);

        if (false === $in_progress_result) {
            $cached_result = get_transient($this->ssl_cached_results);
            if (false !== $cached_result) {
                wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($cached_result)));
            } else {
                wp_send_json_error('No SSL test in progress or cached results available.');
            }
            return;
        }

        if (is_array($result) && isset($result['status']) && $result['status'] === 'READY') {
            delete_transient($this->in_progress_key);
            $this->cache_ssl_results($result);
            wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($result)));
        } elseif (is_array($result) && isset($result['status'])) {
            wp_send_json_success(array('status' => 'in_progress', 'message' => 'SSL test still in progress. Testing can take upto 3 minutes ' . $result['status']));
        } else {
            wp_send_json_error('Failed to check SSL test status.');
        }
    }

    private function cache_ssl_results($result) {
        set_transient($this->ssl_cached_results, $result, HOUR_IN_SECONDS);
    }

    public function get_cached_results() {
        return get_transient($this->ssl_cached_results);
    }

    public function format_ssl_test_results($result) {
        $output = '<div class="ssl-test-results" id="ssl-test-results-' . uniqid() . '">';

        // Overall Rating (always visible)
        $grade = $result['endpoints'][0]['grade'];
        $grade_color = ($grade === 'A' || $grade === 'A+') ? 'green' : (($grade === 'B') ? 'orange' : 'red');
        $output .= '<h1><i class="fas fa-award" style="color: ' . $grade_color . ';"></i> Overall SSL Certificate Rating: <span style="color: ' . $grade_color . ';">' . $grade . '</span></h1>';

        // Start tabs
        $output .= '<div class="ssl-tabs">';
        $output .= '<ul class="ssl-tab-links">';
        $output .= '<li class="active"><a href="#tab-root-stores">Root Stores</a></li>';
        $output .= '<li><a href="#tab-cert">Certificate</a></li>';
        $output .= '<li><a href="#tab-protocols">Protocols</a></li>';
        $output .= '<li><a href="#tab-ciphers">Cipher Suites</a></li>';
        $output .= '<li><a href="#tab-handshake">Handshake Simulation</a></li>';
        $output .= '<li><a href="#tab-http">HTTP Request</a></li>';
        $output .= '<li><a href="#tab-vulnerabilities">Vulnerabilities</a></li>';
        $output .= '<li><a href="#tab-raw">Raw Data</a></li>';
        $output .= '</ul>';

        $output .= '<div class="ssl-tab-content">';
        // Root Stores and Certificate Details
        $output .= '<div id="tab-root-stores" class="ssl-tab active">';
        $output .= $this->format_root_stores_and_cert_details($result);
        $output .= '</div>';

        // Certificate Information
        $output .= '<div id="tab-cert" class="ssl-tab">';
        $output .= $this->format_certificate_info($result['certs'][0]);
        $output .= '</div>';

        // Protocols
        $output .= '<div id="tab-protocols" class="ssl-tab">';
        $output .= $this->format_protocols($result['endpoints'][0]['details']['protocols']);
        $output .= '</div>';

        // Cipher Suites
        $output .= '<div id="tab-ciphers" class="ssl-tab">';
        $output .= $this->format_cipher_suites($result['endpoints'][0]['details']['suites']);
        $output .= '</div>';

        // Handshake Simulation
        $output .= '<div id="tab-handshake" class="ssl-tab">';
        $output .= $this->format_ssl_simulations($result['endpoints'][0]['details']['sims']);
        $output .= '</div>';

        // HTTP Request Information
        $output .= '<div id="tab-http" class="ssl-tab">';
        $output .= $this->format_http_request_info($result['endpoints'][0]['details']['httpTransactions']);
        $output .= '</div>';

        // Vulnerabilities
        $output .= '<div id="tab-vulnerabilities" class="ssl-tab">';
        $output .= $this->format_vulnerabilities($result['endpoints'][0]['details']);
        $output .= '</div>';

        // Raw Data
        $output .= '<div id="tab-raw" class="ssl-tab">';
        $output .= '<pre>' . esc_html(print_r($result, true)) . '</pre>';
        $output .= '</div>';

        $output .= '</div>'; // End tab content
        $output .= '</div>'; // End tabs

        $output .= '</div>'; // End ssl-test-results

        return $output;
    }

    private function format_certificate_info($cert) {
        $output = '<h3><i class="fas fa-certificate"></i> Certificate Information</h3>';
        $output .= '<table class="wp-list-table widefat fixed striped">';
        $output .= '<tbody>';
    
        $add_row = function($label, $value) use (&$output) {
            $output .= '<tr>';
            $output .= '<th scope="row">' . esc_html($label) . '</th>';
            $output .= '<td>' . $value . '</td>';
            $output .= '</tr>';
        };
    
        $add_row('Subject', esc_html($cert['subject']));
        $add_row('Fingerprint SHA256', esc_html($cert['sha256Hash']));
        $add_row('Pin SHA256', esc_html($cert['pinSha256']));
        $add_row('Common names', esc_html(implode(', ', $cert['commonNames'])));
        $add_row('Alternative names', esc_html(implode(', ', $cert['altNames'])));
        $add_row('Serial Number', esc_html($cert['serialNumber']));
        $add_row('Valid from', date('D, d M Y H:i:s T', $cert['notBefore'] / 1000));
        $add_row('Valid until', date('D, d M Y H:i:s T', $cert['notAfter'] / 1000) . ' (expires in ' . $this->format_expiry_time($cert['notAfter']) . ')');
        $add_row('Key', esc_html($cert['keyAlg']) . ' ' . $cert['keySize'] . ' bits (e ' . $cert['keyStrength'] . ')');
        $add_row('Weak key (Debian)', $cert['weakDebianKey'] ? 'Yes' : 'No');
        $add_row('Issuer', esc_html($cert['issuerSubject']));
        $add_row('Signature algorithm', esc_html($cert['sigAlg']));
        $add_row('Extended Validation', $cert['validationType'] === 'EV' ? 'Yes' : 'No');
        $add_row('Certificate Transparency', $cert['sct'] ? 'Yes' : 'No');
        $add_row('OCSP Must Staple', $cert['mustStaple'] ? 'Yes' : 'No');
    
        // Revocation information
        $revocation_info = 'Not available';
        if (isset($cert['revocationInfo'])) {
            if (is_array($cert['revocationInfo'])) {
                $revocation_info = esc_html(implode(', ', $cert['revocationInfo']));
            } else {
                $revocation_info = esc_html($cert['revocationInfo']);
            }
        }
        $add_row('Revocation information', $revocation_info);
    
        // Revocation status
        $revocation_status = isset($cert['revocationStatus']) ? esc_html($cert['revocationStatus']) : 'Not available';
        $add_row('Revocation status', $revocation_status);
    
        $output .= '</tbody>';
        $output .= '</table>';
    
        return $output;
    }

    private function format_protocols($protocols) {
        $output = '<h3><i class="fas fa-exchange-alt"></i> Supported Protocols</h3>';
        $output .= '<ul>';
        foreach ($protocols as $protocol) {
            $output .= '<li><i class="fas fa-check-circle"></i> ' . esc_html($protocol['name'] . ' ' . $protocol['version']) . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    private function format_cipher_suites($suites) {
        $output = '<h3><i class="fas fa-lock"></i> Cipher Suites</h3>';
            foreach ($suites as $protocol_suite) {
                $protocol = isset($protocol_suite['protocol']) ? 'TLS ' . number_format($protocol_suite['protocol'] / 256, 1) : 'Unknown Protocol';
                $output .= '<h4>' . esc_html($protocol) . '</h4>';
                $output .= '<ul>';
                
                if (isset($protocol_suite['list']) && is_array($protocol_suite['list'])) {
                    foreach ($protocol_suite['list'] as $suite) {
                        $icon = (isset($suite['q']) && $suite['q'] == 1) ? '<i class="fas fa-times-circle" style="color: red;"></i>' : '<i class="fas fa-check-circle" style="color: green;"></i>';
                        $strength = isset($suite['cipherStrength']) ? ' (' . $suite['cipherStrength'] . '-bit)' : '';
                        $output .= '<li>' . $icon . ' ' . esc_html($suite['name']) . esc_html($strength) . '</li>';
                    }
                }
                
                $output .= '</ul>';
            }
        return $output;
    }

    private function format_vulnerabilities($details) {
        $output = '<h3><i class="fas fa-bug"></i> Vulnerabilities</h3>';
        $output .= '<ul>';
        $vulnerabilities = [
            'heartbleed' => 'Heartbleed',
            'poodle' => 'POODLE',
            'freak' => 'FREAK',
            'logjam' => 'Logjam'
        ];
        foreach ($vulnerabilities as $key => $name) {
            $vulnerable = $details[$key];
            $icon = $vulnerable ? '<i class="fas fa-exclamation-triangle" style="color: red;"></i>' : '<i class="fas fa-shield-alt" style="color: green;"></i>';
            $status = $vulnerable ? 'Vulnerable' : 'Not Vulnerable';
            $output .= '<li>' . $icon . ' ' . esc_html($name) . ': ' . $status . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    private function format_ssl_simulations($sims) {
        $output = '<h3><i class="fas fa-laptop"></i> Client Simulations</h3>';
        $output .= '<table class="ssl-simulations-table">';
        $output .= '<thead><tr><th>Client</th><th>Version</th><th>Result</th></tr></thead>';
        $output .= '<tbody>';
    
        foreach ($sims['results'] as $sim) {
            $client = $sim['client'];
            $errorClass = ($sim['errorCode'] !== 0) ? ' class="error"' : '';
            
            $output .= '<tr' . $errorClass . '>';
            $output .= '<td>' . esc_html($client['name']) . '</td>';
            $output .= '<td>' . esc_html($client['version']) . '</td>';
            
            if ($sim['errorCode'] === 0) {
                $output .= '<td><i class="fas fa-check-circle" style="color: green;"></i> ' . esc_html($sim['suiteName']) . '</td>';
            } else {
                $output .= '<td><i class="fas fa-exclamation-triangle" style="color: red;"></i> ' . esc_html($sim['errorMessage']) . '</td>';
            }
            
            $output .= '</tr>';
        }
    
        $output .= '</tbody></table>';
    
        return $output;
    }

    private function format_http_request_info($httpTransactions) {
        $output = '<h3><i class="fas fa-exchange-alt"></i> HTTP Request Information</h3>';
        
        foreach ($httpTransactions as $index => $transaction) {
            $output .= '<div class="http-transaction">';
            $output .= '<h4>Transaction #' . ($index + 1) . '</h4>';
            
            // Request Details
            $output .= '<h5><i class="fas fa-arrow-right"></i> Request</h5>';
            $output .= '<table class="http-info-table">';
            $output .= '<tr><th>URL</th><td>' . esc_html($transaction['requestUrl']) . '</td></tr>';
            $output .= '<tr><th>Method</th><td>' . esc_html(explode(' ', $transaction['requestLine'])[0]) . '</td></tr>';
            $output .= '</table>';
            
            // Request Headers
            $output .= '<h6>Request Headers:</h6>';
            $output .= '<table class="http-info-table">';
            foreach ($transaction['requestHeaders'] as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) == 2) {
                    $output .= '<tr><th>' . esc_html(trim($parts[0])) . '</th><td>' . esc_html(trim($parts[1])) . '</td></tr>';
                } else {
                    $output .= '<tr><td colspan="2">' . esc_html($header) . '</td></tr>';
                }
            }
            $output .= '</table>';
            
            // Response Details
            $output .= '<h5><i class="fas fa-arrow-left"></i> Response</h5>';
            $output .= '<table class="http-info-table">';
            $output .= '<tr><th>Status</th><td>' . esc_html($transaction['statusCode'] . ' ' . explode(' ', $transaction['responseLine'], 3)[2]) . '</td></tr>';
            $output .= '</table>';
            
            // Response Headers
            $output .= '<h6>Response Headers:</h6>';
            $output .= '<table class="http-info-table">';
            foreach ($transaction['responseHeaders'] as $header) {
                $output .= '<tr><th>' . esc_html($header['name']) . '</th><td>' . esc_html($header['value']) . '</td></tr>';
            }
            $output .= '</table>';
            
            $output .= '</div>';
        }
        
        return $output;
    }

    private function format_root_stores_and_cert_details($result) {
        $output = '<h3><i class="fas fa-shield-alt"></i> Trusted Root Stores</h3>';
        $output .= '<ul>';
    
        $trustPaths = $result['endpoints'][0]['details']['certChains'][0]['trustPaths'];
        $rootStores = ['Mozilla', 'Apple', 'Android', 'Java', 'Windows'];
    
        foreach ($rootStores as $store) {
            $trusted = false;
            foreach ($trustPaths as $path) {
                foreach ($path['trust'] as $trust) {
                    if ($trust['rootStore'] === $store && $trust['isTrusted']) {
                        $trusted = true;
                        break 2;
                    }
                }
            }
            $icon = $trusted ? '<i class="fas fa-check-circle" style="color: green;"></i>' : '<i class="fas fa-times-circle" style="color: red;"></i>';
            $output .= '<li>' . $icon . ' ' . esc_html($store) . '</li>';
        }
    
        $output .= '</ul>';
    
        return $output;
    }
    
    private function format_expiry_time($timestamp) {
        $now = time();
        $expiry = $timestamp / 1000; // Convert milliseconds to seconds
        $diff = $expiry - $now;
    
        $days = floor($diff / (60 * 60 * 24));
        $months = floor($days / 30);
        $days %= 30;
    
        if ($months > 0) {
            return "$months month" . ($months > 1 ? "s" : "") . " and $days day" . ($days > 1 ? "s" : "");
        } else {
            return "$days day" . ($days > 1 ? "s" : "");
        }
    }

  
}