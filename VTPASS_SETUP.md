# VTPass Integration - Setup & Testing Guide

## ✅ What's Been Done

### 1. Configuration System
- **VTPass settings now load from database** (not hardcoded)
- Settings are pulled via `getSetting()` function in `connect.php`
- All configuration constants defined: `VTPASS_API_URL`, `VTPASS_API_KEY`, `VTPASS_PUBLIC_KEY`, `VTPASS_SECRET_KEY`, `VTPASS_ENABLED`

### 2. Admin Settings Panel
- Added **"VTPass API (Airtime/Data)"** category to admin settings page
- Navigate to: `admin/settings.php` → scroll to VTPass API section
- 5 settings available:
  - `vtpass_enabled` - Enable/Disable integration (boolean)
  - `vtpass_api_url` - API endpoint (sandbox or live)
  - `vtpass_api_key` - Your API key
  - `vtpass_public_key` - Your public key
  - `vtpass_secret_key` - Your secret key

### 3. Integration Functions
Located in `functions.php`:
- `vtpass_send_airtime($phone, $network_id, $amount)` - Send airtime
- `vtpass_send_data($phone, $variation_code)` - Send data bundle
- `vtpass_curl_post($endpoint, $data)` - POST requests to VTPass
- `vtpass_curl_get($endpoint)` - GET requests to VTPass

### 4. Test Script
Created `test-vtpass.php` to verify:
- Configuration status (enabled/disabled)
- API credentials (loaded from database)
- API connectivity (test balance endpoint)
- Database settings view

---

## 🚀 How to Set Up VTPass

### Step 1: Get VTPass API Credentials
1. Sign up at https://www.vtpass.com
2. Login to dashboard
3. Go to **API Settings** or **Developer** section
4. Get your:
   - API Key
   - Public Key
   - Secret Key

### Step 2: Configure in Admin Panel
1. Login to your admin account
2. Navigate to: `http://localhost/opinion/admin/settings.php`
3. Scroll down to **"VTPass API (Airtime/Data)"** section
4. Fill in:
   ```
   Enabled: Yes (select from dropdown)
   API URL: https://sandbox.vtpass.com/api/ (for testing)
            https://vtpass.com/api/ (for production)
   API Key: [paste your API key]
   Public Key: [paste your public key]
   Secret Key: [paste your secret key]
   ```
5. Click **"Save All Changes"**

### Step 3: Test Configuration
1. Navigate to: `http://localhost/opinion/test-vtpass.php`
2. You should see:
   - ✓ VTPASS_ENABLED: ENABLED (green)
   - ✓ All credentials showing as "Set" (green)
   - ✓ API connectivity test results
   - ✓ Your VTPass account balance

---

## 🧪 How to Test VTPass Integration

### Test 1: Check Configuration Status
```
URL: http://localhost/opinion/test-vtpass.php
Expected: All green checkmarks, balance shown
```

### Test 2: Send Test Airtime
Create a PHP test file or use existing agent payout system:
```php
<?php
require_once 'connect.php';
require_once 'functions.php';

// Test sending ₦50 MTN airtime
$result = vtpass_send_airtime('2347019960275', 'mtn', 50);

if ($result['success']) {
    echo "✓ Airtime sent successfully!";
    print_r($result);
} else {
    echo "✗ Failed: " . $result['message'];
}
?>
```

### Test 3: Send Test Data
```php
<?php
require_once 'connect.php';
require_once 'functions.php';

// Test sending MTN 1GB data
$result = vtpass_send_data('2347019960275', 'mtn-1gb');

if ($result['success']) {
    echo "✓ Data bundle sent successfully!";
    print_r($result);
} else {
    echo "✗ Failed: " . $result['message'];
}
?>
```

---

## 📋 VTPass Network IDs

### Airtime Networks
- `mtn` - MTN Nigeria
- `glo` - Glo Nigeria
- `airtel` - Airtel Nigeria
- `etisalat` - 9mobile (formerly Etisalat)

### Data Variation Codes (Examples)
Get full list from VTPass API: https://www.vtpass.com/documentation/data-variation-codes

Common ones:
- MTN: `mtn-1gb`, `mtn-2gb`, `mtn-5gb`, `mtn-10gb`
- GLO: `glo-1gb`, `glo-2gb`, `glo-5gb`
- Airtel: `airtel-1gb`, `airtel-2gb`, `airtel-5gb`
- 9mobile: `etisalat-1gb`, `etisalat-2gb`

---

## ✅ Integration Checklist

- [x] VTPass configuration loads from database
- [x] Settings added to admin panel
- [x] VTPass category visible in admin/settings.php
- [x] Integration functions created (send_airtime, send_data)
- [x] Test script created (test-vtpass.php)
- [ ] **TODO**: Add VTPass credentials from dashboard
- [ ] **TODO**: Enable VTPass in admin settings
- [ ] **TODO**: Test with actual transaction
- [ ] **TODO**: Integrate with agent payout system
- [ ] **TODO**: Add transaction logging

---

## 🔧 Troubleshooting

### Issue: "VTPass is disabled"
**Solution**: Go to admin/settings.php, set `vtpass_enabled` to "Enabled", save

### Issue: "API credentials not configured"
**Solution**: Add your API keys from VTPass dashboard to admin/settings.php

### Issue: "Invalid API Key" error
**Solution**: 
1. Verify keys are correct (no extra spaces)
2. Check if using sandbox keys with sandbox URL
3. Check if using live keys with live URL

### Issue: "Insufficient Balance"
**Solution**: Fund your VTPass account at https://www.vtpass.com

### Issue: Test script shows "Admin access required"
**Solution**: Login as admin first, then visit test-vtpass.php

---

## 📝 Files Modified/Created

1. `connect.php` - Loads VTPass config from database
2. `functions.php` - VTPass integration functions
3. `admin/settings.php` - Added vtpass_api category
4. `test-vtpass.php` - Test script (NEW)
5. `vtpass_settings.sql` - Database settings (NEW)

---

## 🎯 Next Steps

1. **Get VTPass Account**: Sign up at https://www.vtpass.com
2. **Configure Keys**: Add to admin/settings.php
3. **Test**: Run test-vtpass.php
4. **Integrate**: Connect to agent payout system
5. **Go Live**: Switch from sandbox to live URL

---

## 📞 Support

- **VTPass Documentation**: https://www.vtpass.com/documentation
- **VTPass Support**: support@vtpass.com
- **VTPass Dashboard**: https://www.vtpass.com/dashboard

---

**Status**: ✅ Configuration Complete | ⏳ Awaiting API Credentials | 🧪 Ready for Testing
