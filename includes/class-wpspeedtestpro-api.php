<?php 

class Wpspeedtestpro_API {

    public function get_gcp_endpoints() {
        $response = wp_remote_get('https://global.gcping.com/api/endpoints');
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $endpoints = json_decode($body, true);
        
        if (!is_array($endpoints)) {
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

        return $formatted_endpoints;
    }

    public function ping_endpoint($url) {
        $start_time = microtime(true);
        $response = wp_remote_get($url . '/api/ping');
        $end_time = microtime(true);
        if (is_wp_error($response)) {
            return false;
        }
        $ping_time = round(($end_time - $start_time) * 1000, 1);
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
        $api_url = 'https://api.ssllabs.com/api/v4/analyze';
        $host = wp_parse_url($domain, PHP_URL_HOST);
        
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
    
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return array('error' => 'Failed to connect to SSL Labs API: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        
        $data = json_decode($body, true);
        
        if (!$data) {
            return array('error' => 'Failed to parse SSL Labs API response');
        }
        
        
        if (isset($data['errors']) && !empty($data['errors'])) {
            $error_messages = array();
            foreach ($data['errors'] as $index => $error) {
                if (is_array($error) && isset($error['message'])) {
                    $error_message = $error['message'];
                } elseif (is_string($error)) {
                    $error_message = $error;
                } else {
                    $error_message = "Unknown error format";
                }
                $error_messages[] = $error_message;
            }
            
            return array('error' => implode(', ', $error_messages));
        }
        
        if (isset($data['status'])) {
            if ($data['status'] === 'READY' && isset($data['endpoints'])) {
                return $data;
            } else {
                $message = isset($data['statusMessage']) ? $data['statusMessage'] : 'SSL Assessment in progress';
                return array(
                    'status' => $data['status'],
                    'message' => $message
                );
            }
        }
    
        return array('error' => 'Unexpected response from SSL Labs API');
    }

    public function get_hosting_providers() {
        $cache_key = 'wpspeedtestpro_hosting_providers';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingproviders.json');
        if (is_wp_error($response)) {
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (!isset($data['providers']) || !is_array($data['providers'])) {
            return false;
        }
    
    
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
    
    
        $debug_names = array_slice(array_column($unique_providers, 'name'), 0, 5);
    
        set_transient($cache_key, $unique_providers, WEEK_IN_SECONDS);
        return $unique_providers;
    }

    public function get_hosting_providers_json() {
        $cache_key = 'wpspeedtestpro_hosting_providers_json';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingproviders.json');
        if (is_wp_error($response)) {
            return false;
        }
    
        $providers_json = wp_remote_retrieve_body($response);
    
        set_transient($cache_key, $providers_json, WEEK_IN_SECONDS);
    
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
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}