# Agent Earning Guide

## How Agents Get Paid

Agents earn money in TWO ways:
1. **Direct Completion**: When agents complete polls themselves
2. **Referral Commission**: When people complete polls through their referral links

Both methods pay the same amount per response (set in poll's `price_per_response`).

## Step-by-Step Process

### Method 1: Direct Completion (Easiest)
1. Browse available polls
2. Click on a poll to view it
3. You'll see a green card showing: "Earn ₦X.XX"
4. Complete the poll by answering all questions
5. Submit your response
6. You automatically get credited!

**What happens:**
- System detects you're an agent (role = 'agent')
- Checks poll's `price_per_response` value
- Credits your account in `agent_earnings` table
- Updates your `pending_earnings`
- Description: "Direct completion of poll response"

### Method 2: Referral Commission (More Earning Potential)

#### 1. Get Your Referral Link
- Go to **Dashboard** → **Browse Polls** → **Share & Earn** → **Share Poll**
- Or directly: `http://localhost/opinion/agent/share-poll.php?poll_id=X`
- You'll see a green card titled **"Your Referral Link"**
- This link has a tracking code that identifies you: `?ref=POLL5-USR2-1234567890`

#### 2. Share Your Link
You can share your referral link via:
- **Copy Link** - Click the button to copy your unique link
- **WhatsApp** - Direct share button
- **Facebook** - Direct share button  
- **Twitter** - Direct share button
- **Email** - Use the email form
- **SMS** - Use the SMS form (requires credits)

#### 3. Others Complete the Poll
- When someone clicks YOUR referral link
- They see the poll with the tracking code saved
- They complete the poll
- The system records the tracking code with their response

#### 4. You Get Paid!
- The system extracts your user ID from the tracking code
- It checks the poll's `price_per_response` (e.g., ₦1,000)
- It credits your account in the `agent_earnings` table
- Your `pending_earnings` increases
- Description: "Referral commission for poll response (Tracking: XXX)"

## Example Scenarios

**Method 1: Direct Completion (NEW!)** ✅
```
Agent logs in → Clicks "Browse Polls" → Clicks poll → Answers poll → Submits
Result: Agent gets paid immediately! ✅
Database: agent_earnings entry created, description: "Direct completion of poll response"
```

**Method 2: Referral Commission** ✅
```
Agent logs in → Goes to "Share Poll" → Copies referral link
Agent shares link with friend on WhatsApp
Friend clicks link → Completes poll
Result: Agent earns ₦1,000 from referral! ✅
Database: agent_earnings entry created, description: "Referral commission for poll response"
```

**Both methods work!** You can earn by answering polls yourself AND by sharing them!

## Tracking Code Format

Format: `POLL{poll_id}-USR{agent_id}-{timestamp}`
Example: `POLL5-USR2-1734012345`

- `POLL5` = Poll ID 5
- `USR2` = Agent user ID 2
- `1734012345` = Unix timestamp

## Viewing Your Earnings

1. Go to **Dashboard** → **Earnings** → **My Earnings**
2. Or directly: `http://localhost/opinion/agent/my-earnings.php`
3. You'll see:
   - Total earnings
   - Pending earnings
   - Approved earnings
   - List of all commissions

## Payment Information Display

When you view a poll you're sharing, you'll see:
- **Green card at top** showing: "Earn ₦X.XX per completed response"
- **In Poll Statistics**: Target responses and current responses
- This helps you know how much you can earn!

## Database Tables

- **agent_earnings**: Records each commission
- **poll_responses**: Has `tracking_code` column
- **users**: Has `pending_earnings` and `total_earnings` columns

## Testing Your Setup

### Test 1: Generate Referral Link
1. Login as agent
2. Go to `/agent/share-poll.php?poll_id=5`
3. Look for green card "Your Referral Link"
4. Copy the link (should contain `?ref=POLL5-USR{your_id}-...`)

### Test 2: Complete Poll via Referral Link
1. Open incognito/private window OR use different browser
2. Paste your referral link
3. Complete the poll
4. Check database:
```sql
SELECT * FROM poll_responses ORDER BY id DESC LIMIT 1;
-- Should show your tracking_code

SELECT * FROM agent_earnings WHERE agent_id = YOUR_ID ORDER BY id DESC LIMIT 1;
-- Should show new commission
```

### Test 3: View Earnings
1. Go to `/agent/my-earnings.php`
2. Should see your new commission listed

## Common Issues

### Issue: "I answered a poll but got no earnings"
**Solution**: You need to use your REFERRAL LINK. Other people must answer through your link, not you answering directly.

### Issue: "Tracking code is NULL in database"
**Solution**: The poll was accessed without a referral link. Must use the link from "Share Poll" page with `?ref=` parameter.

### Issue: "Agent earnings table is empty"
**Check**:
1. Did you use referral link? (check `tracking_code` in poll_responses)
2. Is `price_per_response` > 0 in polls table?
3. Is agent status = 'approved'?
4. Is agent suspension_status != 'suspended'?

## SQL Queries for Debugging

### Check if tracking code was recorded:
```sql
SELECT id, poll_id, respondent_id, tracking_code, responded_at 
FROM poll_responses 
WHERE poll_id = 5 
ORDER BY id DESC;
```

### Check agent earnings:
```sql
SELECT * FROM agent_earnings 
WHERE agent_id = YOUR_ID 
ORDER BY created_at DESC;
```

### Check poll payment rate:
```sql
SELECT id, title, price_per_response, status 
FROM polls 
WHERE id = 5;
```

### Manual credit (if needed):
```sql
INSERT INTO agent_earnings 
(agent_id, poll_id, earning_type, amount, description, status) 
VALUES (2, 5, 'poll_response', 1000.00, 'Manual test credit', 'pending');

UPDATE users 
SET pending_earnings = pending_earnings + 1000,
    total_earnings = total_earnings + 1000
WHERE id = 2;
```

## Summary

✅ **DO**: Share your referral link from "Share Poll" page
✅ **DO**: Get others to complete polls via your link
✅ **DO**: Check earnings in "My Earnings" page

❌ **DON'T**: Answer polls directly without referral link
❌ **DON'T**: Expect earnings when you answer your own polls
❌ **DON'T**: Share regular poll links (must include ?ref= parameter)
