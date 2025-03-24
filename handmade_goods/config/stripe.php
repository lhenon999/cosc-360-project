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
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                }
                
                $response = curl_exec($ch);
                $err = curl_error($ch);
                
                curl_close($ch);
                
                if ($err) {
                    throw new \Exception('cURL Error: ' . $err);
                }
                
                return json_decode($response, true);
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