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

    // Load from root project's vendor directory
    $vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    } else {
        // Log error if vendor autoload is not found
        error_log("Error: Stripe vendor autoload.php not found at: " . $vendorPath);
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
                
                // Ensure the response has a checkout_url property (for consistency with our PHP code)
                if (isset($response['url']) && !isset($response['checkout_url'])) {
                    $response['checkout_url'] = $response['url'];
                }
                
                // Convert response to a standard object format for consistency with the Stripe SDK
                $responseObj = json_decode(json_encode($response));
                
                // Add checkout_url property if missing (but url exists)
                if (isset($responseObj->url) && !isset($responseObj->checkout_url)) {
                    $responseObj->checkout_url = $responseObj->url;
                }
                
                return $responseObj;
            }
            
            private function request($endpoint, $method = 'GET', $params = []) {
                $ch = curl_init($this->base . $endpoint);
                
                $headers = [
                    'Authorization: Bearer ' . $this->sk,
                    'Content-Type: application/x-www-form-urlencoded',
                ];
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                if ($method === 'POST' && !empty($params)) {
                    // Flatten the params array for proper format expected by Stripe
                    $flatParams = $this->flattenParams($params);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($flatParams));
                }
                
                $response = curl_exec($ch);
                $err = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                curl_close($ch);
                
                if ($err) {
                    throw new \Exception('cURL Error: ' . $err);
                }
                
                $decoded = json_decode($response, true);
                
                if ($httpCode >= 400) {
                    throw new \Exception($decoded['error']['message'] ?? 'Stripe API Error: ' . $response);
                }
                
                return $decoded;
            }
            
            // Helper function to flatten nested arrays for Stripe's API format
            private function flattenParams($params, $prefix = '') {
                $result = [];
                
                foreach ($params as $key => $value) {
                    $newKey = $prefix ? "{$prefix}[{$key}]" : $key;
                    
                    if (is_array($value)) {
                        if (isset($value[0]) || empty($value)) {
                            // Indexed array needs special handling
                            foreach ($value as $i => $item) {
                                if (is_array($item)) {
                                    $result = array_merge($result, $this->flattenParams($item, "{$newKey}[{$i}]"));
                                } else {
                                    $result["{$newKey}[{$i}]"] = $item;
                                }
                            }
                        } else {
                            // Associative array
                            $result = array_merge($result, $this->flattenParams($value, $newKey));
                        }
                    } else {
                        $result[$newKey] = $value;
                    }
                }
                
                return $result;
            }
        }
        
        $stripe = new StripeAPI($stripe_secret_key);
    }

    // Function to ensure Stripe JS is properly loaded
    function ensure_stripe_js() {
        global $stripe_publishable_key;
        
        $stripe_js_tag = '<script src="https://js.stripe.com/v3/"></script>';
        $stripe_init_tag = "<script>
            // Initialize Stripe with fallback error handling
            try {
                const stripe = Stripe('$stripe_publishable_key');
                window.stripeInstance = stripe;
            } catch (e) {
                console.error('Error initializing Stripe:', e);
                // Try to reload the Stripe library if initialization failed
                let script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.onload = function() {
                    try {
                        const stripe = Stripe('$stripe_publishable_key');
                        window.stripeInstance = stripe;
                        console.log('Stripe reloaded successfully');
                    } catch (e) {
                        console.error('Failed to initialize Stripe after reload:', e);
                    }
                };
                document.head.appendChild(script);
            }
        </script>";
        
        return $stripe_js_tag . "\n" . $stripe_init_tag;
    }
}