# 🚀 Production Deployment Checklist

## Pre-Deployment Tasks

### 1. Code Review ✅
- [x] All files uploaded to server
- [x] No debug code or console.log statements
- [x] Error reporting configured for production
- [x] All file permissions set correctly (755 for folders, 644 for files)

### 2. Database Setup
- [ ] Database created on production server
- [ ] All tables imported successfully
- [ ] Database credentials updated in connect.php
- [ ] Database user has appropriate permissions
- [ ] Verify no test data in production database

### 3. Configuration Files

#### connect.php
- [ ] Update SITE_URL to production domain
```php
define('SITE_URL', 'https://yourdomain.com/');
```
- [ ] Update database credentials
```php
define('DB_HOST', 'production_host');
define('DB_USER', 'production_user');
define('DB_PASS', 'strong_password');
define('DB_NAME', 'production_db_name');
```
- [ ] Disable error display
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 4. API Configuration

#### Brevo (Email API) - REQUIRED for Email Sharing
- [ ] Create production Brevo account
- [ ] Verify sender email address
- [ ] Generate production API key
- [ ] Add to database:
```sql
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('brevo_api_key', 'production-key-here')
ON DUPLICATE KEY UPDATE setting_value = 'production-key-here';
```
- [ ] Test email sending
- [ ] Monitor daily limit (300 free, upgrade if needed)

#### Termii (SMS API) - REQUIRED for SMS Sharing
- [ ] Create production Termii account
- [ ] Complete KYC verification
- [ ] Fund wallet (recommended ₦10,000 minimum)
- [ ] Generate production API key
- [ ] Add to database:
```sql
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('termii_api_key', 'production-key-here')
ON DUPLICATE KEY UPDATE setting_value = 'production-key-here';
```
- [ ] Test SMS sending
- [ ] Set up auto-recharge alerts

#### Paystack (Payment) - RECOMMENDED for SMS Credits
- [ ] Create production Paystack account
- [ ] Complete verification
- [ ] Get live API keys
- [ ] Add to database:
```sql
INSERT INTO site_settings (setting_key, setting_value) 
VALUES ('paystack_public_key', 'pk_live_xxx'),
       ('paystack_secret_key', 'sk_live_xxx');
```
- [ ] Update buy-sms-credits.php to use Paystack
- [ ] Test payment flow
- [ ] Set up webhook for payment verification

### 5. Security Hardening

#### File Permissions
```bash
# Set proper permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Protect sensitive files
chmod 640 connect.php
chmod 640 functions.php

# Make uploads writable
chmod 755 assets/images/
```

#### .htaccess Security
- [ ] Create .htaccess file in root:
```apache
# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "(connect\.php|functions\.php|database\.sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Force HTTPS (if SSL installed)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Database Security
- [ ] Change default admin password
```sql
UPDATE users SET password = MD5('new_strong_password_here') 
WHERE email = 'admin@opinionhub.ng';
```
- [ ] Remove test accounts
```sql
DELETE FROM users WHERE email LIKE '%@test.com';
```
- [ ] Review and delete test data
```sql
DELETE FROM agent_sms_credits WHERE description LIKE '%test%';
DELETE FROM poll_shares WHERE created_at < '2024-01-01';
```

### 6. Performance Optimization

#### PHP Configuration
- [ ] Increase upload_max_filesize (for blog images)
- [ ] Increase post_max_size
- [ ] Enable OPcache
- [ ] Optimize session handling

#### Database Optimization
- [ ] Add indexes to frequently queried columns
```sql
-- Add indexes for better performance
ALTER TABLE poll_shares ADD INDEX idx_agent_poll (agent_id, poll_id);
ALTER TABLE blog_posts ADD INDEX idx_status_created (status, created_at);
ALTER TABLE agent_sms_credits ADD INDEX idx_agent_type (agent_id, transaction_type);
```
- [ ] Optimize slow queries
- [ ] Enable query caching

#### Assets
- [ ] Minify CSS files
- [ ] Minify JavaScript files
- [ ] Optimize images
- [ ] Enable gzip compression

### 7. Backup Strategy

#### Database Backups
- [ ] Set up automated daily backups
- [ ] Test backup restoration
- [ ] Store backups off-site

#### File Backups
- [ ] Backup uploaded images
- [ ] Backup configuration files
- [ ] Version control integration

### 8. Monitoring & Logging

#### Error Logging
- [ ] Configure error logging to file
```php
// In connect.php
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/logs/php-error.log');
```
- [ ] Set up log rotation
- [ ] Monitor error logs daily

#### API Monitoring
- [ ] Monitor Brevo API usage
- [ ] Monitor Termii balance and usage
- [ ] Set up low balance alerts
- [ ] Track API failure rates

#### Analytics
- [ ] Install Google Analytics (optional)
- [ ] Track key metrics:
  - New user registrations
  - Poll shares sent
  - SMS credits purchased
  - Blog posts published
  - Agent commissions earned

### 9. Testing on Production

#### Critical Path Testing
- [ ] User registration works
- [ ] Login/logout works
- [ ] Poll creation works
- [ ] Blog post creation works
- [ ] Email sharing works (send test)
- [ ] SMS sharing works (send test)
- [ ] WhatsApp link generation works
- [ ] SMS credits purchase works
- [ ] Admin approval works
- [ ] Payout processing works

#### Cross-Browser Testing
- [ ] Chrome (desktop)
- [ ] Firefox (desktop)
- [ ] Safari (desktop)
- [ ] Edge (desktop)
- [ ] Chrome (mobile)
- [ ] Safari (mobile)

#### Security Testing
- [ ] SQL injection attempts blocked
- [ ] XSS attempts blocked
- [ ] CSRF protection working
- [ ] Unauthorized access blocked
- [ ] File upload security working

### 10. Documentation

#### Admin Documentation
- [ ] Create admin user guide
- [ ] Document common tasks
- [ ] Document API configuration
- [ ] Document troubleshooting steps

#### User Documentation
- [ ] Create user guide
- [ ] Create FAQ page
- [ ] Create video tutorials (optional)

### 11. Legal & Compliance

#### Terms & Policies
- [ ] Terms of Service uploaded
- [ ] Privacy Policy uploaded
- [ ] Refund Policy for SMS credits
- [ ] Data Protection Policy (GDPR/NDPR compliant)

#### Contact Information
- [ ] Update support email
- [ ] Update support phone
- [ ] Update company address
- [ ] Update social media links

### 12. Go-Live Checklist

#### Final Checks
- [ ] All features tested and working
- [ ] All APIs configured and tested
- [ ] All admin accounts created
- [ ] All test data removed
- [ ] All documentation updated
- [ ] Backup systems operational
- [ ] Monitoring systems active

#### Domain & Hosting
- [ ] Domain registered
- [ ] DNS configured correctly
- [ ] SSL certificate installed
- [ ] Email forwarding configured
- [ ] CDN configured (optional)

#### Launch
- [ ] Notify stakeholders
- [ ] Monitor for first 24 hours
- [ ] Have rollback plan ready
- [ ] Support team briefed

## Post-Launch Tasks

### Day 1
- [ ] Monitor error logs
- [ ] Check API usage
- [ ] Verify email deliverability
- [ ] Test SMS sending
- [ ] Monitor server resources

### Week 1
- [ ] Review user feedback
- [ ] Fix any critical bugs
- [ ] Monitor performance metrics
- [ ] Check backup integrity
- [ ] Review security logs

### Month 1
- [ ] Analyze usage patterns
- [ ] Review API costs
- [ ] Optimize based on data
- [ ] Plan feature updates
- [ ] Review and update documentation

## Maintenance Schedule

### Daily
- Check error logs
- Monitor API usage
- Check critical functionality

### Weekly
- Review new user registrations
- Check SMS credits usage
- Review blog posts needing approval
- Process pending payouts

### Monthly
- Review and update security patches
- Optimize database
- Review and renew API subscriptions
- Backup verification
- Performance optimization

### Quarterly
- Security audit
- Code review
- Feature planning
- User feedback analysis

## Emergency Contacts

### API Support
- **Brevo**: support@brevo.com
- **Termii**: support@termii.com
- **Paystack**: support@paystack.com

### Hosting Support
- Provider: ________________
- Support: ________________
- Emergency: ________________

### Development Team
- Lead Developer: ________________
- Backend: ________________
- Frontend: ________________

## Rollback Plan

If critical issues occur:

1. **Immediate Actions**
   - [ ] Notify users of maintenance
   - [ ] Take site offline if necessary
   - [ ] Identify the issue

2. **Rollback Steps**
   - [ ] Restore previous database backup
   - [ ] Restore previous code version
   - [ ] Verify functionality
   - [ ] Bring site back online

3. **Post-Rollback**
   - [ ] Document the issue
   - [ ] Fix in development environment
   - [ ] Test thoroughly
   - [ ] Plan re-deployment

## Success Criteria

System is ready for production when:
- ✅ All checklist items completed
- ✅ All tests passing
- ✅ APIs configured and working
- ✅ Backups operational
- ✅ Documentation complete
- ✅ Team trained
- ✅ Stakeholders approve

---

**Remember:** Test everything twice, deploy once! 🚀

*Last Updated: [Current Date]*
*Prepared by: Development Team*
*Version: 2.0.0*
