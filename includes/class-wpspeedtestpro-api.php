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
        return round(($end_time - $start_time) * 1000, 1);
    }

    public function test_ssl_certificate($domain, $email) {
        $api_url = 'https://api.ssllabs.com/api/v4/analyze';
        $host = parse_url($domain, PHP_URL_HOST);
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'email' => $email
            ),
            'body' => array(
                'host' => $host,
                'fromCache' => 'on',
                'ignoreMismatch' => 'on',
                'all' => 'on',
                'maxAge' => '1'
            )
        );
    
        error_log('Starting SSL Labs API request for host: ' . $host);
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WP Error: ' . $error_message);
            return array('error' => 'Failed to connect to SSL Labs API: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        error_log('API Response Body: ' . substr($body, 0, 500) . '...');
        
        $data = json_decode($body, true);
        
        if (!$data) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array('error' => 'Failed to parse SSL Labs API response');
        }
        
        error_log('Decoded Data: ' . print_r($data, true));
        
        if (isset($data['errors']) && !empty($data['errors'])) {
            error_log('SSL Labs reported errors:');
            $error_messages = array();
            foreach ($data['errors'] as $index => $error) {
                if (is_array($error) && isset($error['message'])) {
                    $error_message = $error['message'];
                } elseif (is_string($error)) {
                    $error_message = $error;
                } else {
                    $error_message = "Unknown error format";
                }
                error_log("Error $index: $error_message");
                $error_messages[] = $error_message;
            }
            
            return array('error' => implode(', ', $error_messages));
        }
        
        if (isset($data['status'])) {
            error_log('Assessment Status: ' . $data['status']);
            if ($data['status'] === 'READY' && isset($data['endpoints'])) {
                error_log('Assessment Ready. Returning full data.');
                return $data;
            } else {
                $message = isset($data['statusMessage']) ? $data['statusMessage'] : 'SSL Assessment in progress';
                error_log('Assessment in progress: ' . $message);
                return array(
                    'status' => $data['status'],
                    'message' => $message
                );
            }
        }
    
        error_log('Unexpected response structure from SSL Labs API');
        return array('error' => 'Unexpected response from SSL Labs API');
    }

    public function test_ssl_certificate_orig($domain, $email) {
        $api_url = 'https://api.ssllabs.com/api/v4/analyze';
        $host = parse_url($domain, PHP_URL_HOST);
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'email' => $email
            ),
            'body' => array(
                'host' => $host,
                'fromCache' => 'on',
                'ignoreMismatch' => 'on',
                'all' => 'on',
                'maxAge' => '1'
            )
        );
    
        error_log('Starting SSL Labs API request');
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WP Error: ' . $error_message);
            return array('error' => 'Failed to connect to SSL Labs API: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        error_log('API Response Body: ' . substr($body, 0, 500) . '...');
        
        $data = json_decode($body, true);
        
        if (!$data) {
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return array('error' => 'Failed to parse SSL Labs API response');
        }
        
        error_log('Decoded Data: ' . print_r($data, true));
        
        if (isset($data['status'])) {
            error_log('Assessment Status: ' . $data['status']);
            if ($data['status'] === 'READY' && isset($data['endpoints'])) {
                error_log('Assessment Ready. Returning full data.');
                return $data;
            } else {
                $message = isset($data['statusMessage']) ? $data['statusMessage'] : 'SSL Assessment in progress';
                error_log('Assessment in progress: ' . $message);
                return array(
                    'status' => $data['status'],
                    'message' => $message
                );
            }
        }

        if (isset($data['errors']) && !empty($data['errors'])) {
            error_log('SSL Labs reported errors:');
            foreach ($data['errors'] as $index => $error) {
                if (is_array($error)) {
                    error_log("Error $index: " . print_r($error, true));
                } else {
                    error_log("Error $index: $error");
                }
            }
            
            $error_string = array_map(function($error) {
                return is_array($error) ? json_encode($error) : $error;
            }, $data['errors']);
        }

        return array('error' => 'Unexpected response from SSL Labs API');
    }

    public function get_hosting_providers() {
        $cache_key = 'wpspeedtestpro_hosting_providers';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            error_log('Returning cached data');
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingprovider.json');
        if (is_wp_error($response)) {
            error_log('Error fetching hosting providers: ' . $response->get_error_message());
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (!isset($data['providers']) || !is_array($data['providers'])) {
            error_log('Invalid data structure in hosting providers response');
            return false;
        }
    
        error_log('Number of providers before sorting: ' . count($data['providers']));
    
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
    
        error_log('Number of providers before duplicate removal: ' . count($data['providers']));
        error_log('Number of providers after duplicate removal: ' . count($unique_providers));
    
        $debug_names = array_slice(array_column($unique_providers, 'name'), 0, 5);
        error_log('First 5 provider names after sorting and duplicate removal: ' . implode(', ', $debug_names));
    
        set_transient($cache_key, $unique_providers, WEEK_IN_SECONDS);
    
        return $unique_providers;
    }

    public function get_hosting_providers_json() {
        $cache_key = 'wpspeedtestpro_hosting_providers_json';
        $cached_data = get_transient($cache_key);
    
        if ($cached_data !== false) {
            error_log('Returning cached data');
            return $cached_data;
        }
    
        $response = wp_remote_get('https://assets.wpspeedtestpro.com/wphostingprovider.json');
        if (is_wp_error($response)) {
            error_log('Error fetching hosting providers: ' . $response->get_error_message());
            return false;
        }
    
        $providers_json = wp_remote_retrieve_body($response);
    
        set_transient($cache_key, $providers_json, WEEK_IN_SECONDS);
    
        return $providers_json;
    }

    public function speedvitals_run_test($api_key, $url, $location, $device) {
        $api_url = 'https://api.speedvitals.com/v1/lighthouse-tests';
        
        $body = array(
            'url' => $url,
            'device' => $device,
            'location' => $location,
            'config' => array(
                'connection' => 'fiber',
                'video' => true,
                'adblock' => true
            )
        );

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'X-API-KEY' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['id'])) {
            return new WP_Error('api_error', 'Invalid response from SpeedVitals API');
        }

        return $data;
    }

    public function speedvitals_get_test_result($api_key, $test_id) {
        $api_url = "https://api.speedvitals.com/v1/lighthouse-tests/{$test_id}";

        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'X-API-KEY' => $api_key
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['id'])) {
            return new WP_Error('api_error', 'Invalid response from SpeedVitals API');
        }

        return $data;
    }

    public function speedvitals_get_account_credits($api_key) {
        $api_url = 'https://api.speedvitals.com/v1/account/credits';

        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'X-API-KEY' => $api_key
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (!isset($data['data']['lighthouse'])) {
            return new WP_Error('api_error', 'Invalid response from SpeedVitals Credits API');
        }

        return $data['data'];
    }
}