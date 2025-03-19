<?php
require_once '../config.php';
require_once '../config/stripe.php';

// Log webhook events
function logWebhookEvent($event_type, $event_id, $status = 'success', $error = null) {
    error_log(sprintf(
        "Stripe Webhook: [%s] Event: %s (ID: %s) %s",
        $status,
        $event_type,
        $event_id,
        $error ? "Error: " . $error : ""
    ));
}

// Verify webhook signature
function verifyWebhookSignature($payload, $sig_header, $secret) {
    $timestamp = explode(',', $sig_header)[0];
    $signature = explode(',', $sig_header)[1];
    
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac('sha256', $signed_payload, $secret);
    
    return hash_equals('t=' . $timestamp . ',v1=' . $expected_signature, $sig_header);
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhook_secret = 'whsec_your_webhook_secret'; // Replace with your webhook secret

try {
    if (!verifyWebhookSignature($payload, $sig_header, $webhook_secret)) {
        throw new Exception('Invalid signature');
    }

    $event = json_decode($payload, true);
    if ($event === null) {
        throw new Exception('Invalid payload');
    }

    logWebhookEvent($event['type'], $event['id']);

    // Handle the event
    switch ($event['type']) {
        case 'checkout.session.completed':
            $session = $event['data']['object'];
            $orderId = $session['metadata']['order_id'];
            
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Processing', 
                    payment_id = ?,
                    payment_method = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $session['payment_intent'], $session['payment_method_types'][0], $orderId);
            $stmt->execute();
            $stmt->close();
            break;

        case 'charge.refunded':
            $charge = $event['data']['object'];
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Refunded'
                WHERE payment_id = ?
            ");
            $stmt->bind_param("s", $charge['payment_intent']);
            $stmt->execute();
            $stmt->close();
            break;

        default:
            // Log unhandled event type
            logWebhookEvent($event['type'], $event['id'], 'unhandled');
            break;
    }

    http_response_code(200);
} catch (Exception $e) {
    logWebhookEvent('error', 'N/A', 'failed', $e->getMessage());
    http_response_code(400);
    error_log('Webhook Error: ' . $e->getMessage());
}