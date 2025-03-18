<?php
// Stripe configuration
$vendorDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'vendor';
$stripeInit = $vendorDir . DIRECTORY_SEPARATOR . 'stripe' . DIRECTORY_SEPARATOR . 'stripe-php' . DIRECTORY_SEPARATOR . 'init.php';

if (!file_exists($stripeInit)) {
    die('Stripe PHP library not found. Please run "composer install" in the project root directory.');
}

require_once $stripeInit;

// Set your keys here
$stripe_secret_key = 'sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH';
$stripe_publishable_key = 'pk_test_51R1OROBSlWUNcExMybmLKuUOMFFhHJ7ZYoaNOHG6XvbnoqxRyQxkLJcf2hgNuIvgd3d03CPS5DvOStmYzOoP80c100G4jIbM8r';

// Set your secret key
\Stripe\Stripe::setApiKey($stripe_secret_key);