# 🎯 Opinion Hub NG - Quick Reference Card

## 🔗 Important URLs

| Page | URL | Access |
|------|-----|--------|
| **Homepage** | http://localhost/opinion/ | Public |
| **Blog** | http://localhost/opinion/blog.php | Public |
| **Sign In** | http://localhost/opinion/signin.php | Public |
| **Sign Up** | http://localhost/opinion/signup.php | Public |
| **Create Blog** | http://localhost/opinion/blog/create.php | Logged In |
| **My Posts** | http://localhost/opinion/blog/my-posts.php | Logged In |
| **Notifications** | http://localhost/opinion/notifications.php | Logged In |
| **Agent Dashboard** | http://localhost/opinion/dashboards/agent-dashboard.php | Agent |
| **Share Polls** | http://localhost/opinion/agent/share-poll.php | Agent |
| **Payouts** | http://localhost/opinion/agent/payouts.php | Agent |
| **Admin Blog** | http://localhost/opinion/admin/blog-approval.php | Admin |
| **Admin Agents** | http://localhost/opinion/admin/agents.php | Admin |
| **Admin Payouts** | http://localhost/opinion/admin/payouts.php | Admin |
| **Admin Settings** | http://localhost/opinion/admin/settings.php | Admin |

---

## 👤 User Roles

| Role | Can Do |
|------|--------|
| **Guest** | View blog, view polls |
| **User** | Take polls, create blog posts, like/comment |
| **Agent** | Share polls, earn commissions, request payouts |
| **Admin** | Approve blogs, approve agents, process payouts, change settings |

---

## 📝 Blog Post Status Flow

```
DRAFT → PENDING → APPROVED (visible on blog)
              └→ REJECTED (can edit and resubmit)
```

---

## 💰 Agent Commission System

| Event | Commission |
|-------|-----------|
| Poll response via agent's link | ₦1,000 |
| Minimum payout | ₦5,000 |
| Payment processing time | 5 working days |

---

## 📊 Key Database Tables

| Table | Primary Use |
|-------|-------------|
| `users` | All user accounts |
| `blog_posts` | Blog articles |
| `blog_comments` | Post comments + replies |
| `blog_likes` | Post likes |
| `blog_shares` | Social shares |
| `notifications` | User notifications |
| `poll_shares` | Agent tracking codes |
| `agent_payouts` | Payout requests |
| `site_settings` | Global configuration |

---

## 🔔 Notification Types

| Type | Triggered When |
|------|----------------|
| `blog_approved` | Admin approves your blog post |
| `blog_rejected` | Admin rejects your blog post |
| `new_comment` | Someone comments on your post |
| `payout_processed` | Admin approves your payout |
| `payout_rejected` | Admin rejects your payout |
| `agent_approved` | Admin approves agent application |
| `agent_rejected` | Admin rejects agent application |

---

## 🎨 TinyMCE Setup

1. **Get API Key:**
   - Visit: https://www.tiny.cloud/
   - Sign up (free)
   - Copy API key

2. **Configure:**
   - Login as admin
   - Go to Admin → Settings
   - Find "Editor API (TinyMCE)"
   - Paste API key
   - Save

3. **Test:**
   - Go to Blog → New Post
   - Rich editor should load
   - If not, check browser console

---

## 🛠️ Common Admin Tasks

### Approve a Blog Post
1. Admin → Blog Approval
2. Click "Pending Review"
3. Preview content (optional)
4. Click "Approve"
5. User gets notification ✅

### Approve an Agent
1. Admin → Agents
2. Click "Pending"
3. Review agent details
4. Click "Approve"
5. Agent gets notification ✅

### Process a Payout
1. Admin → Payouts
2. Click "Pending"
3. Verify amount
4. Click "Approve"
5. Agent gets notification ✅

### Change Settings
1. Admin → Settings
2. Find the setting
3. Edit value
4. Click "Save Settings"
5. Changes apply immediately ✅

---

## 📁 Important Directories

| Path | Purpose |
|------|---------|
| `/opinion/blog/` | Blog-related pages |
| `/opinion/agent/` | Agent-specific pages |
| `/opinion/admin/` | Admin-only pages |
| `/opinion/dashboards/` | User dashboards |
| `/opinion/uploads/blog/` | Blog featured images |

---

## 🔐 Default Settings

| Setting | Default Value |
|---------|---------------|
| Agent Commission | ₦1,000 per response |
| Minimum Payout | ₦5,000 |
| Payment Processing | 5 working days |
| Max Image Size | 5MB |
| Allowed Image Types | JPG, PNG, GIF |
| Posts Per Page | 9 |

---

## 🐛 Troubleshooting

### Blog Post Won't Submit
- Check title and content are filled
- Ensure TinyMCE loaded (see editor)
- Check browser console for errors
- Verify uploads/ folder has write permissions

### TinyMCE Not Loading
- Check API key in Admin → Settings
- Look for "no-api-key" in page source
- Clear browser cache
- Check internet connection (CDN)

### Notifications Not Showing
- Check notifications table exists
- Verify bell icon has badge counter
- Check browser console for errors
- Refresh the page

### Images Not Uploading
- Check uploads/blog/ folder exists
- Verify folder permissions (755)
- Check file size < 5MB
- Ensure file is JPG/PNG/GIF

### Agent Can't Share Polls
- Verify agent_status = 'approved'
- Check is_agent = 1 in users table
- Ensure polls exist
- Check poll status = 'active'

---

## 📞 Quick SQL Commands

### Check Blog Posts
```sql
SELECT id, title, status FROM blog_posts ORDER BY created_at DESC LIMIT 10;
```

### Check Notifications
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

### Check Agent Earnings
```sql
SELECT agent_id, COUNT(*) * 1000 as earnings 
FROM poll_responses 
WHERE tracking_code IS NOT NULL 
GROUP BY agent_id;
```

### Manually Approve Blog Post
```sql
UPDATE blog_posts SET status = 'approved' WHERE id = X;
```

### Reset TinyMCE Key
```sql
UPDATE site_settings SET setting_value = 'YOUR_KEY' WHERE setting_key = 'tinymce_api_key';
```

---

## ✅ Testing Checklist

- [ ] Create blog post as user
- [ ] Admin approve blog post
- [ ] User receives notification
- [ ] Post appears on blog.php
- [ ] Like post works
- [ ] Comment works
- [ ] Reply to comment works
- [ ] Share tracking works
- [ ] Register as agent
- [ ] Admin approve agent
- [ ] Agent shares poll
- [ ] Agent requests payout
- [ ] Admin processes payout
- [ ] All notifications received

---

## 🚀 Performance Tips

1. **Optimize Images:**
   - Compress before upload
   - Use max 1200px width
   - Keep under 500KB

2. **Database Maintenance:**
   - Regular backups
   - Index optimization
   - Clean old notifications

3. **Caching:**
   - Enable PHP OPcache
   - Cache database queries
   - Use CDN for assets

4. **Security:**
   - Keep software updated
   - Use strong passwords
   - Enable HTTPS
   - Validate all inputs

---

## 📈 Metrics to Monitor

- Total blog posts
- Pending approvals
- Active agents
- Total commissions paid
- Notification delivery rate
- User engagement (likes/comments)
- Poll response rate
- Agent conversion rate

---

## 🎓 Learning Resources

### PHP & MySQL
- PHP.net documentation
- MySQL reference manual
- W3Schools tutorials

### TinyMCE
- https://www.tiny.cloud/docs/
- https://www.tiny.cloud/blog/

### Bootstrap
- https://getbootstrap.com/docs/5.3/

### JavaScript
- MDN Web Docs
- JavaScript.info

---

**Keep this card handy for quick reference! 📌**

For complete documentation, see:
- README_COMPLETE.md (Full guide)
- BLOG_SYSTEM_COMPLETE.md (Blog system details)
