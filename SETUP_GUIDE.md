# Opinion Hub NG - Complete Setup Guide

## Quick Start Guide

### Method 1: Automated Installation (Recommended)

1. **Access the installer**
   - Navigate to: `http://localhost/opinion/install.php`
   - Follow the on-screen instructions
   - The installer will:
     - Check system requirements
     - Create the database
     - Import tables
     - Create admin account
     - Configure the application

2. **Login**
   - Go to: `http://localhost/opinion/login.php`
   - Use the admin credentials you created
   - Start using the platform!

### Method 2: Manual Installation

#### Step 1: Database Setup

1. Open phpMyAdmin or MySQL command line
2. Create a new database:
   ```sql
   CREATE DATABASE opinionhub_ng CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the database schema:
   ```bash
   mysql -u root -p opinionhub_ng < database.sql
   ```
   Or use phpMyAdmin to import `database.sql`

#### Step 2: Configuration

1. Edit `connect.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'opinionhub_ng');
   define('SITE_URL', 'http://localhost/opinion/');
   ```

2. Create uploads directory:
   ```bash
   mkdir -p uploads/{polls,profiles,ads,blog}
   chmod -R 755 uploads
   ```

#### Step 3: Create Admin Account

1. Register a new account through the website
2. In MySQL, update the user role:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```

## Configuration Guide

### Payment Integration

#### Paystack Setup
1. Sign up at https://dashboard.paystack.com
2. Go to Settings > API Keys & Webhooks
3. Copy your keys and update `connect.php`:
   ```php
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxx');
   define('PAYSTACK_SECRET_KEY', 'sk_test_xxxx');
   ```

### Messaging Integration

#### Termii SMS Setup
1. Sign up at https://www.termii.com
2. Get your API key from dashboard
3. Update `connect.php`:
   ```php
   define('TERMII_API_KEY', 'your_api_key');
   define('TERMII_SENDER_ID', 'OpinionHub');
   ```

#### Brevo Email Setup
1. Sign up at https://www.brevo.com
2. Go to SMTP & API > API Keys
3. Create a new API key
4. Update `connect.php`:
   ```php
   define('BREVO_API_KEY', 'your_api_key');
   define('BREVO_FROM_EMAIL', 'noreply@opinionhub.ng');
   ```

## Testing the Application

### Test User Roles

1. **Admin Login**
   - Email: your_admin@email.com
   - Access all admin features

2. **Create Test Client**
   - Register new account
   - In database: `UPDATE users SET role = 'client' WHERE id = X;`

3. **Create Test Agent**
   - Register with "Become an Agent" option
   - Admin approves from dashboard

### Test Poll Creation

1. Login as Client
2. Go to Dashboard > Create New Poll
3. Fill in poll details:
   - Title: "Sample Political Poll"
   - Description: "Test poll description"
   - Category: Politics & Governance
   - Poll Type: Opinion Poll
4. Add questions (Multiple Choice, Ratings, etc.)
5. Publish poll

### Test Poll Participation

1. Logout or use incognito mode
2. Go to Browse Polls
3. Select the test poll
4. Submit responses
5. View results in Databank

## Common Issues & Solutions

### Issue 1: Database Connection Error
**Solution:**
- Check MySQL is running in XAMPP
- Verify credentials in `connect.php`
- Ensure database exists

### Issue 2: Blank Page
**Solution:**
- Enable error display in `connect.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check PHP error log

### Issue 3: Upload Errors
**Solution:**
- Check uploads directory exists and is writable:
  ```bash
  chmod -R 755 uploads
  ```

### Issue 4: Session Issues
**Solution:**
- Check PHP session directory is writable
- Clear browser cookies
- Restart Apache

## Security Checklist

Before going live:

- [ ] Change all default passwords
- [ ] Update Paystack to live keys
- [ ] Disable error display:
  ```php
  ini_set('display_errors', 0);
  ```
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure proper file permissions
- [ ] Enable PHP security extensions
- [ ] Set up regular backups
- [ ] Delete `install.php` file
- [ ] Update `.htaccess` for security headers

## Production Deployment

### Apache Configuration

Add to `.htaccess`:
```apache
# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# URL Rewriting
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove trailing slash
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "(^#.*#|\.(bak|conf|dist|fla|inc|ini|log|psd|sh|sql|sw[op])|~)$">
    Order allow,deny
    Deny from all
    Satisfy All
</FilesMatch>
```

### Database Optimization

```sql
-- Add indexes for better performance
ALTER TABLE polls ADD INDEX idx_status_created (status, created_at);
ALTER TABLE poll_responses ADD INDEX idx_poll_respondent (poll_id, respondent_id);
ALTER TABLE users ADD INDEX idx_role_status (role, status);
```

### Cron Jobs

Set up cron jobs for:

1. **Close Expired Polls** (Daily at midnight)
   ```bash
   0 0 * * * /usr/bin/php /path/to/opinion/cron/close-polls.php
   ```

2. **Send Email Notifications** (Every 15 minutes)
   ```bash
   */15 * * * * /usr/bin/php /path/to/opinion/cron/send-notifications.php
   ```

3. **Database Backup** (Daily at 2 AM)
   ```bash
   0 2 * * * /usr/bin/mysqldump -u root -p opinionhub_ng > /backups/db_$(date +\%Y\%m\%d).sql
   ```

## Performance Optimization

### Enable Caching

In `connect.php`:
```php
// Enable OpCache for PHP files
opcache_enable();
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Database Query Optimization

- Use prepared statements (already implemented)
- Add proper indexes on frequently queried columns
- Implement pagination (LIMIT/OFFSET)
- Cache frequently accessed data

### Image Optimization

- Compress uploaded images
- Use WebP format when possible
- Implement lazy loading
- Use CDN for static assets

## Monitoring & Maintenance

### Regular Tasks

- **Daily**: Check error logs
- **Weekly**: Review user registrations, Monitor poll activities
- **Monthly**: Database backup verification, Security updates check
- **Quarterly**: Performance audit, Feature usage analysis

### Log Files to Monitor

1. Apache error log: `/var/log/apache2/error.log`
2. PHP error log: Check `php.ini` for location
3. MySQL slow query log
4. Application logs (implement custom logging)

## Support & Resources

### Documentation
- API Documentation: `/docs/api.md`
- User Guide: `/docs/user-guide.md`
- Admin Manual: `/docs/admin-manual.md`

### Contact
- Email: hello@opinionhub.ng
- Phone: +234 (0) 803 3782 777
- Website: www.opinionhub.ng

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Core polling functionality
- Agent system
- Subscription plans
- Payment integration
- Messaging system
- Admin dashboard

## Next Steps

After installation:

1. ✅ Login to admin dashboard
2. ✅ Update system settings
3. ✅ Configure payment gateways
4. ✅ Set up messaging services
5. ✅ Create categories and subcategories
6. ✅ Test poll creation and participation
7. ✅ Invite test agents
8. ✅ Review subscription plans
9. ✅ Customize branding
10. ✅ Launch!

---

**Need Help?**

If you encounter any issues during setup, please contact our support team or refer to the troubleshooting section above.

Happy Polling! 🎉
