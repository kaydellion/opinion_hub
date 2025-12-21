# vPay Africa Payment Integration Guide

## Overview
OpinionHub.ng now uses **vPay Africa** as the payment gateway. Paystack has been disabled.

---

## Configuration

### 1. Get vPay Africa API Credentials

1. Sign up at https://vpay.africa
2. Complete your business verification
3. Navigate to Settings > API Keys
4. Copy your:
   - Public Key (starts with `vpay_pub_`)
   - Secret Key (starts with `vpay_sec_`)
   - Merchant ID

### 2. Update Configuration File

Edit `/Applications/XAMPP/xamppfiles/htdocs/opinion/connect.php`:

```php
// vPay Africa Configuration (Get from https://vpay.africa)
define('VPAY_PUBLIC_KEY', 'vpay_pub_YOUR_PUBLIC_KEY_HERE');
define('VPAY_SECRET_KEY', 'vpay_sec_YOUR_SECRET_KEY_HERE');
define('VPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID_HERE');
define('VPAY_CALLBACK_URL', SITE_URL . 'vpay-callback.php');
```

Replace:
- `vpay_pub_YOUR_PUBLIC_KEY_HERE` with your actual public key
- `vpay_sec_YOUR_SECRET_KEY_HERE` with your actual secret key
- `YOUR_MERCHANT_ID_HERE` with your merchant ID

---

## Files Updated

### Payment Pages (Updated to use vPay)
1. **client/buy-credits.php** - Client credit purchases
2. **client/subscription.php** - Subscription payments
3. **agent/buy-sms-credits.php** - Agent SMS credit purchases
4. **client/pay-for-ad.php** - Advertisement payments
5. **client/ad-payment-callback.php** - Ad payment verification

### Core Files
1. **connect.php** - Payment gateway configuration
2. **functions.php** - Added `verifyVPayPayment()` and `getUserById()`
3. **vpay-callback.php** - New callback handler for vPay payments

---

## Payment Flow

### User Journey
1. User clicks "Buy Now" or "Subscribe" button
2. vPay Africa popup opens with payment form
3. User completes payment using:
   - Credit/Debit Card
   - Bank Transfer
   - USSD
4. Payment is verified with vPay API
5. Credits/subscription activated automatically
6. User redirected to success page

### Technical Flow
```
User Action → JavaScript (VPay SDK) → vPay Payment Page
     ↓
Payment Success → Callback URL
     ↓
vpay-callback.php → verifyVPayPayment()
     ↓
Database Update → Credits Added → Notification Sent
```

---

## Integration Details

### JavaScript Implementation
All payment pages now use the vPay Africa SDK:

```html
<script src="https://checkout.vpay.africa/v1/vpay.js"></script>
```

### Payment Initialization
```javascript
const vpay = new VPay({
    key: 'YOUR_PUBLIC_KEY',
    email: 'user@example.com',
    amount: 5000, // Amount in Naira (NOT kobo)
    currency: 'NGN',
    reference: 'UNIQUE_REFERENCE',
    firstname: 'John',
    lastname: 'Doe',
    phone: '08012345678',
    merchant_id: 'YOUR_MERCHANT_ID',
    callback_url: 'https://yourdomain.com/vpay-callback.php',
    metadata: {
        custom_field: 'value'
    },
    onSuccess: function(response) {
        // Payment successful
        window.location.href = callback_url;
    },
    onError: function(error) {
        // Payment failed
        alert('Payment failed');
    },
    onClose: function() {
        // Popup closed
    }
});

vpay.open();
```

### Payment Verification
The `verifyVPayPayment()` function in `functions.php` handles verification:

```php
$result = verifyVPayPayment($reference);

if ($result['status']) {
    // Payment successful
    $amount = $result['data']['amount'];
    $reference = $result['data']['reference'];
    // Process payment...
} else {
    // Payment failed
    $error = $result['message'];
}
```

---

## Callback Handler (vpay-callback.php)

Handles all payment confirmations and processes:

### Credit Purchases
- **poll_credits** - Client poll credits
- **sms_credits** - Client SMS credits
- **agent_sms_credits** - Agent SMS credits

### Subscriptions
- Monthly plans
- Annual plans
- Updates user subscription status and expiry

### Advertisements
- Updates payment status
- Sets ad to "pending" for admin approval

### URL Parameters
```
?reference=REF123&type=poll_credits&units=100
?reference=REF456&type=subscription&plan=monthly
?reference=REF789&type=advertisement&ad_id=5
```

---

## Database Changes

No database migrations needed. The existing `transactions` table supports vPay:

```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    type VARCHAR(50),
    amount DECIMAL(10,2),
    reference VARCHAR(255),
    status VARCHAR(20),
    payment_method VARCHAR(20), -- 'vpay' or 'paystack'
    metadata TEXT,
    created_at TIMESTAMP
);
```

---

## Testing

### Test Mode
1. Use vPay test credentials
2. Test cards provided by vPay Africa
3. Check logs in `vpay_debug.log`

### Test Scenarios
- ✅ Buy poll credits
- ✅ Buy SMS credits
- ✅ Subscribe to monthly plan
- ✅ Subscribe to annual plan
- ✅ Pay for advertisement
- ✅ Cancel payment (popup close)
- ✅ Failed payment
- ✅ Duplicate payment (idempotency)

---

## Key Differences from Paystack

| Feature | Paystack | vPay Africa |
|---------|----------|-------------|
| Amount Format | Kobo (x100) | Naira (direct) |
| SDK URL | js.paystack.co | checkout.vpay.africa |
| Verification | /transaction/verify | /v1/verify |
| Popup Class | PaystackPop | VPay |
| Reference Format | Any string | Any string |

### Amount Conversion
**Before (Paystack):**
```javascript
amount: 5000 * 100 // ₦5,000 = 500,000 kobo
```

**Now (vPay Africa):**
```javascript
amount: 5000 // ₦5,000 directly
```

---

## Security

### API Key Protection
- ✅ Secret key never exposed to frontend
- ✅ Public key safe for JavaScript
- ✅ Webhook signature verification
- ✅ HTTPS required for production

### Payment Verification
All payments are verified server-side before crediting:
1. User completes payment on vPay
2. Callback receives reference
3. Server verifies with vPay API
4. Only then credits are added

---

## Troubleshooting

### Payment Not Processing
1. Check vPay credentials in `connect.php`
2. Verify callback URL is accessible
3. Check `vpay_debug.log` for errors
4. Ensure HTTPS is configured

### Popup Not Opening
1. Verify public key is correct
2. Check browser console for errors
3. Ensure vPay SDK script is loaded
4. Check for JavaScript conflicts

### Verification Failing
1. Verify secret key is correct
2. Check network connectivity to vPay API
3. Review error logs
4. Contact vPay support

---

## Production Checklist

- [ ] Sign up for vPay Africa production account
- [ ] Complete business verification
- [ ] Get live API credentials
- [ ] Update `VPAY_PUBLIC_KEY` in connect.php
- [ ] Update `VPAY_SECRET_KEY` in connect.php
- [ ] Update `VPAY_MERCHANT_ID` in connect.php
- [ ] Test with small live transaction
- [ ] Configure webhook URL in vPay dashboard
- [ ] Enable HTTPS on your domain
- [ ] Monitor `vpay_debug.log` for issues
- [ ] Delete or secure debug logs

---

## Support

### vPay Africa Support
- **Email:** support@vpay.africa
- **Website:** https://vpay.africa
- **Documentation:** https://docs.vpay.africa
- **Dashboard:** https://dashboard.vpay.africa

### OpinionHub Implementation
All payment pages have been updated:
- Client credit purchases ✅
- Agent SMS credits ✅
- Subscriptions ✅
- Advertisement payments ✅

---

## Migration Notes

### Paystack Status
Paystack integration has been **DISABLED** but not removed:
- Code commented out in `connect.php`
- Functions still exist but marked as disabled
- Can be re-enabled if needed

### Existing Transactions
Historical Paystack transactions remain in database with `payment_method = 'paystack'`.
New transactions will have `payment_method = 'vpay'`.

---

## Next Steps

1. **Immediate:** Update vPay credentials in `connect.php`
2. **Testing:** Test all payment flows in development
3. **Production:** Deploy to live site with live credentials
4. **Monitor:** Watch `vpay_debug.log` for first few days
5. **Optimize:** Remove debug logging after stable

---

## Questions?

Contact the development team or refer to vPay Africa documentation at https://docs.vpay.africa
