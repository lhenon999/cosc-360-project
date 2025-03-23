<?php
// Stripe configuration
if (!defined('STRIPE_INCLUDED')) {
    define('STRIPE_INCLUDED', true);

    // Set your keys here
    $stripe_secret_key = 'sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH';
    $stripe_publishable_key = 'pk_test_51R1OROBSlWUNcExMybmLKuUOMFFhHJ7ZYoaNOHG6XvbnoqxRyQxkLJcf2hgNuIvgd3d03CPS5DvOStmYzOoP80c100G4jIbM8r';

    // Error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // First try to load from Composer's vendor directory
    $vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    }

    // Then try the direct project vendor directory
    $projectVendorPath = dirname(__DIR__) . '/vendor/stripe/stripe-php/init.php';
    if (file_exists($projectVendorPath)) {
        require_once $projectVendorPath;
    }

    // Check if Stripe is available
    if (class_exists('\Stripe\Stripe')) {
        // Use the Stripe PHP library
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        $stripe = new \Stripe\StripeClient($stripe_secret_key);
    } else {
        // Fallback minimal Stripe API implementation
        class StripeAPI {
            private $sk;
            private $base = 'https://api.stripe.com/v1/';
            
            public function __construct($sk) {
                $this->sk = $sk;
            }
            
            public function createCheckoutSession($params) {
                // Log the request
                error_log('Creating checkout session with params: ' . json_encode($params));
                
                // Make the API request
                $response = $this->request('checkout/sessions', 'POST', $params);
                
                if (isset($response['error'])) {
                    throw new \Exception($response['error']['message'] ?? 'Unknown Stripe error');
                }
                
                return $response;
            }
            
            private function request($endpoint, $method = 'POST', $data = []) {
                $ch = curl_init();
                
                curl_setopt($ch, CURLOPT_URL, $this->base . $endpoint);
                curl_setopt($ch, CURLOPT_USERPWD, $this->sk . ":");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                if ($method === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->flattenParams($data)));
                }
                
                $response = curl_exec($ch);
                $error = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($error) {
                    throw new \Exception('cURL Error: ' . $error);
                }
                
                $decoded = json_decode($response, true);
                if ($httpCode >= 400) {
                    throw new \Exception($decoded['error']['message'] ?? 'Stripe API Error: ' . $response);
                }
                
                return $decoded;
            }
            
            private function flattenParams($params, $prefix = '') {
                $result = [];
                
                foreach ($params as $key => $value) {
                    $newKey = $prefix ? "{$prefix}[{$key}]" : $key;
                    
                    if (is_array($value)) {
                        if ($this->isAssociativeArray($value)) {
                            $result = array_merge($result, $this->flattenParams($value, $newKey));
                        } else {
                            foreach ($value as $i => $item) {
                                if (is_array($item)) {
                                    $result = array_merge($result, $this->flattenParams($item, "{$newKey}[{$i}]"));
                                } else {
                                    $result["{$newKey}[{$i}]"] = $item;
                                }
                            }
                        }
                    } else {
                        $result[$newKey] = $value;
                    }
                }
                
                return $result;
            }
            
            private function isAssociativeArray($array) {
                if (!is_array($array) || empty($array)) {
                    return false;
                }
                return array_keys($array) !== range(0, count($array) - 1);
            }
        }
        
        $stripe = new StripeAPI($stripe_secret_key);
    }
}