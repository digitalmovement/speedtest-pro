<?php 

class Wpspeedtestpro_API {

    public function get_gcp_endpoints() {
        error_log('WPSpeedTestPro: Starting get_gcp_endpoints()');
        $response = wp_remote_get('https://global.gcping.com/api/endpoints');
        if (is_wp_error($response)) {
            error_log('WPSpeedTestPro: Error in get_gcp_endpoints() - ' . $response->get_error_message());
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $endpoints = json_decode($body, true);
        
        if (!is_array($endpoints)) {
            error_log('WPSpeedTestPro: Invalid response in get_gcp_endpoints() - ' . print_r($body, true));
            return false;
        }

        $formatted_endpoints = [];
        foreach ($endpoints as $region_code => $region_data) {
            $formatted_endpoints[] = [
                'region' => $region_data['Region'],
                'region_name' => $region_data['RegionName'],
                'url' => $region_data['URL']
            ];
        }

        error_log('WPSpeedTestPro: get_gcp_endpoints() completed successfully');
        return $formatted_endpoints;
    }

    public function ping_endpoint($url) {
        error_log('WPSpeedTestPro: Starting ping_endpoint() for URL: ' . $url);
        $start_time = microtime(true);
        $response = wp_remote_get($url . '/api/ping');
        $end_time = microtime(true);
        if (is_wp_error($response)) {
            error_log('WPSpeedTestPro: Error in ping_endpoint() - ' . $response->get_error_message());
            return false;
        }
        $ping_time = round(($end_time - $start_time) * 1000, 1);
        error_log('WPSpeedTestPro: ping_endpoint() completed. Ping time: ' . $ping_time . 'ms');
        return $ping_time;
    }

    public function register_ssl_user($first_name, $last_name, $email, $organization) {
        $api_url = 'https://api.ssllabs.com/api/v4/register';
        
        $body = json_encode([
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'organization' => $organization
        ]);

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $body,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 200) {
            return [
                'success' => true,
                'message' => 'User registered successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => isset($data['message']) ? $data['message'] : 'Unknown error occurred'
            ];
        }
    }


    public function test_ssl_certificate($domain, $email, $getStatus="on") {
        error_log('WPSpeedTestPro: Starting test_ssl_certificate() for domain: ' . $domain);
        $api_url = 'https://api.ssllabs.com/api/v4/analyze';
        $host = parse_url($domain, PHP_URL_HOST);
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'email' => $email
            ),
            'body' => array(
                'host' => $host,
                'fromCache' => 'off',
                'ignoreMismatch' => 'on',
                'all' => $getStatus,
                'maxAge' => '1'
            )
        );
    
        error_log('WPSpeedTestPro: Starting SSL Labs API request for host: ' . $host);
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WPSpeedTestPro: WP Error in test_ssl_certificate() - ' . $error_message);
            return array('error' => 'Failed to connect to SSL Labs API: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        error_log('WPSpeedTestPro: API Response Body: ' . substr($body, 0, 500) . '...');
        
        $data = json_decode($body, true);
        
        if (!$data) {
            error_log('WPSpeedTestPro: JSON Decode Error in test_ssl_certificate() - ' . json_last_error_msg());
            return array('error' => 'Failed to parse SSL Labs API response');
        }
        
        error_log('WPSpeedTestPro: Decoded Data: ' . print_r($data, true));
        
        if (isset($data['errors']) && !empty($data['errors'])) {
            error_log('WPSpeedTestPro: SSL Labs reported errors:');
            $error_messages = array();
            foreach ($data['errors'] as $index => $error) {
                if (is_array($error) && isset($error['message'])) {
                    $error_message = $error['message'];
                } elseif (is_string($error)) {
                    $error_message = $error;
                } else {
                    $error_message = "Unknown error format";
                }
                error_log("WPSpeedTestPro: Error $index: $error_message");
                $error_messages[] = $error_message;
            }
            
            return array('error' => implode(', ', $error_messages));
        }
        
        if (isset($data['status'])) {
            error_log('WPSpeedTestPro: Assessment Status: ' . $data['status']);
            if ($data['status'] === 'READY' && isset($data['endpoints'])) {
                error_log('WPSpeedTestPro: Assessment Ready. Returning full data.');
                return $data;
            } else {
                $message = isset($data['statusMessage']) ? $data['statusMessage'] : 'SSL Assessment in progress';
                error_log('WPSpeedTestPro: Assessment in progress: ' . $message);
                return array(
                    'status' => $data['status'],
                    'message' => $message
                );
            }
        }
    
        error_log('WPSpeedTestPro: Unexpected response structure from SSL Labs API');
        return array('error' => 'Unexpected response from SSL Labs API');
    }

    public function get_hosting_providers() {
        error_log('WPSpeedTestPro: Starting get_hosting_providers()');
        $cache_key = 'wpspeedtestpro_hosting_providers';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            error_log('WPSpeedTestPro: Returning cached hosting providers data');
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingproviders.json');
        if (is_wp_error($response)) {
            error_log('WPSpeedTestPro: Error fetching hosting providers - ' . $response->get_error_message());
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (!isset($data['providers']) || !is_array($data['providers'])) {
            error_log('WPSpeedTestPro: Invalid data structure in hosting providers response');
            return false;
        }
    
        error_log('WPSpeedTestPro: Number of providers before sorting: ' . count($data['providers']));
    
        $unique_providers = [];
        $seen_names = [];
        foreach ($data['providers'] as $provider) {
            if (!isset($seen_names[$provider['name']])) {
                $unique_providers[] = $provider;
                $seen_names[$provider['name']] = true;
            }
        }
    
        usort($unique_providers, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    
        error_log('WPSpeedTestPro: Number of providers after duplicate removal: ' . count($unique_providers));
    
        $debug_names = array_slice(array_column($unique_providers, 'name'), 0, 5);
        error_log('WPSpeedTestPro: First 5 provider names after sorting and duplicate removal: ' . implode(', ', $debug_names));
    
        set_transient($cache_key, $unique_providers, WEEK_IN_SECONDS);
    
        error_log('WPSpeedTestPro: get_hosting_providers() completed successfully');
        return $unique_providers;
    }

    public function get_hosting_providers_json() {
        error_log('WPSpeedTestPro: Starting get_hosting_providers_json()');
        $cache_key = 'wpspeedtestpro_hosting_providers_json';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            error_log('WPSpeedTestPro: Returning cached hosting providers JSON data');
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingproviders.json');
        if (is_wp_error($response)) {
            error_log('WPSpeedTestPro: Error fetching hosting providers JSON - ' . $response->get_error_message());
            return false;
        }
    
        $providers_json = wp_remote_retrieve_body($response);
    
        set_transient($cache_key, $providers_json, WEEK_IN_SECONDS);
    
        error_log('WPSpeedTestPro: get_hosting_providers_json() completed successfully');
        return $providers_json;
    }

    public function fetch_and_store_ssl_emails() {
        // URL of the JSON file
        $url = 'https://assets.wpspeedtestpro.com/ssl_emails.json';
    
        // Fetch the JSON file
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'sslverify' => true
        ));
    
        // Check for errors
        if (is_wp_error($response)) {
            error_log('WPSpeedTestPro: Error fetching SSL emails - ' . $response->get_error_message());
            return false;
        }
    
        // Get the response body
        $body = wp_remote_retrieve_body($response);
    
        // Try to decode the JSON
        $data = json_decode($body, true);
    
        // Check if JSON decode was successful and has the expected structure
        if (json_last_error() === JSON_ERROR_NONE && isset($data['ssl_emails']) && is_array($data['ssl_emails'])) {
            // Array to store sanitized emails
            $sanitized_emails = array();
    
            // Process each email
            foreach ($data['ssl_emails'] as $email) {
                $sanitized_email = sanitize_email($email);
                if ($sanitized_email) {
                    $sanitized_emails[] = $sanitized_email;
                }
            }
    
            // Store the emails in WordPress options if we have any valid ones
            if (!empty($sanitized_emails)) {
                // Store as a comma-separated string for easier use
                update_option('wpspeedtestpro_user_ssl_email', $sanitized_emails[array_rand($sanitized_emails)]);
                error_log('WPSpeedTestPro: Successfully stored ' . count($sanitized_emails) . ' SSL emails');
                return true;
            } else {
                error_log('WPSpeedTestPro: No valid emails found in JSON data');
                return false;
            }
        } else {
            error_log('WPSpeedTestPro: Error decoding JSON or missing ssl_emails array');
            return false;
        }
    }

}