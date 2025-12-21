# Agent Features Implementation Summary

## Overview
This document outlines all the agent features that have been implemented for Opinion Hub NG polling platform, per the requirements from pollwebestite.txt.

## Implemented Features

### 1. Agent Registration & Approval System
**Files:**
- `agent/register-agent.php` - Agent registration form for logged-in users
- `agent/become-agent.php` - Information page about becoming an agent
- Database: `users` table with `agent_status` (pending/approved/rejected)

**Features:**
- Logged-in users can upgrade to agent role
- Collects: phone, address, state, LGA, bank details, payment preference
- Sets status to 'pending' upon application
- Admin approval workflow (to be implemented in admin panel)
- Email notifications on approval (to be implemented)

### 2. Payment Preferences
**Files:**
- `agent/register-agent.php` - Payment preference selection
- Database: `users.payment_preference` ENUM('cash', 'airtime', 'data')

**Features:**
- Agents choose payment method during registration: Bank Transfer (Cash), Airtime, or Data Bundle
- Preference displayed in payout requests
- Can be updated in profile settings

### 3. Poll Sharing System
**Files:**
- `agent/share-poll.php` - Interface for sharing polls via Email/SMS/WhatsApp
- Database: `poll_shares` table with tracking

**Features:**
- Share polls via Email, SMS, or WhatsApp
- Unique tracking codes for each share
- Track clicks and responses per share
- Custom message option
- Sharing history showing performance per recipient
- Earnings calculation: ₦1,000 per response from shared link
- Available only to approved agents

**Database Schema:**
```sql
poll_shares (
    id, poll_id, agent_id, 
    share_method ENUM('email','sms','whatsapp','link'),
    recipient, tracking_code, 
    clicks, responses, created_at
)
```

### 4. Payout Request System
**Files:**
- `agent/payouts.php` - Payout request and history page
- Database: `agent_payouts` table

**Features:**
- View earnings summary (total earned, paid, pending, available)
- Request payouts (minimum ₦5,000)
- Choose payment type (cash, airtime, data)
- Add notes/preferences
- View payout history with status tracking
- Payment processing within 5 working days
- Bank account validation required before payout

**Database Schema:**
```sql
agent_payouts (
    id, agent_id, amount, 
    payment_type ENUM('cash','airtime','data'),
    payment_method, 
    status ENUM('pending','processing','completed','failed'),
    reference_number, requested_at, processed_at, 
    processed_by, notes
)
```

### 5. Enhanced Agent Dashboard
**Files:**
- `dashboards/agent-dashboard.php` - Comprehensive agent dashboard

**Features:**
- Approval status alerts (pending/approved/rejected)
- Earnings summary cards:
  - Total Earnings (₦1,000 × responses)
  - Pending Earnings (if not yet approved)
  - Polls Completed
  - Average per Response
- Quick action buttons:
  - Browse Polls
  - Share Polls (approved agents only)
  - Request Payout (approved agents only)
  - Update Profile
  - Help & FAQ
- Active Polls to Share table (approved agents only)
  - Shows poll title, category, response count, end date
  - Direct share button for each poll
- Recent activity showing poll responses
- Banking information display
- Payment timeline info

### 6. Task Assignment System (Database Ready)
**Files:**
- Database: `agent_tasks` and `agent_task_assignments` tables created

**Features (To Be Implemented):**
- Admin can create tasks (assign specific polls to agents)
- Email notifications to assigned agents
- Agents can accept/reject tasks
- Track responses per task
- Calculate earnings per task
- Task status tracking (assigned/accepted/in_progress/completed/rejected)

**Database Schema:**
```sql
agent_tasks (
    id, poll_id, title, description,
    target_responses, commission_per_response,
    status ENUM('active','paused','completed','expired'),
    start_date, end_date, created_by, created_at
)

agent_task_assignments (
    id, task_id, agent_id,
    status ENUM('assigned','accepted','in_progress','completed','rejected'),
    assigned_at, accepted_at, completed_at,
    responses_count, earnings
)
```

## Database Migrations

### Migration 1: `add_agent_status.sql`
- Added `agent_status` ENUM('pending', 'approved', 'rejected') to users
- Added `agent_applied_at` timestamp
- Added `agent_approved_at` timestamp
- Added `agent_approved_by` INT (admin user ID)

### Migration 2: `add_agent_features.sql`
- Added `payment_preference` ENUM('cash', 'airtime', 'data') to users
- Added `total_earnings`, `pending_earnings`, `paid_earnings` DECIMAL to users
- Created `agent_payouts` table for payment tracking
- Created `poll_shares` table for poll distribution tracking
- Created `agent_tasks` table for task management
- Created `agent_task_assignments` table for agent-task relationships

## Agent Earning Structure (Per Requirements)

**Rate:** ₦1,000 per completed poll response

**Payment Methods:**
1. Bank Transfer (Cash)
2. Airtime (any network)
3. Data Bundle (any network)

**Payment Timeline:** Within 5 working days after approval

**Minimum Payout:** ₦5,000

**Sources of Earnings:**
1. Direct poll responses by agent
2. Responses from shared poll links (tracked via `poll_shares.tracking_code`)
3. Assigned task completions (future implementation)

## Agent Workflow

### 1. Registration
1. User signs up as regular user
2. User logs in and navigates to "Become an Agent"
3. User fills out agent registration form
4. System sets `role = 'agent'` and `agent_status = 'pending'`
5. User sees "pending approval" message in dashboard

### 2. Approval
1. Admin reviews agent application
2. Admin approves/rejects application
3. System sends email notification to agent
4. Approved agents can access sharing and payout features

### 3. Earning
1. Agent completes polls (+₦1,000 per poll)
2. Agent shares polls via Email/SMS/WhatsApp
3. Recipients click tracking link
4. Recipients complete poll (+₦1,000 to agent's earnings)
5. Earnings tracked in dashboard

### 4. Payout
1. Agent navigates to "Request Payout"
2. Agent enters amount (minimum ₦5,000)
3. Agent selects payment type (cash/airtime/data)
4. System creates payout request with status 'pending'
5. Admin processes payout within 5 working days
6. System updates status to 'completed' and adds reference number
7. Agent receives payment

## Next Steps (To Be Implemented)

### Admin Features
1. **Agent Approval Interface**
   - List pending agent applications
   - Approve/reject with reason
   - Send email notifications

2. **Payout Management**
   - List pending payout requests
   - Process payments (mark as processing/completed/failed)
   - Add payment reference numbers
   - Bulk payment processing

3. **Task Assignment**
   - Create tasks for specific polls
   - Assign tasks to agents via email
   - Track task performance
   - Calculate task-based earnings

### Email Integration
1. **Agent Approval Email**
   - Welcome email on approval
   - Rejection email with reason

2. **Task Notification Email**
   - Email when task is assigned
   - Email when task is completed

3. **Poll Sharing Emails**
   - Send poll invitations via Brevo API
   - Track email opens and clicks

### SMS Integration (Termii)
1. **Poll Sharing SMS**
   - Send poll links via SMS
   - Track delivery and clicks

### Tracking Enhancements
1. **Click Tracking**
   - Track when shared poll links are clicked
   - Update `poll_shares.clicks` counter

2. **Response Attribution**
   - Link responses to tracking codes
   - Update `poll_shares.responses` counter
   - Calculate agent earnings from shares

## File Structure
```
/opinion
├── agent/
│   ├── become-agent.php         # Info page about agents
│   ├── register-agent.php       # Agent registration form
│   ├── share-poll.php           # Poll sharing interface
│   └── payouts.php              # Payout requests & history
├── dashboards/
│   └── agent-dashboard.php      # Enhanced agent dashboard
├── add_agent_status.sql         # Migration 1
└── add_agent_features.sql       # Migration 2
```

## Key Constants
- **Agent Commission:** ₦1,000 per poll response
- **Minimum Payout:** ₦5,000
- **Payment Timeline:** 5 working days
- **Approval Timeline:** 48 hours (mentioned in UI)
- **Company:** Foraminifera Market Research Limited
- **Contact:** +234 (0) 803 3782 777, hello@opinionhub.ng

## Design Consistency
All agent pages use:
- Bootstrap 5.3.0
- Font Awesome 6.4.0 icons
- Poppins font
- Color scheme: Primary (Indigo #6366f1) - To be updated to Grey/Orange/Black per requirements
- Responsive layouts
- Alert messages for user feedback
- Card-based UI components

## Testing Checklist
- [ ] User can register as agent
- [ ] Agent status shows as 'pending' after registration
- [ ] Payment preference is saved correctly
- [ ] Approved agents see sharing and payout options
- [ ] Pending agents see appropriate messages
- [ ] Poll sharing form works for all methods (email/SMS/WhatsApp)
- [ ] Tracking codes are unique
- [ ] Sharing history displays correctly
- [ ] Payout calculation is accurate (₦1,000 × responses - paid)
- [ ] Minimum payout validation works (₦5,000)
- [ ] Payout history shows all statuses
- [ ] Agent dashboard shows correct earnings
- [ ] Active polls list displays for approved agents
- [ ] Banking info displays/validates correctly
