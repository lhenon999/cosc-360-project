<?php
// Simple endpoint to verify webhook server is accessible
header('Content-Type: application/json');

file_put_contents('../logs/webhook_ping.log', date('Y-m-d H:i:s') . " - Webhook ping received\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'Webhook endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s')
]);