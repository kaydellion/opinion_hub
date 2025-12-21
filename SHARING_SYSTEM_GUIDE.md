# Poll Sharing System - Configuration Guide

## Overview
The Opinion Hub NG poll sharing system supports three methods:
1. **Email** - Free (via Brevo API - 300 emails/day free tier)
2. **SMS** - Paid (via Termii API - agents buy credits)
3. **WhatsApp** - Free/Manual (generates clickable wa.me links)

## API Configuration

### 1. Brevo Email API Setup

**Step 1: Create Brevo Account**
- Go to https://www.brevo.com (formerly Sendinblue)
- Sign up for a free account
- Verify your email address

**Step 2: Get API Key**
- Log in to Brevo dashboard
- Go to Settings → SMTP & API → API Keys
- Click "Generate a new API key"
- Give it a name (e.g., "Opinion Hub NG")
- Copy the API key (starts with "xkeysib-...")

**Step 3: Add to Database**
```sql
INSERT INTO site_settings (setting_key, setting_value, created_at) 
VALUES ('brevo_api_key', 'xkeysib-your-actual-api-key-here', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'xkeysib-your-actual-api-key-here';
```

**Free Tier Limits:**
- 300 emails per day
- Unlimited contacts
- Brevo branding in emails

### 2. Termii SMS API Setup

**Step 1: Create Termii Account**
- Go to https://termii.com
- Sign up for an account
- Complete KYC verification (required for sending SMS in Nigeria)

**Step 2: Get API Key**
- Log in to Termii dashboard
- Go to API Settings or Developer Settings
- Copy your API Key

**Step 3: Fund Your Termii Account**
- Go to Billing/Wallet
- Add funds to your Termii account (minimum ₦1,000 recommended)
- Note: This is separate from agent SMS credits

**Step 4: Add to Database**
```sql
INSERT INTO site_settings (setting_key, setting_value, created_at) 
VALUES ('termii_api_key', 'your-termii-api-key-here', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'your-termii-api-key-here';
```

**Pricing:**
- Approximately ₦2-4 per SMS (varies by route and volume)
- Agents pay for credits, not the actual Termii cost
- You can set your own markup in buy-sms-credits.php

### 3. WhatsApp Setup

**No API Required!**
- Uses WhatsApp Web API (wa.me links)
- Completely free
- Agent must have WhatsApp installed on their device
- Clicking the link opens WhatsApp with pre-filled message
- Agent manually sends each message

## SMS Credits System

### How It Works
1. Agents purchase SMS credit packages
2. Credits are stored in `agent_sms_credits` table
3. Each SMS sent deducts 1 credit
4. Transactions are tracked (purchase, used, refund)

### Credit Packages
Defined in `agent/buy-sms-credits.php`:

```php
$packages = [
    1 => ['credits' => 10,  'price' => 100],   // ₦10 per SMS
    2 => ['credits' => 50,  'price' => 450],   // ₦9 per SMS (10% off)
    3 => ['credits' => 100, 'price' => 800],   // ₦8 per SMS (20% off)
    4 => ['credits' => 200, 'price' => 1500],  // ₦7.50 per SMS (25% off)
    5 => ['credits' => 500, 'price' => 3500],  // ₦7 per SMS (30% off)
];
```

**Customizing Prices:**
- Edit the packages array in `agent/buy-sms-credits.php`
- Adjust prices based on your Termii costs + desired profit margin
- Example: If Termii charges ₦3/SMS, you could charge ₦5/credit for profit

### Payment Integration (TODO)
Currently in development mode (credits added directly). To integrate Paystack:

1. **Get Paystack API Keys**
   - Sign up at https://paystack.com
   - Get Test keys for development
   - Get Live keys for production

2. **Add to Database**
```sql
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('paystack_public_key', 'pk_test_xxx'),
       ('paystack_secret_key', 'sk_test_xxx');
```

3. **Modify buy-sms-credits.php**
   - Replace the TODO section with Paystack initialization
   - Verify payment callback
   - Add credits only after successful payment

## Testing the System

### Test Email Sharing
1. Configure Brevo API key (see above)
2. Go to agent/share-poll.php
3. Select a poll
4. Choose "Email" method
5. Enter test email addresses (comma-separated)
6. Check if email is received
7. Click tracking link to verify tracking code works

### Test SMS Sharing
1. Configure Termii API key (see above)
2. Give yourself SMS credits:
```sql
INSERT INTO agent_sms_credits (agent_id, credits, transaction_type, amount_paid, description) 
VALUES (YOUR_USER_ID, 10, 'purchase', 100, 'Test credits');
```
3. Go to agent/share-poll.php
4. Select "SMS" method
5. Enter test phone number (e.g., 08012345678)
6. Check if SMS is received
7. Verify credits were deducted

### Test WhatsApp Sharing
1. No configuration needed!
2. Go to agent/share-poll.php
3. Select "WhatsApp" method
4. Enter phone numbers (comma-separated)
5. Click "Share Poll"
6. Page will show clickable WhatsApp links
7. Click a link - WhatsApp should open with pre-filled message
8. Send the message manually

## Tracking & Commissions

### How Tracking Works
- Each share creates a unique tracking code (e.g., "POLL1-USR5-1234567890")
- Format: `POLL{poll_id}-USR{agent_id}-{timestamp}`
- Tracking link: `{SITE_URL}poll-response.php?poll_id={id}&ref={tracking_code}`
- When someone responds via the link, commission is credited

### Commission Rates
Defined in poll creation (default ₦1,000 per response):
```sql
SELECT payout_per_response FROM polls WHERE id = ?
```

### Viewing Earnings
- Agents can see earnings in `agent/payouts.php`
- Shows total earned, paid, and pending
- Admin approves payouts in `admin/agent-payouts.php`

## Database Schema

### agent_sms_credits Table
```sql
CREATE TABLE agent_sms_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    credits INT NOT NULL DEFAULT 0,
    transaction_type ENUM('purchase', 'used', 'refund') NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Transaction Types:**
- `purchase`: Agent buys credits (+credits, +amount_paid)
- `used`: Agent sends SMS (-1 credit, 0 amount_paid)
- `refund`: Admin refunds credits (+credits, 0 amount_paid)

**Balance Calculation:**
```sql
SELECT 
    SUM(CASE 
        WHEN transaction_type = 'purchase' THEN credits
        WHEN transaction_type = 'refund' THEN credits
        WHEN transaction_type = 'used' THEN -credits
        ELSE 0
    END) as balance
FROM agent_sms_credits 
WHERE agent_id = ?
```

### poll_shares Table
```sql
-- Already exists in your database
-- Columns: id, poll_id, agent_id, share_method, recipients, tracking_code, created_at
```

## Important Functions

### Email Sending
```php
sendEmailViaBrevo($to, $subject, $body)
// Returns: true on success, false on failure
// Located in: functions.php
```

### SMS Sending
```php
sendSMSViaTermii($to, $message)
// Returns: true on success, false on failure
// Auto-formats phone numbers (adds +234 if needed)
// Located in: functions.php
```

### Credits Management
```php
getAgentSMSCredits($agent_id)
// Returns: current credit balance (int)

addAgentSMSCredits($agent_id, $credits, $amount_paid, $description)
// Returns: true on success, false on failure

deductAgentSMSCredit($agent_id, $description)
// Returns: true on success, false on failure
// Deducts exactly 1 credit
```

## Troubleshooting

### Email Not Sending
1. Check if Brevo API key is configured:
```sql
SELECT setting_value FROM site_settings WHERE setting_key = 'brevo_api_key';
```
2. Check Brevo dashboard for errors
3. Verify email address format is correct
4. Check daily limit (300 emails/day on free tier)

### SMS Not Sending
1. Check if Termii API key is configured
2. Verify Termii account has sufficient balance
3. Check phone number format (should be 11 digits or +234...)
4. Verify agent has SMS credits:
```sql
SELECT SUM(CASE 
    WHEN transaction_type IN ('purchase', 'refund') THEN credits
    WHEN transaction_type = 'used' THEN -credits
END) as balance
FROM agent_sms_credits WHERE agent_id = ?;
```
5. Check Termii dashboard for delivery status

### WhatsApp Links Not Working
1. Ensure phone numbers don't have special characters
2. Check if WhatsApp is installed on device
3. Try different browser if web.whatsapp.com doesn't open
4. Verify URL encoding of message text

### Tracking Not Working
1. Check if tracking code was saved in database:
```sql
SELECT * FROM poll_shares WHERE tracking_code = 'YOUR_CODE';
```
2. Verify tracking link format is correct
3. Check poll-response.php handles `ref` parameter
4. Ensure poll is still active and not expired

## Production Checklist

Before going live:

- [ ] Configure Brevo API key (production)
- [ ] Configure Termii API key (production)
- [ ] Fund Termii account with sufficient balance
- [ ] Integrate Paystack payment (replace development mode)
- [ ] Set production Paystack keys
- [ ] Test all three sharing methods
- [ ] Test commission tracking
- [ ] Test payout system
- [ ] Set SMS credit prices based on actual Termii costs
- [ ] Add Terms & Conditions for SMS credits
- [ ] Add refund policy
- [ ] Monitor API usage and costs

## Cost Estimation

### Monthly Costs (Example: 1,000 polls shared)

**Brevo Email:**
- Free tier: 300 emails/day = 9,000/month
- Paid tier: $25/month for 20,000 emails
- **Estimated: $0-25/month**

**Termii SMS:**
- Cost: ₦3/SMS × number of SMS sent
- Agent pays via credits, not direct cost to you
- Only cost is your Termii account funding
- **Estimated: Variable based on agent usage**

**WhatsApp:**
- Completely free (manual sending)
- **Estimated: ₦0/month**

### Revenue (Example)
If agents buy 1,000 SMS credits at ₦8 average = ₦8,000
If Termii cost is ₦3/SMS = ₦3,000 cost
**Profit: ₦5,000** from SMS credits

## Support

For API-related issues:
- **Brevo Support:** https://help.brevo.com
- **Termii Support:** support@termii.com
- **Paystack Support:** support@paystack.com

For code issues:
- Check error logs in PHP
- Enable debugging in development
- Review database transactions
- Check API response codes

## Future Enhancements

Potential improvements:
1. Bulk import contacts from CSV
2. Schedule sharing (send later)
3. SMS templates/presets
4. A/B testing different messages
5. Analytics dashboard (open rates, click rates)
6. Auto-retry failed sends
7. Blacklist management (unsubscribers)
8. WhatsApp Business API integration (paid, but automated)
9. Telegram sharing support
10. Social media sharing (Facebook, Twitter)
