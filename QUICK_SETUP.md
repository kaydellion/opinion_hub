# Quick Setup Guide - Poll Sharing System

## 🚀 Get Started in 5 Minutes

### Step 1: Database Setup ✅ (Already Done)
The database table has already been created. If you need to verify:

```sql
-- Check if table exists
SHOW TABLES LIKE 'agent_sms_credits';

-- View table structure
DESCRIBE agent_sms_credits;
```

### Step 2: Configure Brevo (Email - FREE)

**2.1 Create Account (2 minutes)**
1. Go to: https://www.brevo.com
2. Click "Sign up free"
3. Enter email, create password
4. Verify your email address

**2.2 Get API Key (1 minute)**
1. Log in to Brevo dashboard
2. Click your name (top right) → "SMTP & API"
3. Click "API Keys" tab
4. Click "Generate a new API key"
5. Name it: "Opinion Hub NG"
6. Copy the key (starts with `xkeysib-`)

**2.3 Add to Database (30 seconds)**
```sql
INSERT INTO site_settings (setting_key, setting_value, created_at) 
VALUES ('brevo_api_key', 'xkeysib-paste-your-actual-key-here', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'xkeysib-paste-your-actual-key-here';
```

**✅ Email sharing is now active!** (300 free emails/day)

---

### Step 3: Configure Termii (SMS - PAID)

**3.1 Create Account (3 minutes)**
1. Go to: https://termii.com
2. Click "Get Started"
3. Sign up with business email
4. Complete KYC verification (upload ID)
5. Wait for approval (usually 24-48 hours)

**3.2 Get API Key (1 minute)**
1. Log in to Termii dashboard
2. Go to "Settings" or "Developer"
3. Find "API Key" section
4. Copy your API key

**3.3 Fund Account (2 minutes)**
1. In Termii dashboard, go to "Billing" or "Wallet"
2. Click "Fund Wallet"
3. Add minimum ₦1,000 (recommended ₦5,000)
4. Choose payment method (Card/Bank Transfer)
5. Complete payment

**3.4 Add to Database (30 seconds)**
```sql
INSERT INTO site_settings (setting_key, setting_value, created_at) 
VALUES ('termii_api_key', 'paste-your-actual-key-here', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'paste-your-actual-key-here';
```

**✅ SMS sharing is now active!** (Agents need to buy credits)

---

### Step 4: Test the System

#### Test Email Sharing
1. Go to: `http://localhost/opinion/agent/share-poll.php?poll_id=1`
2. Select "Email" method
3. Enter your email: `your-email@example.com`
4. Click "Share Poll"
5. Check your inbox for the poll invitation

#### Test WhatsApp Sharing (No setup needed!)
1. Go to: `http://localhost/opinion/agent/share-poll.php?poll_id=1`
2. Select "WhatsApp" method
3. Enter phone number: `08012345678`
4. Click "Share Poll"
5. Click the generated WhatsApp link
6. WhatsApp should open with pre-filled message

#### Test SMS Sharing
**First, give yourself test credits:**
```sql
-- Get your user_id first (replace YOUR_EMAIL)
SELECT id FROM users WHERE email = 'YOUR_EMAIL@example.com';

-- Add 10 test credits (replace YOUR_USER_ID)
INSERT INTO agent_sms_credits (agent_id, credits, transaction_type, amount_paid, description) 
VALUES (YOUR_USER_ID, 10, 'purchase', 100, 'Test credits for development');
```

**Then test:**
1. Go to: `http://localhost/opinion/agent/share-poll.php?poll_id=1`
2. You should see "SMS Credits: 10 credits available" alert
3. Select "SMS" method
4. Enter your phone number: `08012345678`
5. Click "Share Poll"
6. Check your phone for the SMS

---

### Step 5: Buy SMS Credits (For Real Use)

#### As an Agent:
1. Go to: `http://localhost/opinion/agent/buy-sms-credits.php`
2. Choose a package:
   - **Starter:** 10 credits = ₦100
   - **Basic:** 50 credits = ₦450 (Popular)
   - **Standard:** 100 credits = ₦800
   - **Professional:** 200 credits = ₦1,500 (Best Value)
   - **Enterprise:** 500 credits = ₦3,500
3. Click "Buy Now"

**Note:** Currently in development mode - credits are added directly. 
In production, you'll need to integrate Paystack payment.

---

### Step 6: Admin Management

#### View SMS Credits System-Wide:
1. Login as admin
2. Click your name (top right) → "SMS Credits"
3. See all agents, balances, and transactions

#### Manually Adjust Credits:
1. In SMS Credits page, find the agent
2. Click "Adjust" button
3. Enter amount (+10 to add, -5 to deduct)
4. Enter description (reason)
5. Click "Save Adjustment"

---

## 🔍 Troubleshooting

### Email Not Sending?
1. Check if API key is configured:
   ```sql
   SELECT setting_value FROM site_settings WHERE setting_key = 'brevo_api_key';
   ```
2. Verify key starts with `xkeysib-`
3. Check Brevo dashboard → Logs for errors
4. Ensure you haven't exceeded 300 emails/day

### SMS Not Sending?
1. Check if API key is configured:
   ```sql
   SELECT setting_value FROM site_settings WHERE setting_key = 'termii_api_key';
   ```
2. Verify Termii account has balance
3. Check if agent has SMS credits:
   ```sql
   SELECT SUM(CASE 
       WHEN transaction_type IN ('purchase', 'refund') THEN credits
       WHEN transaction_type = 'used' THEN -credits
   END) as balance
   FROM agent_sms_credits 
   WHERE agent_id = YOUR_USER_ID;
   ```
4. Check Termii dashboard → SMS Logs

### WhatsApp Link Not Opening?
1. Ensure WhatsApp is installed on device
2. Try different browser
3. Check if phone number is correct (11 digits)
4. On desktop, it should open web.whatsapp.com

---

## 📋 Production Checklist

Before going live with real users:

### Required:
- [ ] Get Brevo account (FREE)
- [ ] Configure Brevo API key
- [ ] Get Termii account (PAID)
- [ ] Fund Termii wallet
- [ ] Configure Termii API key
- [ ] Test all 3 sharing methods
- [ ] Verify tracking codes work
- [ ] Test commission calculations

### Recommended:
- [ ] Get Paystack account
- [ ] Configure Paystack keys
- [ ] Integrate payment for SMS credits
- [ ] Set production SMS credit prices
- [ ] Create Terms & Conditions for SMS credits
- [ ] Add refund policy
- [ ] Set up monitoring/alerts for API failures

### Optional:
- [ ] Set up email templates in Brevo
- [ ] Configure Termii sender ID (branded SMS)
- [ ] Add analytics tracking
- [ ] Create backup API keys
- [ ] Set up usage alerts

---

## 💰 Cost Breakdown

### Email (Brevo)
- **Free Tier:** 300 emails/day = 9,000/month
- **Paid Tier:** $25/month for 20,000 emails
- **Recommendation:** Start with free tier

### SMS (Termii)
- **Cost per SMS:** ₦2-4 (depending on volume/route)
- **Agent Pays:** ₦7-10 per SMS (you set the price)
- **Your Profit:** ₦3-8 per SMS
- **Example:** If 100 agents each send 50 SMS/month:
  - Total SMS: 5,000
  - Revenue: ₦35,000 - ₦50,000
  - Termii Cost: ₦10,000 - ₦20,000
  - **Profit: ₦15,000 - ₦40,000/month**

### WhatsApp
- **Cost:** FREE (manual sending)
- **Alternative:** WhatsApp Business API (~$0.005-0.015 per message, but requires approval)

---

## 📞 API Support

### Brevo (Email)
- **Email:** support@brevo.com
- **Docs:** https://developers.brevo.com
- **Dashboard:** https://app.brevo.com
- **Status:** https://status.brevo.com

### Termii (SMS)
- **Email:** support@termii.com
- **Docs:** https://developers.termii.com
- **Dashboard:** https://accounts.termii.com
- **WhatsApp:** +234 903 985 7518

### Paystack (Payment)
- **Email:** support@paystack.com
- **Docs:** https://paystack.com/docs
- **Dashboard:** https://dashboard.paystack.com
- **Phone:** +234 1 453 5710

---

## 🎯 Quick Command Reference

### Check System Status
```sql
-- Check if APIs are configured
SELECT setting_key, setting_value 
FROM site_settings 
WHERE setting_key IN ('brevo_api_key', 'termii_api_key');

-- Check total credits in system
SELECT 
    COUNT(DISTINCT agent_id) as total_agents,
    SUM(CASE 
        WHEN transaction_type IN ('purchase', 'refund') THEN credits
        WHEN transaction_type = 'used' THEN -credits
    END) as total_credits
FROM agent_sms_credits;

-- Check recent shares
SELECT 
    ps.*,
    u.full_name,
    p.title as poll_title
FROM poll_shares ps
JOIN users u ON ps.agent_id = u.id
JOIN polls p ON ps.poll_id = p.id
ORDER BY ps.created_at DESC
LIMIT 10;
```

### Add Test Data
```sql
-- Add test agent (if needed)
INSERT INTO users (first_name, last_name, email, password, user_type, created_at) 
VALUES ('Test', 'Agent', 'testagent@example.com', MD5('password123'), 'agent', NOW());

-- Give test credits
INSERT INTO agent_sms_credits (agent_id, credits, transaction_type, amount_paid, description) 
VALUES (LAST_INSERT_ID(), 100, 'purchase', 800, 'Initial test credits');
```

### Reset for Fresh Start
```sql
-- Clear all SMS credits (USE WITH CAUTION!)
TRUNCATE TABLE agent_sms_credits;

-- Clear all poll shares
TRUNCATE TABLE poll_shares;
```

---

## 🎉 You're All Set!

Your poll sharing system is now ready to use. Agents can:
1. ✅ Share polls via **Email** (free, up to 300/day)
2. ✅ Share polls via **SMS** (paid via credits)
3. ✅ Share polls via **WhatsApp** (free, manual)

All shares include unique tracking codes that:
- Track who sent the invitation
- Track when recipients respond
- Calculate commissions automatically
- Provide analytics for agents

**Happy sharing! 🚀**

---

*For detailed documentation, see: `SHARING_SYSTEM_GUIDE.md`*
*For implementation details, see: `IMPLEMENTATION_SUMMARY.md`*
