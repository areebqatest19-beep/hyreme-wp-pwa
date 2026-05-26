# HYREME Phase 5 - Quick Reference Guide

## For Recruiters

### Messaging Features
1. **Send Messages**
   - Go to "💬 Messages" tab
   - Click on a candidate from the list
   - Type message in input field and press Enter or click Send
   - Messages appear in real-time

2. **Schedule Interviews** 
   - Open a chat with a candidate
   - Click "📅 Schedule" button in chat header
   - Select date and time
   - Click "✅ Schedule"
   - Candidate receives notification automatically

3. **Like/Save Candidates**
   - Click ❤️ button on candidate video
   - Button will animate and highlight in red
   - Candidate receives "Profile Liked" notification

4. **Unread Messages**
   - Red badge shows number of unread messages
   - Badge clears when you open the conversation

---

## For Candidates

### Profile Features
1. **Upload Resume**
   - Go to "👤 Profile" tab
   - Scroll to "📄 Resume Upload" section
   - Drag & drop PDF/DOC file or click to select
   - File must be under 10MB
   - Click "✅ Upload Resume"
   - View or delete anytime

2. **View Messages**
   - Go to "💬 Messages" tab
   - See list of recruiters who messaged you
   - Click on a recruiter to open conversation
   - All messages load automatically

3. **Check Notifications**
   - Go to "🔔 Notifications" tab
   - See all activity: likes, messages, interview schedules
   - Each notification shows type, message, and timestamp

4. **View Profile Analytics**
   - Go to "📊 Analytics" tab
   - See: Profile Views, Saved by Recruiters, Messages Received

---

## For Admins

### Admin Dashboard Access
1. Log in to WordPress Admin
2. Look for "HYREME" menu in left sidebar
3. Click to open Analytics & User Management

### Admin Functions

**Analytics Cards**
- Shows: Total Candidates, Recruiters, Videos, Users

**User Management**
- View all users with roles
- Search users by email or name
- Delete inappropriate users
- See join date and video count

**Video Management**
- View all videos on platform
- See which user uploaded each video
- Click "View" to preview video
- Click "Delete" to remove inappropriate videos

---

## Technical Details

### File Structure
```
hyreme-wp-pwa/
├── hyreme-core.php              (Main plugin file with AJAX handlers)
├── dashboards-recruiter.php     (Recruiter dashboard UI)
├── dashboards-candidate.php     (Candidate dashboard UI)
├── admin-dashboard.php          (Admin dashboard UI)
├── PHASE_5_IMPLEMENTATION.md    (Detailed documentation)
└── QUICK_REFERENCE.md           (This file)
```

### Database Storage (WordPress User Meta)
- Messages: `hyreme_conversation_{user1_id}_{user2_id}`
- Interviews: `hyreme_interview_scheduled`
- Notifications: `hyreme_notifications`
- Resume: `hyreme_resume`
- Videos: `hyreme_intro_video`, `hyreme_portfolio_video`, `hyreme_skill_video`

### AJAX Endpoints (All Secured with Nonces)
```
POST /wp-admin/admin-ajax.php?action=hyreme_send_message
POST /wp-admin/admin-ajax.php?action=hyreme_get_messages
POST /wp-admin/admin-ajax.php?action=hyreme_schedule_interview
POST /wp-admin/admin-ajax.php?action=hyreme_upload_resume
POST /wp-admin/admin-ajax.php?action=hyreme_delete_resume
POST /wp-admin/admin-ajax.php?action=hyreme_get_recruiters
POST /wp-admin/admin-ajax.php?action=hyreme_admin_delete_user (Admin only)
POST /wp-admin/admin-ajax.php?action=hyreme_admin_delete_video (Admin only)
```

### Key Features Summary

| Feature | Recruiter | Candidate | Admin |
|---------|-----------|-----------|-------|
| Send Messages | ✅ | ✅ | - |
| Schedule Interviews | ✅ | 👁️ | - |
| Upload Resume | - | ✅ | - |
| Receive Notifications | ✅ | ✅ | - |
| View Analytics | ✅ | ✅ | ✅ |
| Manage Users | - | - | ✅ |
| Delete Videos | - | - | ✅ |
| Like/Save Profiles | ✅ | - | - |

Legend: ✅ = Full Access, 👁️ = View Only, - = No Access

---

## Troubleshooting

### Messages Not Sending
1. Check browser console for errors (F12)
2. Ensure nonce is valid
3. Verify candidate exists and has proper role
4. Clear browser cache and refresh

### Resume Upload Fails
1. Check file is PDF or DOC
2. Ensure file is under 10MB
3. Verify `/wp-content/uploads/` directory has write permissions
4. Check browser console for specific error

### Notifications Not Appearing
1. Ensure action was performed (like, message, schedule)
2. Check user meta in database: `hyreme_notifications`
3. Refresh notifications tab
4. Clear browser cache

### Admin Dashboard Missing
1. Verify user has admin role
2. Check WordPress admin menu
3. Look for "HYREME" menu item
4. If missing, deactivate/reactivate plugin

---

## Security Notes

✅ **Protected Features:**
- All AJAX endpoints require WordPress nonce tokens
- Admin functions require `manage_options` capability
- File uploads validated by type and size
- All user input sanitized and escaped
- Messages stored securely in user meta
- Resume files stored outside web root initially

✅ **Best Practices Followed:**
- Least privilege principle
- Input validation
- Output escaping
- CSRF protection via nonces
- No direct SQL queries

---

## Performance Optimization

**Heartbeat Polling:** Every 3 seconds for active chats
- Only loads messages when chat is open
- Polling stops when user is idle
- Reduces server load compared to WebSockets

**Lazy Loading:** Candidates/Recruiters loaded on demand
- Video feed loads candidates on filter
- Messages load only when opening chat
- Notifications retrieved on demand

**Caching:**
- Conversation data cached in browser
- Unread count tracked in memory
- No redundant database queries

---

## Future Enhancements

Potential features for Phase 6:
- Real-time notifications via WebSocket
- Video call integration (Jitsi/Twilio)
- Email notifications
- Advanced search & filtering
- Profile verification badges
- Rating & review system
- Job postings module
- Resume parsing AI

---

**Last Updated:** 2026-05-26
**Version:** 1.0 (Complete)
**Support:** See PHASE_5_IMPLEMENTATION.md for full documentation
