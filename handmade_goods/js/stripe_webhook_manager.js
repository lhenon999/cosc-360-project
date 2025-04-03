/**
 * Stripe Webhook Manager
 * Automatically checks if the webhook service is running and starts it if needed
 */
(function() {
    // Only run this on pages that might need Stripe payments
    const isPaymentPage = document.querySelector('.checkout-form') || 
                           window.location.href.includes('/checkout') || 
                           window.location.href.includes('/cart');
    
    if (!isPaymentPage) {
        return; // Don't run on non-payment pages
    }
    
    // Check and start the webhook service
    function checkWebhookStatus() {
        fetch('/cosc-360-project/handmade_goods/payments/stripe_cli_manager.php?action=status')
            .then(response => response.json())
            .then(data => {
                console.log('Webhook status:', data);
                
                // If webhook is not running, start it
                if (!data.status.is_running) {
                    console.log('Webhook not running, starting...');
                    startWebhook();
                } else {
                    console.log('Webhook is already running');
                }
            })
            .catch(error => {
                console.error('Error checking webhook status:', error);
            });
    }
    
    // Start the webhook service
    function startWebhook() {
        fetch('/cosc-360-project/handmade_goods/payments/stripe_cli_manager.php?action=start')
            .then(response => response.json())
            .then(data => {
                console.log('Webhook start result:', data);
                
                // If failed to start, show a message to the administrator
                if (!data.started && isAdmin()) {
                    showAdminNotification('Failed to start Stripe webhook service. Please check the server logs.');
                }
            })
            .catch(error => {
                console.error('Error starting webhook:', error);
                if (isAdmin()) {
                    showAdminNotification('Error starting Stripe webhook service: ' + error.message);
                }
            });
    }
    
    // Helper function to check if current user is an admin
    function isAdmin() {
        // This should be customized based on your authentication system
        return document.body.classList.contains('admin-user') || 
               document.querySelector('.admin-controls') !== null;
    }
    
    // Show notification to admin users
    function showAdminNotification(message) {
        const notificationArea = document.querySelector('.admin-notification') || 
                                document.querySelector('.notification-area');
        
        if (notificationArea) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-warning';
            notification.innerHTML = message;
            notificationArea.appendChild(notification);
            
            // Remove after 10 seconds
            setTimeout(() => {
                notification.remove();
            }, 10000);
        } else {
            console.warn('Admin notification:', message);
        }
    }
    
    // Run the check when the page loads
    checkWebhookStatus();
    
    // Also add a check in case the page is kept open for a long time
    setInterval(checkWebhookStatus, 15 * 60 * 1000); // Check every 15 minutes
})(); 