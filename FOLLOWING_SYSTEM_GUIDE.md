# Following System Implementation Guide

## Overview

The Opinion Hub NG following system allows users to follow poll creators and categories. When users follow creators or categories, related polls from followed entities will be prioritized in the "Related Polls" section of the view-poll page.

## Database Schema

Run the following SQL to create the required tables:

```sql
-- User follows table (users following other users)
CREATE TABLE user_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX(follower_id),
    INDEX(following_id),
    INDEX(created_at)
);

-- User category follows table (users following categories)
CREATE TABLE user_category_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_follow (user_id, category_id),
    INDEX(user_id),
    INDEX(category_id),
    INDEX(created_at)
);
```

## Features

### User Following
- Click on creator names in poll view to follow/unfollow creators
- Follow button shows current follow status
- AJAX-powered for instant feedback

### Category Following
- Click the "Follow" button next to category badges to follow/unfollow categories
- Followed categories show "Following" with a star icon
- Separate button provides clear follow/unfollow action

### Related Polls Logic
Related polls now show **only polls from the same category** as the current poll:
- Shows polls that share the exact same category_id as the current poll
- Sorted by popularity (total responses) and recency (newest first)
- Excludes the current poll itself

### Simple Follow System with Auto-Setup
Following uses a straightforward approach with confirmation messages:
- **Simple Buttons**: Basic follow buttons without complex state management
- **Alert Confirmations**: Shows "You have followed this creator/category!" on success
- **Auto-Table Creation**: Database tables are created automatically if they don't exist
- **Database Storage**: Follow relationships stored reliably in database
- **Visual State Changes**: Buttons change to "Following" state after successful follow
- **Creator Profile Display**: Shows detailed creator information in poll view
- **Reliable**: No complex state synchronization issues

### Creator Profile Section
The poll view page now displays detailed creator information:
- **Profile Photo**: Creator's profile image or default avatar
- **Name Display**: First and last name prominently shown
- **Role Badge**: Shows creator's role (Admin, Client, Agent, User)
- **Follow Statistics**: Displays followers, following, and polls count
- **Follow Button**: Allows users to follow/unfollow the creator
- **Responsive Design**: Clean, professional layout above poll statistics

### Poll Reporting System
Users can report inappropriate polls with a comprehensive admin management system:
- **Report Button**: Available on every poll view page for logged-in users
- **Report Categories**: Spam, inappropriate content, harassment, copyright, other
- **Admin Dashboard**: Quick overview of pending reports
- **Full Management**: Dedicated page for reviewing all reports with filtering
- **Poll Actions**: Suspend, unsuspend, or delete reported polls
- **Status Tracking**: Reports can be pending, reviewed, or resolved
- **Visibility Control**: Suspended polls are hidden from regular users but visible to admins

*Note: Following categories affects individual user preferences but doesn't change related polls display - related polls are strictly category-based*

## Implementation Details

### Files Modified

1. **`add_following_system.sql`** - Database schema
2. **`actions.php`** - Added follow/unfollow API endpoints:
   - `follow_user` - Follow a user
   - `unfollow_user` - Unfollow a user
   - `follow_category` - Follow a category
   - `unfollow_category` - Unfollow a category

3. **`view-poll.php`** - Updated UI and logic:
   - Added follow status checking
   - Made creator names clickable
   - Made category badges clickable
   - Enhanced related polls query with prioritization
   - Added JavaScript for AJAX functionality

### API Endpoints

All endpoints return JSON responses:

```javascript
// Follow user
POST /actions.php?action=follow_user
Body: following_id=123

// Unfollow user
POST /actions.php?action=unfollow_user
Body: following_id=123

// Follow category
POST /actions.php?action=follow_category
Body: category_id=456

// Unfollow category
POST /actions.php?action=unfollow_category
Body: category_id=456
```

Response format:
```json
{
    "success": true|false,
    "message": "Success/error message"
}
```

## Usage

1. **For Users**:
   - Visit any poll page (e.g., `/view-poll/some-poll-slug`)
   - Click the "Follow" button next to creator names
   - Click on category badges to follow categories
   - View prioritized related polls in the sidebar

2. **For Developers**:
   - Run the SQL schema to create tables
   - No additional configuration needed
   - System works with existing authentication

## Security Features

- Users cannot follow themselves
- Login required for following actions
- SQL injection prevention with prepared statements
- CSRF protection via existing session management
- Input validation and sanitization

## Performance Considerations

- Indexed database tables for fast queries
- AJAX requests for better user experience
- Limited related polls to 5 for page speed
- Efficient follow status checking

## Future Enhancements

Potential additions:
- Follow notifications
- Follower counts display
- Follow activity feed
- Bulk follow/unfollow operations
- Follow recommendations
