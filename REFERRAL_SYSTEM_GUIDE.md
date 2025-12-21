# Referral System Implementation

## Summary of Changes

### 1. Created Agent Referrals Page
**File:** `agent/referrals.php`

**Features:**
- Display agent's unique referral code and shareable link
- Copy-to-clipboard functionality for easy sharing
- Statistics cards showing:
  - Total referrals
  - Active users
  - Subscribed users
  - Referral earnings
- Paginated table of all referred users
- User details: name, email, join date, status, responses, subscription status
- "How It Works" section explaining the referral process

### 2. Updated Header Navigation
**File:** `header.php`

**Changes:**
- **Removed** standalone "Share & Earn" dropdown from agent menu
- **Moved** Share & Earn items under "Browse Polls" dropdown for agents
- Agent's "Browse Polls" is now a dropdown containing:
  - All Polls
  - --- (divider) ---
  - Share & Earn (header)
  - Share Poll
  - My Referrals

**Menu Structure for Agents:**
```
Dashboard
Earnings ▼
  - My Payouts
  - Buy SMS Credits
Browse Polls ▼
  - All Polls
  - --- Share & Earn ---
  - Share Poll
  - My Referrals
Blog ▼
Notifications
...
```

### 3. Database Migration
**Files:** 
- `migrations/add_referral_system.sql` (SQL file)
- `migrations/run_referral_migration.php` (PHP migration runner)

**Database Changes:**
Added to `users` table:
- `referral_code` VARCHAR(20) UNIQUE - Agent's unique referral code
- `referred_by` INT - ID of referring agent
- `total_earnings` DECIMAL(10,2) - Total earnings from all sources
- `sms_credits` INT - SMS credits balance
- Indexes on `referral_code` and `referred_by`

Created `agent_earnings` table:
- Tracks all agent earnings
- Fields: agent_id, user_id, earning_type, amount, description, poll_id, reference, status
- Earning types: poll_response, poll_share, referral, subscription, other
- Status: pending, approved, paid

## Installation Steps

### Step 1: Run Database Migration
Open in browser:
```
http://localhost/opinion/migrations/run_referral_migration.php
```

This will:
- Add referral columns to users table
- Create agent_earnings table
- Add necessary indexes

### Step 2: Test the Referrals Page
1. Login as an agent
2. Navigate to "Browse Polls" → "My Referrals"
3. Or directly visit: `http://localhost/opinion/agent/referrals.php`

### Step 3: Test Referral Functionality
1. Copy the referral code or link
2. Share with a new user
3. New user signs up using the referral code (signup form needs to accept ref parameter)
4. Agent should see the new referral in their list

## Usage

### For Agents:
1. Go to **Browse Polls** → **My Referrals**
2. Copy your referral code or link
3. Share with potential users
4. Earn commissions when referred users:
   - Sign up and become active
   - Participate in polls
   - Subscribe to premium plans

### For Admin:
- Track agent performance through referral stats
- Manage agent earnings through the earnings table
- Approve/reject payout requests based on earnings

## Features

### Referrals Page:
✓ Unique referral code generation
✓ Copy-to-clipboard buttons
✓ Referral statistics dashboard
✓ Paginated referral list (20 per page)
✓ User activity tracking
✓ Subscription status display
✓ Responsive design
✓ "How It Works" guide

### Navigation:
✓ Cleaner menu structure
✓ Logical grouping of agent features
✓ Easy access to earning features
✓ Consistent with other user roles

## Future Enhancements

1. **Signup Integration:**
   - Update signup.php to accept `?ref=CODE` parameter
   - Auto-fill referral code field
   - Link new user to referring agent

2. **Earnings Automation:**
   - Auto-calculate earnings when referred users:
     - Complete poll responses
     - Subscribe to plans
     - Make purchases

3. **Commission Structure:**
   - Define commission rates for different actions
   - Implement tiered commission system
   - Add bonus for milestone achievements

4. **Referral Tracking:**
   - Track referral link clicks
   - Conversion rate analytics
   - Top performing agents leaderboard

5. **Email Notifications:**
   - Notify agent when someone uses their code
   - Send monthly referral performance reports
   - Alert on earnings milestones

## File Structure
```
opinion/
├── agent/
│   ├── referrals.php (NEW)
│   ├── share-poll.php
│   └── ...
├── migrations/
│   ├── add_referral_system.sql (NEW)
│   └── run_referral_migration.php (NEW)
├── header.php (MODIFIED)
└── REFERRAL_SYSTEM_GUIDE.md (NEW - this file)
```

## Testing Checklist

- [ ] Run database migration successfully
- [ ] Access referrals page without errors
- [ ] Verify referral code is generated
- [ ] Test copy-to-clipboard functionality
- [ ] Check referral statistics display correctly
- [ ] Verify pagination works (if you have 20+ referrals)
- [ ] Test navigation menu changes
- [ ] Verify "Browse Polls" dropdown works for agents
- [ ] Check that non-agents still see simple "Browse Polls" link
- [ ] Verify responsive design on mobile devices

## Notes

- The referral system is ready for agents to use
- Signup form needs to be updated to accept and process referral codes
- Commission calculation logic needs to be implemented
- Consider adding referral analytics dashboard for admins
