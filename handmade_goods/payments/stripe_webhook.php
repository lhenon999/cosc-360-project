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

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $stripe_webhook_secret
    );

    logWebhookEvent($event->type, $event->id);

    // Handle the event
    switch ($event->type) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            $orderId = $paymentIntent->metadata->order_id;
            
            // Update order status and payment details
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Processing', 
                    payment_id = ?,
                    payment_method = ?
                WHERE id = ?
            ");
            $paymentMethod = $paymentIntent->payment_method_types[0];
            $stmt->bind_param("ssi", $paymentIntent->id, $paymentMethod, $orderId);
            $stmt->execute();
            $stmt->close();
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            $orderId = $paymentIntent->metadata->order_id;
            
            // Update order status to failed with error message
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Payment Failed',
                    payment_id = ?,
                    payment_method = ?
                WHERE id = ?
            ");
            $paymentMethod = $paymentIntent->payment_method_types[0];
            $stmt->bind_param("ssi", $paymentIntent->id, $paymentMethod, $orderId);
            $stmt->execute();
            $stmt->close();
            break;

        case 'charge.refunded':
            $charge = $event->data->object;
            // Find order by payment intent ID
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Refunded'
                WHERE payment_id = ?
            ");
            $stmt->bind_param("s", $charge->payment_intent);
            $stmt->execute();
            $stmt->close();
            break;

        case 'payment_intent.processing':
            $paymentIntent = $event->data->object;
            $orderId = $paymentIntent->metadata->order_id;
            
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'Processing Payment'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();
            break;

        default:
            // Log unhandled event type
            logWebhookEvent($event->type, $event->id, 'unhandled');
            http_response_code(200);
            exit();
    }

    http_response_code(200);
} catch(\UnexpectedValueException $e) {
    logWebhookEvent('error', 'N/A', 'failed', 'Invalid payload: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    logWebhookEvent('error', 'N/A', 'failed', 'Invalid signature: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch (Exception $e) {
    logWebhookEvent('error', 'N/A', 'failed', $e->getMessage());
    http_response_code(500);
    exit();
}