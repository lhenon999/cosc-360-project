# Payment Testing Guide

## Test Cards
You can use these test card numbers to simulate different scenarios:

### Basic Test Cards
- **Test Card (Visa)**: 4242 4242 4242 4242
- **Test Card (Mastercard)**: 5555 5555 5555 4444
- **Test Card (American Express)**: 3782 822463 10005
- **Test Card (Discover)**: 6011 1111 1111 1117
- **Test Card (Diners Club)**: 3056 9309 0259 04
- **Test 3D Secure Card**: 4000 0027 6000 3184

For all test cards:
- Expiration Date: Any future date (e.g., 12/25)
- CVC: Any 3 digits (e.g., 123) or 4 digits for American Express
- ZIP: Any 5 digits (e.g., 12345)

### Special Test Scenarios
- **Successful Payment**: 4242 4242 4242 4242
- **Requires Authentication**: 4000 0025 0000 3155
- **Declined Payment**: 4000 0000 0000 9995
- **Insufficient Funds**: 4000 0000 0000 9995
- **Lost Card**: 4000 0000 0000 7002
- **Expired Card**: 4000 0000 0000 0069
- **Processing Error**: 4000 0000 0000 0119

### Google Pay Test Setup
1. Use Chrome browser on Windows/Android
2. Make sure you have Google Pay set up in your Chrome browser
3. Add a test card to your Google Pay:
   - Open Chrome Settings
   - Go to Payment Methods
   - Add one of the test card numbers above

### Test Mode Indicators
- In test mode, the payment form will show a test mode badge
- Test payments will be marked with "TEST" in your Stripe dashboard
- No real charges will be made

## Important Notes
1. Always use test API keys (starting with 'pk_test_' and 'sk_test_')
2. Test transactions will appear in your Stripe dashboard with a "Test" label
3. You can view test transactions in the Stripe dashboard under "Payments"

## Testing Tips
- All test card numbers must be typed with spaces every 4 digits (as shown above)
- For successful payments, you can use any future expiration date
- CVV can be any 3 digits (4 digits for American Express)
- For international cards, any postal code will work in test mode