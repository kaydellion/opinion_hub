# Opinion Hub NG - Fullstack Polling & Survey Application

## Overview
Opinion Hub NG is a comprehensive polling and survey platform designed for Nigeria. It enables businesses, political organizations, researchers, and individuals to create polls, gather insights, and make data-driven decisions.

## Features

### Core Functionality
- ✅ User authentication (Login/Register/Logout)
- ✅ Role-based access control (Admin, Client, Agent, User)
- ✅ Multi-step poll/survey creation
- ✅ Multiple question types (Multiple Choice, Ratings, Open-ended, Yes/No, etc.)
- ✅ Real-time poll results with charts
- ✅ Agent management system
- ✅ Subscription plans (Free, Basic, Classic, Enterprise)
- ✅ Payment integration with vPay Africa
- ✅ SMS/Email/WhatsApp messaging system
- ✅ Advertisement management
- ✅ Comprehensive databank for poll results

### User Roles

#### Admin
- Manage all users, polls, and system settings
- Approve/reject agent applications
- Manage categories and subcategories
- View system-wide analytics
- Manage subscription plans

#### Client
- Create and manage polls/surveys
- Send invitations via SMS/Email/WhatsApp
- View detailed poll results and analytics
- Export data
- Manage subscription and messaging credits

#### Agent
- Browse and accept poll tasks
- Earn commissions for completed responses
- Track earnings and request payouts
- View task progress

#### Regular User
- Participate in polls
- View poll results
- Become an agent

## Installation

### Prerequisites
- XAMPP (or any LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Setup Instructions

1. **Clone/Copy the project**
   ```bash
   # Copy the 'opinion' folder to your htdocs directory
   # Default path: /Applications/XAMPP/xamppfiles/htdocs/opinion
   ```

2. **Create Database**
   ```bash
   # Open phpMyAdmin or MySQL command line
   # Import the database.sql file
   mysql -u root -p opinionhub_ng < database.sql
   ```

3. **Configure Database Connection**
   Edit `connect.php` and update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Your MySQL password
   define('DB_NAME', 'opinionhub_ng');
   define('SITE_URL', 'http://localhost/opinion/');
   ```

4. **Configure Payment Gateway**
   Update vPay Africa keys in `connect.php`:
   ```php
   define('VPAY_PUBLIC_KEY', 'vpay_pub_your_public_key');
   define('VPAY_SECRET_KEY', 'vpay_sec_your_secret_key');
   define('VPAY_MERCHANT_ID', 'your_merchant_id');
   ```
   Get credentials from https://vpay.africa

5. **Create Upload Directory**
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

6. **Start XAMPP**
   - Start Apache and MySQL services
   - Access the application at: `http://localhost/opinion/`

## File Structure

```
opinion/
├── actions.php              # Form submission handler
├── connect.php              # Database connection & config
├── functions.php            # Core functions
├── header.php              # Header template
├── footer.php              # Footer template
├── index.php               # Homepage
├── login.php               # Login page
├── register.php            # Registration page
├── dashboard.php           # Dashboard router
├── polls.php               # Browse polls
├── view-poll.php           # View & participate in poll
├── databank.php            # Poll results databank
├── subscription.php        # Subscription plans
├── about.php               # About page
├── contact.php             # Contact page
├── database.sql            # Database schema
├── dashboards/
│   ├── admin-dashboard.php
│   ├── client-dashboard.php
│   ├── agent-dashboard.php
│   └── user-dashboard.php
├── client/
│   ├── create-poll.php     # Poll creation
│   ├── my-polls.php        # Manage polls
│   ├── view-results.php    # Poll analytics
│   └── buy-credits.php     # Purchase messaging credits
├── agent/
│   ├── index.php           # Agent landing page
│   ├── my-tasks.php        # Agent tasks
│   └── earnings.php        # Earnings history
└── uploads/                # File uploads directory
```

## Database Schema

### Main Tables
- `users` - All system users
- `agents` - Agent-specific data
- `polls` - Poll/survey information
- `poll_questions` - Poll questions
- `poll_question_options` - Question options
- `poll_responses` - User responses
- `question_responses` - Individual question answers
- `subscription_plans` - Available plans
- `user_subscriptions` - User subscriptions
- `transactions` - Payment records
- `messaging_credits` - SMS/Email/WhatsApp credits
- `categories` - Poll categories
- `sub_categories` - Poll subcategories
- `advertisements` - Ad placements
- `blog_articles` - Blog posts

## Default Login Credentials

After installation, you can create an admin account:
1. Register a new account
2. Manually update the database:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```

## Key Features Implementation

### Poll Creation
1. Client logs in and navigates to Create Poll
2. Fills in poll details (title, description, category, type)
3. Adds questions with various types
4. Sets poll settings (voting rules, results visibility)
5. Configures distribution method (Agent, SMS, Email, WhatsApp)
6. Publishes poll

### Agent System
1. User registers as an agent
2. Completes profile with required information
3. Accepts contract agreement
4. Admin approves agent
5. Agent browses and accepts tasks
6. Earns commission per completed response
7. Requests payout when threshold is met

### Subscription Plans
- **Free Plan**: Limited features for testing
- **Basic Plan**: ₦65,000/month - For small businesses
- **Classic Plan**: ₦85,000/month - Most popular
- **Enterprise Plan**: ₦120,000/month - Unlimited features

### Payment Integration
- vPay Africa integration for secure payments
- Subscription payments
- Messaging credits purchase
- Agent payouts

## API Integration

### vPay Africa
Configure in `connect.php`:
```php
define('VPAY_PUBLIC_KEY', 'vpay_pub_your_key');
define('VPAY_SECRET_KEY', 'vpay_sec_your_key');
define('VPAY_MERCHANT_ID', 'your_merchant_id');
function initializePayment($user_id, $amount, $type) {
    // Initialize Paystack payment
}
```

### Messaging Gateways
- **SMS**: Termii API integration
- **Email**: Brevo (formerly Sendinblue) integration
- **WhatsApp**: WhatsApp Business API

## Security Features
- Password hashing with bcrypt
- SQL injection prevention
- CSRF protection
- Session management
- Input validation and sanitization
- Role-based access control

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Technologies Used
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Charts**: Chart.js
- **Payment**: vPay Africa API

## Support
For issues or questions:
- Email: hello@opinionhub.ng
- Phone: +234 (0) 803 3782 777

## License
Proprietary - FORAMINIFERA MARKET RESEARCH LIMITED

## Version
2.0.0 - Major Update

### What's New in v2.0

#### Blog System (Complete)
- ✅ Rich text blog post creation with TinyMCE editor
- ✅ Image upload and featured images
- ✅ Admin approval workflow with rejection reasons
- ✅ Like and comment system
- ✅ Social sharing (Email, WhatsApp, Twitter, Facebook)
- ✅ Category organization
- ✅ Draft support
- ✅ My Posts dashboard for agents

#### Poll Sharing System (Complete Overhaul)
- ✅ **Email Sharing**: Via Brevo API (300 free emails/day)
- ✅ **SMS Sharing**: Via Termii API with prepaid credit system
- ✅ **WhatsApp Sharing**: Manual sharing via web.whatsapp.com links
- ✅ Unique tracking codes for commission tracking
- ✅ Real-time delivery status

#### SMS Credits System (NEW)
- ✅ Purchase SMS credits with bulk discounts
- ✅ Transaction history and balance tracking
- ✅ Admin management of credits
- ✅ 5 credit packages (10 to 500 credits)
- ✅ Automated credit deduction on SMS send

#### Admin Tools (Enhanced)
- ✅ Blog approval page with rejection reasons
- ✅ Agent management dashboard
- ✅ SMS credits management system
- ✅ Payout processing interface
- ✅ System-wide analytics

#### Bug Fixes
- ✅ Fixed function redeclaration error in blog submission
- ✅ Fixed database column mismatch (author_id → user_id)
- ✅ Fixed broken earnings page link
- ✅ Added missing rejection_reason column

### New Documentation
- **QUICK_SETUP.md**: 5-minute setup guide for APIs
- **SHARING_SYSTEM_GUIDE.md**: Complete API configuration documentation
- **IMPLEMENTATION_SUMMARY.md**: Detailed feature list (26 features)

### Files Added (v2.0)
- `blog/create.php`
- `blog/submit-post.php`
- `blog/my-posts.php`
- `blog/view.php`
- `blog/edit.php`
- `blog/delete.php`
- `blog/like.php`
- `blog/comment.php`
- `blog/share.php`
- `blog.php`
- `admin/blog-approval.php`
- `admin/agent-management.php`
- `admin/agent-payouts.php`
- `admin/sms-credits-management.php`
- `agent/buy-sms-credits.php`
- `agent/share-poll.php` (major update)

### Database Changes (v2.0)
```sql
-- New table for SMS credits
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

-- Added column to blog_posts
ALTER TABLE blog_posts ADD COLUMN rejection_reason TEXT NULL AFTER status;
```

### API Integration Required
For full functionality, configure these in `site_settings` table:

1. **Brevo Email API** (Free - 300 emails/day)
   ```sql
   INSERT INTO site_settings (setting_key, setting_value) 
   VALUES ('brevo_api_key', 'your-key-here');
   ```
   Get key: https://www.brevo.com

2. **Termii SMS API** (Paid - ₦2-4 per SMS)
   ```sql
   INSERT INTO site_settings (setting_key, setting_value) 
   VALUES ('termii_api_key', 'your-key-here');
   ```
   Get key: https://termii.com

See `QUICK_SETUP.md` for step-by-step instructions.

## Contributors
Developed for Opinion Hub NG
# opinion_hub
