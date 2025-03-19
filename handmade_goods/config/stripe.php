<?php
// Stripe configuration - Using hosted library instead of local dependency
if (!defined('STRIPE_INCLUDED')) {
    define('STRIPE_INCLUDED', true);

    // Set your keys here
    $stripe_secret_key = 'sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH';
    $stripe_publishable_key = 'pk_test_51R1OROBSlWUNcExMybmLKuUOMFFhHJ7ZYoaNOHG6XvbnoqxRyQxkLJcf2hgNuIvgd3d03CPS5DvOStmYzOoP80c100G4jIbM8r';
    
    // Fallback minimal Stripe API implementation if local library is not available
    if (!class_exists('\Stripe\Stripe')) {
        class StripeAPI {
            private $sk;
            private $base = 'https://api.stripe.com/v1/';
            
            public function __construct($sk) {
                $this->sk = $sk;
            }
            
            public function request($endpoint, $method = 'POST', $data = []) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->base . $endpoint);
                curl_setopt($ch, CURLOPT_USERPWD, $this->sk . ":");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                if ($method === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                
                $response = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new Exception('cURL Error: ' . $error);
                }
                
                return json_decode($response, true);
            }
            
            public function createCheckoutSession($params) {
                return $this->request('checkout/sessions', 'POST', $params);
            }
            
            public function createPaymentIntent($params) {
                return $this->request('payment_intents', 'POST', $params);
            }
        }
        
        $stripe = new StripeAPI($stripe_secret_key);
    } else {
        \Stripe\Stripe::setApiKey($stripe_secret_key);
    }
}