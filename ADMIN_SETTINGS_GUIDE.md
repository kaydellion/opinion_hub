# Admin Configurable Settings Guide

## Overview
All global platform settings can now be edited by administrators through the **Admin > Settings** page (`/admin/settings.php`). No code changes required!

## Accessing Settings
1. Login as an admin user
2. Navigate to **Admin > Settings** (or go to `/admin/settings.php`)
3. Modify any settings
4. Click "Save All Changes"
5. Changes take effect immediately

## Settings Categories

### 1. Site Configuration ⭐ NEW
**Core website settings:**

- **Site Name** (Opinion Hub NG)
  - Website name shown in header, title, emails
  
- **Site Tagline** (What Gets Measured, Gets Done!)
  - Slogan/tagline for the platform
  
- **Site Email** (hello@opinionhub.ng)
  - Primary contact email
  
- **Site Phone** (+234 (0) 803 3782 777)
  - Primary contact phone
  
- **Site URL** (http://localhost/opinion/)
  - Base URL of website (include trailing slash)
  - Update when moving to production!
  
- **Site Favicon** (favicon.jpg)
  - Favicon filename (upload to root directory)
  - Supported: .jpg, .png, .ico
  - Fallback: Shows default if file not found
  
- **Site Logo** (logo.png)
  - Logo filename (upload to root directory)
  - If file exists: Shows image in navbar
  - If file not found: Shows icon + site name
  - Recommended size: 200x50px (transparent PNG)

### 2. Agent Earnings & Payments
**These control how agents are paid:**

- **Commission Per Poll** (₦1,000 default)
  - Amount agents earn per completed poll response
  - Can be changed to any amount (e.g., ₦500, ₦1500, ₦2000)

- **Min Payout** (₦5,000 default)
  - Minimum amount agents must accumulate before requesting payout
  - Prevents too many small transactions

- **Payment Processing Days** (5 days default)
  - Number of working days to process agent payouts
  - Displayed to agents when requesting payout
  - Can be changed to 3, 7, 10, etc.

### 3. Agent Approval
- **Approval Timeline Hours** (48 hours default)
  - Expected time for reviewing agent applications
  - Displayed to pending agents

- **Auto Approval** (false/disabled default)
  - Set to "true" to automatically approve all agent applications
  - Not recommended - manual review is safer

### 4. Poll Settings
- **Response Required Login** (true/enabled default)
  - Whether users must login before answering polls
  
- **Default Duration Days** (30 days default)
  - Default poll duration when creating new polls

### 5. Payment API (Paystack) 🔐
**API credentials for payment processing:**

- **Public Key** (pk_test_...)
  - Get from https://dashboard.paystack.com/#/settings/developer
  - Use test keys for testing, live keys for production
  
- **Secret Key** (sk_test_...)
  - Keep secure! Password-protected field
  
- **Callback URL** (payment-callback.php)
  - Relative URL for payment callbacks

### 6. Email API (Brevo) 🔐
**Credentials for email service:**

- **API Key** (YOUR_BREVO_API_KEY)
  - Get from https://www.brevo.com
  - Password-protected field
  
- **From Email** (noreply@opinionhub.ng)
  - Email address for sending
  
- **From Name** (Opinion Hub NG)
  - Name shown as sender

### 7. SMS API (Termii) 🔐
**Credentials for SMS service:**

- **API Key** (YOUR_TERMII_API_KEY)
  - Get from https://www.termii.com
  - Password-protected field
  
- **Sender ID** (OpinionHub)
  - SMS sender name (11 characters max)

### 8. WhatsApp API 🔐
**Credentials for WhatsApp Business:**

- **API URL** (YOUR_WHATSAPP_API_URL)
- **API Key** (YOUR_WHATSAPP_API_KEY)
  - Password-protected field

### 9. Email Settings
- **From Name** (Opinion Hub NG)
- **From Address** (hello@opinionhub.ng)
- **Email Enabled** (true/false)
  - Master switch for all email notifications

### 10. SMS Settings
- **SMS Enabled** (false/disabled default)
- **Sender ID** (OpinionHub)
  - Name shown as SMS sender

### 11. Company Information
**All company details in one place:**
- Company Name
- Address
- Phone Number
- Email Address

These are used in:
- Footer
- Contact pages
- Email signatures
- Legal documents

### 12. Advertisement Rates
**Per-view pricing for ads (in ₦):**
- Top Banner: ₦5
- Sidebar: ₦3
- Footer: ₦1.5
- In-Poll: ₦4

### 13. Pricing Plans
**Monthly subscription costs and limits:**

**Basic Plan:**
- Cost: ₦65,000
- Responses: 650,000

**Classic Plan:**
- Cost: ₦85,000
- Responses: 950,000

**Enterprise Plan:**
- Cost: ₦120,000
- Responses: 1,150,000

### 14. System Settings
- **Maintenance Mode** (false/disabled)
  - Enable to disable public access for updates
  
- **Registration Enabled** (true/enabled)
  - Allow/prevent new user signups
  
- **Agent Registration Enabled** (true/enabled)
  - Allow/prevent users from applying as agents

## Using Settings in Code

### For Developers
Settings can be accessed anywhere in the code using helper functions:

```php
// Get any setting
$value = getSetting('setting_key', $default_value);

// Quick access functions
$commission = getAgentCommission();  // Returns current commission amount
$minPayout = getMinPayout();         // Returns minimum payout amount
$processingDays = getPaymentProcessingDays(); // Returns processing days

// Get company info as array
$company = getCompanyInfo();
echo $company['name'];    // Foraminifera Market Research Limited
echo $company['address']; // Full address
echo $company['phone'];   // +234 (0) 803 3782 777
echo $company['email'];   // hello@opinionhub.ng
```

### Updating Settings Programmatically
```php
// Update a setting (admin only)
updateSetting('agent_commission_per_poll', 1500, $_SESSION['user_id']);
updateSetting('agent_min_payout', 10000, $_SESSION['user_id']);
```

## Common Use Cases

### Change Logo/Favicon
**Scenario:** You want to use your custom logo and favicon

1. Upload your logo file (e.g., `logo.png`) to the root directory
2. Upload your favicon file (e.g., `favicon.ico`) to the root directory
3. Go to Settings > Site Configuration
4. Update "Site Logo" to `logo.png`
5. Update "Site Favicon" to `favicon.ico`
6. Save changes

**Note:** If files don't exist, system automatically falls back to:
- Logo: Shows icon + site name
- Favicon: Browser default

### Update Site URL for Production
**Scenario:** Moving from localhost to live domain

1. Go to Settings > Site Configuration
2. Find "Site URL"
3. Change from `http://localhost/opinion/` to `https://yourdomain.com/`
4. Save changes

This updates all links, emails, payment callbacks automatically!

### Change Agent Commission
**Scenario:** You want to increase agent earnings from ₦1,000 to ₦1,500 per poll

1. Go to Settings > Agent Earnings & Payments
2. Find "Commission Per Poll"
3. Change from `1000` to `1500`
4. Save changes

All future earnings will be calculated at ₦1,500. Past earnings remain unchanged.

### Change Payout Processing Time
**Scenario:** You want to speed up payouts from 5 days to 3 days

1. Go to Settings > Agent Earnings & Payments
2. Find "Payment Processing Days"
3. Change from `5` to `3`
4. Save changes

Agents will now see "Payment within 3 working days" messages.

### Update Company Contact Info
**Scenario:** Phone number or address changed

1. Go to Settings > Company Information
2. Update the relevant fields
3. Save changes

Changes appear immediately in footer, contact page, emails, etc.

### Enable/Disable Features
**Scenario:** Temporarily disable new agent registrations

1. Go to Settings > System Settings
2. Find "Agent Registration Enabled"
3. Change from "Enabled" to "Disabled"
4. Save changes

The "Become an Agent" link will be hidden/disabled.

## Database Table
All settings are stored in the `site_settings` table:

```sql
site_settings (
    id,
    setting_key,      -- Unique identifier (e.g., 'agent_commission_per_poll')
    setting_value,    -- The actual value
    setting_type,     -- 'text', 'number', 'boolean', 'json'
    description,      -- What this setting does
    category,         -- Group (e.g., 'agent_earnings')
    updated_at,       -- Last modification time
    updated_by        -- Admin user ID who made the change
)
```

## Future Settings to Add

As the platform grows, you can easily add new settings:

1. Go to database
2. Insert new row in `site_settings` table
3. Setting will appear automatically in admin settings page

Example:
```sql
INSERT INTO site_settings (setting_key, setting_value, setting_type, description, category) 
VALUES ('referral_bonus', '500', 'number', 'Bonus amount for referring new agents (₦)', 'agent_earnings');
```

## Benefits of This System

✅ **No Code Changes Required** - Admins can adjust values without touching PHP files
✅ **Instant Updates** - Changes take effect immediately
✅ **Audit Trail** - Track who changed what and when
✅ **Type Safety** - Values are automatically cast to correct types (number, boolean, etc.)
✅ **Organized** - Settings grouped by category for easy navigation
✅ **Scalable** - Easy to add new settings as platform grows
✅ **Safe** - Only admins can access and modify settings

## Security Notes

- Only users with `role = 'admin'` can access `/admin/settings.php`
- All inputs are sanitized before database storage
- Changes are logged with timestamp and admin user ID
- Settings page requires authentication

## Support

If you need to add new settings or have questions:
- Contact: hello@opinionhub.ng
- Phone: +234 (0) 803 3782 777
