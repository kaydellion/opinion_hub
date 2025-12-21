# Advertisement System - Complete Implementation Guide

## Overview
The advertisement management system for Opinion Hub NG allows administrators to create, manage, and track advertisements across the platform. Advertisers can view pricing, submit requests, and admin can manage the complete lifecycle of ads.

## Database Schema

### advertisements Table
```sql
CREATE TABLE advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    advertiser_id INT DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    placement VARCHAR(100) NOT NULL,
    ad_size VARCHAR(50) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    ad_url VARCHAR(255) NOT NULL,
    status ENUM('pending','active','paused','rejected') DEFAULT 'pending',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    cost_per_view DECIMAL(10,2) DEFAULT 0.00,
    total_views INT DEFAULT 0,
    click_throughs INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Available Placements

1. **homepage_top** - Top banner on homepage (728x90)
2. **homepage_sidebar** - Sidebar on homepage (300x250)
3. **poll_page_top** - Top of poll pages (728x90)
4. **poll_page_sidebar** - Sidebar on poll pages (300x250)
5. **dashboard** - User dashboard banner (728x90)

## Core Functions (functions.php)

### 1. getActiveAds($placement, $limit = 1)
Retrieves active advertisements for a specific placement that are within their date range.

**Parameters:**
- `$placement` (string) - The placement identifier
- `$limit` (int) - Maximum number of ads to return (default: 1)

**Returns:** Array of advertisement records

**Example:**
```php
$ads = getActiveAds('homepage_sidebar', 3);
```

### 2. displayAd($placement, $class = '')
Displays an advertisement HTML for a specific placement.

**Parameters:**
- `$placement` (string) - The placement identifier
- `$class` (string) - Additional CSS classes

**Returns:** void (echoes HTML directly)

**Example:**
```php
displayAd('homepage_top', 'mb-4');
```

### 3. trackAdView($ad_id)
Tracks an advertisement view (increments total_views). Only tracks once per session.

**Parameters:**
- `$ad_id` (int) - The advertisement ID

**Returns:** bool - Success status

**Example:**
```php
$success = trackAdView(15);
```

### 4. trackAdClick($ad_id)
Tracks an advertisement click (increments click_throughs).

**Parameters:**
- `$ad_id` (int) - The advertisement ID

**Returns:** bool - Success status

**Example:**
```php
$success = trackAdClick(15);
```

### 5. pauseExpiredAds()
Auto-pauses advertisements that have passed their end_date.

**Returns:** int - Number of ads paused

**Called automatically:** Once per session in header.php

**Example:**
```php
$paused_count = pauseExpiredAds();
```

### 6. sendAdExpiryNotification($ad_id)
Sends email notification to advertiser when their ad expires.

**Parameters:**
- `$ad_id` (int) - The advertisement ID

**Returns:** bool - Success status

**Example:**
```php
sendAdExpiryNotification(15);
```

## Admin Interface (admin/ads.php)

### Features:
1. **View all advertisements** - Table with title, placement, duration, amount paid, views, clicks, status
2. **Create advertisement** - Modal form with all fields
3. **Edit advertisement** - Pre-filled modal with existing data
4. **Delete advertisement** - Confirmation dialog
5. **Upload ad image** - File upload with validation
6. **Assign advertiser** - Dropdown to select user
7. **Set date range** - Start and end date pickers with validation
8. **Track payment** - Amount paid field

### Table Columns:
- Image (thumbnail)
- Title
- Placement + Size
- Duration (start - end dates)
- Amount Paid
- Views/Clicks
- Status (badge)
- Actions (Edit/Delete buttons)

### Form Fields:
- **Title** - Advertisement title (required)
- **Advertiser** - Select from users (optional)
- **Placement** - Dropdown of available placements (required)
- **Ad Size** - Dropdown of standard sizes (required)
- **Start Date** - Campaign start date (required)
- **End Date** - Campaign end date (required, must be after start date)
- **Amount Paid** - Payment amount in Naira (required)
- **Ad Image** - File upload (JPG/PNG/GIF, required for new, optional for edit)
- **Target URL** - Landing page URL (required)
- **Status** - Pending/Active/Paused/Rejected (required)

### JavaScript Validation:
- End date must be after start date
- Image preview for existing ads
- Form reset on modal close
- Date picker minimum values

## Frontend Integration

### 1. Homepage (index.php)
```php
<!-- Top banner -->
<?php displayAd('homepage_top'); ?>

<!-- Sidebar ad -->
<div class="ad-label">Advertisement</div>
<?php displayAd('homepage_sidebar'); ?>
```

### 2. Poll Pages
```php
<!-- Top banner -->
<?php displayAd('poll_page_top'); ?>

<!-- Sidebar -->
<?php displayAd('poll_page_sidebar'); ?>
```

### 3. Dashboard
```php
<?php displayAd('dashboard'); ?>
```

## Click Tracking

### AJAX Endpoint (track-ad.php)
Accepts POST requests with `ad_id` parameter and increments click counter.

**Request:**
```javascript
fetch('/track-ad.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ad_id=15'
});
```

**Response:**
```json
{"success": true}
```

### JavaScript Function (footer.php)
```javascript
function trackAdClick(adId) {
    fetch('<?php echo SITE_URL; ?>track-ad.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ad_id=' + adId
    }).catch(err => console.error('Failed to track ad click:', err));
}
```

### HTML Implementation
```html
<a href="https://example.com" onclick="trackAdClick(15)">
    <img src="ad-image.jpg" alt="Advertisement">
</a>
```

## CSS Styling (header.php)

```css
.advertisement {
    margin: 20px 0;
    text-align: center;
    overflow: hidden;
}

.advertisement img.ad-image {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.advertisement:hover img.ad-image {
    transform: scale(1.02);
}

.ad-label {
    font-size: 10px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
}
```

## Advertiser-Facing Page (advertise.php)

### Features:
- Platform statistics
- Pricing packages
- Why advertise section
- Contact information
- Request quote functionality

### Pricing Display:
Shows packages with:
- Placement name and description
- Ad size
- Estimated impressions
- Pricing for 7/14/30 days

## Automatic Expiry System

### Implementation:
1. **Trigger**: Runs once per session in header.php
2. **Query**: Updates ads where `status='active' AND end_date < CURDATE()`
3. **Action**: Sets `status='paused'`
4. **Notification**: Can be extended to send email using `sendAdExpiryNotification()`

### Session Check:
```php
if (!isset($_SESSION['ads_checked'])) {
    pauseExpiredAds();
    $_SESSION['ads_checked'] = true;
}
```

## Reporting & Analytics

### Available Metrics:
1. **Total Views** - Number of times ad was displayed
2. **Click-throughs** - Number of times ad was clicked
3. **CTR** - Click-through rate (clicks/views * 100)
4. **Duration** - Campaign length
5. **Cost per View** - amount_paid / total_views
6. **Cost per Click** - amount_paid / click_throughs

### Future Enhancements:
- Admin analytics dashboard
- Advertiser portal to view own ad performance
- Geographic tracking
- Device type tracking
- Time-based performance analysis

## Workflow

### 1. Client Requests Advertisement
1. Client visits `/advertise.php`
2. Reviews pricing and placements
3. Contacts admin via email/phone

### 2. Admin Creates Ad
1. Admin navigates to `/admin/ads.php`
2. Clicks "Create Advertisement"
3. Fills in all fields:
   - Title
   - Assigns to client (advertiser_id)
   - Selects placement
   - Chooses size
   - Sets start and end dates
   - Enters amount paid
   - Uploads ad image
   - Sets target URL
   - Sets status to "pending" initially
4. Saves advertisement

### 3. Admin Activates Ad
1. Reviews uploaded ad for quality/compliance
2. Edits ad and changes status to "active"
3. Ad appears on site immediately (if within date range)

### 4. Campaign Runs
1. Users see ad on specified placement
2. System tracks views automatically
3. Clicks tracked via JavaScript
4. Analytics accumulate

### 5. Campaign Ends
1. System checks expiry daily (in header.php)
2. When end_date passes, status changes to "paused"
3. Optional: Email sent to advertiser
4. Ad stops displaying on site

### 6. Renewal (Optional)
1. Advertiser contacts admin for renewal
2. Admin edits ad:
   - Updates start_date and end_date
   - Updates amount_paid
   - Changes status back to "active"
3. Campaign resumes

## Security Considerations

### Input Validation:
- File upload restrictions (image types only)
- URL validation for ad_url
- Date validation (end > start)
- SQL injection prevention (prepared statements)

### Access Control:
- Only admin can manage ads (requireRole('admin'))
- Advertiser selection from registered users only
- File upload to secure directory

### Privacy:
- No personal tracking
- Session-based view tracking prevents inflation
- Click tracking via POST (not GET)

## Files Modified/Created

### Created:
1. `/track-ad.php` - AJAX endpoint for click tracking

### Modified:
1. `/functions.php` - Added 6 advertisement functions
2. `/header.php` - Added auto-expiry check and CSS
3. `/footer.php` - Added trackAdClick() JavaScript
4. `/admin/ads.php` - Enhanced with new fields and UI
5. `/index.php` - Integrated ad display examples

### Existing (for reference):
1. `/advertise.php` - Public-facing advertiser information page

## Testing Checklist

- [ ] Create advertisement via admin panel
- [ ] Upload and display ad image
- [ ] Set date range and verify activation
- [ ] Check ad displays on correct placement
- [ ] Verify view tracking increments
- [ ] Test click tracking via browser console
- [ ] Confirm expiry system pauses old ads
- [ ] Test edit functionality
- [ ] Verify delete works correctly
- [ ] Check responsive display on mobile
- [ ] Test with multiple placements simultaneously
- [ ] Verify advertiser email notifications

## Support & Maintenance

### Regular Tasks:
1. Monitor ad performance metrics
2. Review and approve pending ads
3. Handle advertiser inquiries
4. Update pricing as needed
5. Archive completed campaigns

### Troubleshooting:
- **Ad not displaying**: Check status, dates, placement name
- **Views not tracking**: Check session variables, database connection
- **Clicks not tracking**: Check JavaScript console, track-ad.php accessibility
- **Image not showing**: Verify file path, SITE_URL constant

---

**Implementation Date:** January 2025
**Version:** 1.0
**Author:** Opinion Hub NG Development Team
