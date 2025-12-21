# Quick Start Guide - Advertisement Display

## How to Display Ads on Any Page

### Step 1: Include the Header
Make sure your page includes `header.php`:
```php
<?php
$page_title = "Your Page Title";
include_once 'header.php';
?>
```

### Step 2: Display Advertisement
Use the `displayAd()` function anywhere in your page:

```php
<!-- Display a top banner ad -->
<?php displayAd('homepage_top'); ?>

<!-- Display a sidebar ad with custom class -->
<?php displayAd('homepage_sidebar', 'my-custom-class mb-4'); ?>
```

### Step 3: Add Ad Label (Optional)
For better UX, add a label above the ad:
```php
<div class="ad-label">Advertisement</div>
<?php displayAd('homepage_sidebar'); ?>
```

## Available Placements

| Placement ID | Recommended Size | Best For |
|-------------|-----------------|----------|
| `homepage_top` | 728x90 | Top banner on homepage |
| `homepage_sidebar` | 300x250 | Sidebar on homepage |
| `poll_page_top` | 728x90 | Top of poll pages |
| `poll_page_sidebar` | 300x250 | Sidebar on poll pages |
| `dashboard` | 728x90 | User dashboard |

## Example Layouts

### Layout 1: Full Width Banner
```php
<div class="container">
    <?php displayAd('homepage_top'); ?>
    
    <!-- Your content here -->
</div>
```

### Layout 2: Content with Sidebar Ad
```php
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Main content -->
            <h1>Your Content</h1>
            <p>Lorem ipsum...</p>
        </div>
        
        <div class="col-lg-4">
            <!-- Sidebar with ad -->
            <div class="ad-label">Advertisement</div>
            <?php displayAd('poll_page_sidebar'); ?>
            
            <!-- Other sidebar content -->
        </div>
    </div>
</div>
```

### Layout 3: Multiple Ad Placements
```php
<div class="container">
    <!-- Top banner -->
    <?php displayAd('homepage_top'); ?>
    
    <div class="row">
        <div class="col-lg-9">
            <!-- Main content -->
        </div>
        
        <div class="col-lg-3">
            <!-- Sidebar ad -->
            <div class="ad-label">Advertisement</div>
            <?php displayAd('homepage_sidebar'); ?>
        </div>
    </div>
</div>
```

## What Happens Automatically

✅ **View Tracking** - Each ad view is tracked once per session
✅ **Click Tracking** - Clicks are tracked via JavaScript
✅ **Expiry Management** - Expired ads are auto-paused
✅ **Responsive Display** - Ads scale on mobile devices
✅ **Date Range Filtering** - Only shows ads within active dates
✅ **Status Filtering** - Only shows 'active' status ads

## No Ads Scenario

If no active ads exist for a placement, the `displayAd()` function:
- Returns nothing (no output)
- Does not display placeholder
- Does not throw errors

This means pages work perfectly whether ads exist or not!

## Testing Your Implementation

1. **Create a test ad** in `/admin/ads.php`
2. **Set dates** to include today
3. **Set status** to "active"
4. **Visit your page** and verify ad displays
5. **Check browser console** for any JavaScript errors
6. **Click the ad** and verify click tracking (check database)

## Common Mistakes

❌ **Wrong placement ID**
```php
displayAd('sidebar'); // Won't work - use full ID
```

✅ **Correct placement ID**
```php
displayAd('homepage_sidebar'); // Works!
```

❌ **Forgot to include header**
```php
// Will cause undefined function error
displayAd('homepage_top');
```

✅ **Include header first**
```php
include_once 'header.php';
displayAd('homepage_top');
```

## Need Help?

- Check `/ADVERTISEMENT_SYSTEM_GUIDE.md` for detailed documentation
- Review `index.php` for working examples
- Test in `/admin/ads.php` to create/manage ads

---

**Quick Tip:** Start with `homepage_top` placement - it's the most visible and easiest to test!
