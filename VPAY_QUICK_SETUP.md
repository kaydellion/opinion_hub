# ⚡ Quick Setup: vPay Africa Integration

## 🎯 What Was Done
✅ Paystack has been **DISABLED**  
✅ vPay Africa is now the payment gateway  
✅ All payment pages updated  
✅ New callback handler created  

---

## 🚀 IMMEDIATE ACTION REQUIRED

### Step 1: Get vPay Africa Credentials
1. Go to https://vpay.africa
2. Sign up and complete verification
3. Navigate to **Settings** > **API Keys**
4. Copy these 3 items:
   - **Public Key** (starts with `vpay_pub_`)
   - **Secret Key** (starts with `vpay_sec_`)
   - **Merchant ID**

### Step 2: Update Configuration
Open `/Applications/XAMPP/xamppfiles/htdocs/opinion/connect.php`

Find these lines (around line 25):
```php
define('VPAY_PUBLIC_KEY', 'vpay_pub_YOUR_PUBLIC_KEY_HERE');
define('VPAY_SECRET_KEY', 'vpay_sec_YOUR_SECRET_KEY_HERE');
define('VPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID_HERE');
```

Replace with your actual credentials:
```php
define('VPAY_PUBLIC_KEY', 'vpay_pub_1234567890abcdef');
define('VPAY_SECRET_KEY', 'vpay_sec_0987654321fedcba');
define('VPAY_MERCHANT_ID', 'MERCH123');
```

### Step 3: Configure Webhook (Optional but Recommended)
In your vPay Africa dashboard:
1. Go to **Settings** > **Webhooks**
2. Add webhook URL: `https://yourdomain.com/vpay-callback.php`
3. Select all payment events

---

## 📝 Files Updated

### Payment Pages (All now use vPay)
- ✅ `client/buy-credits.php` - Client credit purchases
- ✅ `client/subscription.php` - Subscription payments
- ✅ `agent/buy-sms-credits.php` - Agent SMS credits
- ✅ `client/pay-for-ad.php` - Advertisement payments

### Core Files
- ✅ `connect.php` - Payment configuration
- ✅ `functions.php` - Added vPay verification
- ✅ `vpay-callback.php` - **NEW** callback handler
- ✅ `faq.php` - Updated payment info
- ✅ `README.md` - Updated documentation

### Legacy Files (Deprecated)
- ⚠️ `payment-callback.php` - Now redirects to vpay-callback.php

---

## 🧪 Testing

### Test in This Order:
1. **Client Credits**
   - Login as client
   - Go to Buy Credits
   - Try buying poll credits
   - Verify payment popup opens
   - Complete test payment
   - Check credits added

2. **Agent SMS Credits**
   - Login as agent
   - Go to Buy SMS Credits
   - Try any package
   - Complete test payment
   - Check credits added

3. **Subscription**
   - Login as client
   - Go to Subscription page
   - Try monthly plan
   - Complete test payment
   - Check subscription activated

4. **Advertisement**
   - Create unpaid ad
   - Go to payment page
   - Complete test payment
   - Check ad status changed

---

## 🔍 Debugging

### Check Logs
If payment doesn't work:
```bash
# View vPay debug log
tail -f /Applications/XAMPP/xamppfiles/htdocs/opinion/vpay_debug.log

# View PHP error log
tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log
```

### Common Issues

**Problem:** Payment popup doesn't open  
**Solution:** Check browser console, verify public key is set

**Problem:** Payment completes but credits not added  
**Solution:** Check `vpay_debug.log`, verify secret key is correct

**Problem:** "Invalid credentials" error  
**Solution:** Double-check all 3 credentials in connect.php

---

## 📚 Full Documentation

For detailed information, see:
- `VPAY_INTEGRATION_GUIDE.md` - Complete integration guide
- `VPAY_IMPLEMENTATION_SUMMARY.md` - Full change summary

---

## ✅ Checklist

Before going live:
- [ ] vPay account created and verified
- [ ] API credentials updated in connect.php
- [ ] Webhook configured in vPay dashboard
- [ ] Test payment completed successfully
- [ ] Credits/subscription working correctly
- [ ] HTTPS enabled on production server
- [ ] Debug logs reviewed

---

## 🆘 Support

**vPay Africa:**
- Website: https://vpay.africa
- Email: support@vpay.africa
- Dashboard: https://dashboard.vpay.africa

**OpinionHub:**
- Email: hello@opinionhub.ng
- Phone: +234 (0) 803 3782 777

---

## 🎉 That's It!

Once you add your vPay credentials, all payment features will work automatically.

**Next:** Test with a small amount to verify everything works!
