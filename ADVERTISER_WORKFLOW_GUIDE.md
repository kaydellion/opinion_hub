# Advertiser Self-Service System - Complete Guide

## Overview
Users can now create and manage their own advertisements through a self-service portal. The system includes payment integration, admin approval workflow, and performance tracking.

## User Roles & Access

### Who Can Advertise?
- **Client** role: Full access to advertisement features + poll creation
- **User** role: Can create advertisements (general users)
- **Agent** role: Can create advertisements
- **Admin** role: Can manually create ads and approve/reject user submissions

### Access Points
- **User Dashboard Menu**: Profile dropdown → "My Advertisements"
- **Direct URL**: `/client/my-ads.php`
- **Public Info**: `/advertise.php` (pricing and information)

## Complete Workflow

### Step 1: User Creates Advertisement

**Location**: `/client/my-ads.php`

**Process:**
1. Click "Create New Ad" button
2. Fill out advertisement form:
   - **Title** - Campaign name (required)
   - **Placement** - Where ad will appear (required)
   - **Ad Size** - Standard sizes (required)
   - **Start Date** - Campaign start (required, cannot be in past)
   - **End Date** - Campaign end (required, must be after start date)
   - **Ad Image** - Upload JPG/PNG/GIF (required)
   - **Target URL** - Landing page (required)
3. Submit form

**What Happens:**
- System calculates price based on placement and duration
- Ad saved with status = 'pending'
- Image uploaded to `/uploads/ads/`
- User redirected to payment page
- Admin receives email notification

### Step 2: Payment

**Location**: `/client/pay-for-ad.php?ad_id=X`

**Process:**
1. User reviews advertisement details
2. Sees calculated amount
3. Clicks "Pay Now" button
4. Paystack popup opens
5. User completes payment

**Pricing Logic:**
```php
Duration ≤ 7 days   → 7-day package price
Duration ≤ 14 days  → 14-day package price
Duration ≤ 30 days  → 30-day package price
Duration > 30 days  → Daily rate × number of days
```

**Pricing Table:**
| Placement | 7 Days | 14 Days | 30 Days | Per Day (>30) |
|-----------|--------|---------|---------|---------------|
| Homepage Top | ₦25,000 | ₦45,000 | ₦80,000 | ₦4,000 |
| Homepage Sidebar | ₦20,000 | ₦35,000 | ₦60,000 | ₦3,000 |
| Poll Page Top | ₦30,000 | ₦55,000 | ₦100,000 | ₦5,000 |
| Poll Page Sidebar | ₦22,000 | ₦40,000 | ₦70,000 | ₦3,500 |
| Dashboard | ₦18,000 | ₦30,000 | ₦50,000 | ₦2,500 |

### Step 3: Payment Callback

**Location**: `/client/ad-payment-callback.php`

**Process:**
1. Paystack redirects to callback
2. System verifies payment with Paystack API
3. Updates advertisement:
   - `amount_paid` = actual payment
   - `status` = 'pending' (awaiting approval)
4. Creates transaction record
5. Sends confirmation email to user
6. Sends notification to admin

**Emails Sent:**
- **To User**: Payment successful, awaiting approval
- **To Admin**: New paid ad requires review

### Step 4: Admin Review

**Location**: `/admin/ads.php`

**Admin View:**
- Alert banner shows count of pending paid ads
- Table shows all advertisements with:
  - Preview image
  - Title and advertiser name
  - Placement and size
  - Duration (date range)
  - Amount paid
  - Current views/clicks
  - Status badge

**Admin Actions:**
1. Click "Edit" on pending ad
2. Review image quality
3. Check target URL is appropriate
4. Verify dates and pricing
5. Change status:
   - **Active** - Approve and publish
   - **Rejected** - Decline (with reason)
   - **Paused** - Hold temporarily

**What Happens on Approval:**
- Status changes to 'active'
- Ad appears on site (if within date range)
- User can view performance metrics

**What Happens on Rejection:**
- Status changes to 'rejected'
- Ad does NOT appear on site
- User can see rejection in dashboard
- (Future: Automated refund process)

### Step 5: Campaign Runs

**Active Campaign:**
- Ad displays on specified placement
- Only shows if:
  - Status = 'active'
  - Current date ≥ start_date
  - Current date ≤ end_date
- Views tracked automatically (once per session)
- Clicks tracked via JavaScript

**User Monitoring:**
- Access via `/client/view-ad.php?id=X`
- See real-time metrics:
  - Total views
  - Total clicks
  - Click-through rate (CTR)
  - Cost per view
  - Cost per click
  - Days remaining

### Step 6: Campaign Ends

**Auto-Expiry:**
- System checks daily (in header.php)
- When end_date passes:
  - Status changes to 'paused'
  - Ad stops displaying
  - (Future: Email notification to user)

**User Options:**
- View final performance data
- Contact admin for renewal
- Create new campaign

## File Structure

### User-Facing Files
```
/client/my-ads.php              - Dashboard with all user's ads
/client/pay-for-ad.php          - Payment page
/client/ad-payment-callback.php - Payment verification
/client/view-ad.php             - Single ad detail view
/advertise.php                  - Public pricing page
```

### Admin Files
```
/admin/ads.php                  - Admin management interface
```

### Core Files
```
/functions.php                  - Ad display functions
/header.php                     - Auto-expiry check, menu items
/footer.php                     - Click tracking JavaScript
/track-ad.php                   - AJAX click tracking endpoint
```

## Database Schema

### advertisements Table
```sql
CREATE TABLE advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    advertiser_id INT,                    -- FK to users.id
    title VARCHAR(255),                   -- Campaign name
    placement VARCHAR(100),               -- Where to show
    ad_size VARCHAR(50),                  -- Image dimensions
    image_url VARCHAR(255),               -- Uploaded image path
    ad_url VARCHAR(255),                  -- Click destination
    status ENUM(...),                     -- pending/active/paused/rejected
    start_date DATE,                      -- Campaign start
    end_date DATE,                        -- Campaign end
    amount_paid DECIMAL(10,2),            -- Payment amount
    cost_per_view DECIMAL(10,2),          -- (unused, calculated on-the-fly)
    total_views INT DEFAULT 0,            -- View counter
    click_throughs INT DEFAULT 0,         -- Click counter
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Email Notifications

### User Receives:
1. **Ad Submitted** - When form is first submitted (before payment)
2. **Payment Successful** - After successful Paystack payment
3. **Ad Approved** - When admin activates ad (future enhancement)
4. **Ad Rejected** - When admin rejects ad (future enhancement)
5. **Campaign Expired** - When end_date passes (future enhancement)

### Admin Receives:
1. **New Ad Submitted** - When user submits unpaid ad
2. **Payment Received** - When user completes payment
3. **Requires Review** - Reminder for pending ads

## Dashboard Features

### `/client/my-ads.php`

**Statistics Cards:**
- Total Ads (all statuses)
- Active Ads (currently running)
- Total Views (all ads combined)
- Total Clicks (all ads combined)

**Ads Table:**
- Preview image
- Title and size
- Placement badge
- Date range
- Amount paid
- Views/Clicks with CTR
- Status badge
- Actions (Pay/View buttons)

**Empty State:**
- Friendly message for new users
- "Create Advertisement" button

### `/client/view-ad.php`

**Main Details:**
- Full-size image preview
- Placement and size
- Target URL (clickable)
- Start and end dates
- Days remaining countdown

**Performance Section:**
- Total views (large number)
- Click-throughs (large number)
- CTR percentage (calculated)
- Cost per view
- Cost per click

**Sidebar:**
- Status with icon and description
- Payment amount (large, green)
- Payment confirmation
- Important dates (created, updated)

## Payment Integration

### Paystack Configuration
- Uses `PAYSTACK_PUBLIC_KEY` for popup
- Uses `PAYSTACK_SECRET_KEY` for verification
- Amount in kobo (multiply by 100)
- Reference format: `AD_{ad_id}_{timestamp}`

### Payment Metadata
```javascript
{
    custom_fields: [
        {
            display_name: "Advertisement ID",
            variable_name: "ad_id",
            value: adId
        },
        {
            display_name: "Payment Type",
            variable_name: "payment_type",
            value: "advertisement"
        }
    ]
}
```

### Transaction Record
- Saved to `transactions` table
- Type: 'advertisement_payment'
- Status: 'completed'
- Links to user and payment reference

## Security Features

### Access Control
- Login required for all user pages
- Only own ads accessible (checked via `advertiser_id`)
- Admin role required for approval

### File Upload Security
- Allowed extensions: JPG, JPEG, PNG, GIF only
- Filename sanitization
- Unique filenames (user_id + timestamp)
- Storage in `/uploads/ads/` directory

### Payment Security
- Server-side verification with Paystack
- Payment reference validation
- Amount verification
- Double-payment prevention

### SQL Injection Prevention
- All queries use prepared statements
- User input sanitized with `sanitize()` function

## Frontend Display

### How Ads Appear
```php
// Homepage top
displayAd('homepage_top');

// Homepage sidebar
displayAd('homepage_sidebar');

// Poll pages
displayAd('poll_page_top');
displayAd('poll_page_sidebar');
```

### HTML Output
```html
<div class="advertisement" data-ad-id="15">
    <a href="https://target-url.com" target="_blank" onclick="trackAdClick(15)">
        <img src="/uploads/ads/ad_15_image.jpg" class="img-fluid ad-image" alt="Ad Title">
    </a>
</div>
```

### Ad Selection
- Random selection from active ads
- Filtered by placement
- Filtered by date range
- Filtered by status (active only)

## Tracking & Analytics

### View Tracking
- Increments on page load
- Session-based (once per session per ad)
- Stored in `$_SESSION['ad_views']` array
- Updates `total_views` in database

### Click Tracking
- JavaScript function: `trackAdClick(adId)`
- AJAX POST to `/track-ad.php`
- Updates `click_throughs` in database
- No session restriction (counts all clicks)

### Metrics Calculated
- **CTR**: `(clicks / views) × 100`
- **Cost per View**: `amount_paid / total_views`
- **Cost per Click**: `amount_paid / click_throughs`

## Future Enhancements

### Planned Features
1. **Automated Refunds** - For rejected ads
2. **Renewal System** - Extend expired campaigns
3. **A/B Testing** - Multiple images per ad
4. **Geographic Targeting** - Show ads by location
5. **Schedule Control** - Pause/resume campaigns
6. **Bulk Upload** - Multiple ads at once
7. **Analytics Dashboard** - Detailed performance charts
8. **Email Reports** - Weekly performance summaries

### Admin Features
1. **Bulk Approval** - Approve multiple ads at once
2. **Ad Preview** - See how ad looks before approval
3. **Advertiser Management** - View all ads by user
4. **Revenue Reports** - Total ad income tracking
5. **Quality Control** - Image dimension verification

## Troubleshooting

### Common Issues

**Ad Not Displaying:**
- Check status is 'active'
- Verify current date is between start_date and end_date
- Confirm placement name matches exactly
- Check image_url is valid path

**Payment Not Processing:**
- Verify PAYSTACK_PUBLIC_KEY is set
- Check PAYSTACK_SECRET_KEY for verification
- Ensure amount is positive number
- Check Paystack account is live (not test mode)

**Upload Failing:**
- Check `/uploads/ads/` directory exists
- Verify directory has write permissions (777)
- Confirm file is JPG/PNG/GIF
- Check file size is reasonable

**Tracking Not Working:**
- Verify JavaScript is enabled
- Check `/track-ad.php` is accessible
- Confirm ad_id is passed correctly
- Check database connection

## Support Contacts

**For Users:**
- Email: hello@opinionhub.ng
- Phone: +234 (0) 803 3782 777

**For Advertisers:**
- Email: ads@opinionhub.ng
- Pricing inquiries: /advertise.php

---

**System Version:** 2.0
**Last Updated:** November 2025
**Status:** Production Ready ✅
