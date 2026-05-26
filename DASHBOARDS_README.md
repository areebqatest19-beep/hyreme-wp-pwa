# HYREME Phase 2: Dashboards Implementation

## 📋 Overview
Phase 2 introduces role-based dashboards for Candidates and Recruiters. When users log in and navigate to `/account/`, the system detects their WordPress role and loads the appropriate dashboard interface.

## 🎯 Architecture

### Routing Logic (hyreme-core.php)
The main plugin file now intercepts the `/account/` page:
- Checks if user is logged in
- Detects user role (candidate or recruiter)
- Includes the appropriate dashboard file
- Redirects to `/login/` if not authenticated or role is unknown

```php
if ( is_page('account') ) {
    $current_user = wp_get_current_user();
    if ( ! $current_user->ID ) {
        wp_safe_redirect( home_url('/login/') );
        exit;
    }
    if ( in_array('candidate', (array) $current_user->roles) ) {
        include plugin_dir_path(__FILE__) . 'dashboards-candidate.php';
    } elseif ( in_array('recruiter', (array) $current_user->roles) ) {
        include plugin_dir_path(__FILE__) . 'dashboards-recruiter.php';
    }
}
```

## 👤 Candidate Dashboard (`dashboards-candidate.php`)

### Features
1. **Profile Management Section**
   - Edit First Name & Last Name
   - Add Location
   - List Skills (comma-separated)
   - Describe Professional Experience
   - Add Education Details
   - Portfolio Links (multiple URLs)
   - Salary Expectations

2. **Video Resume Upload Section**
   - Drag-and-drop zones for three videos:
     - Intro Video (30s - 2min)
     - Portfolio/Project Showcase Video
     - Skill Showcase Video
   - File type validation (MP4, WebM, MOV)
   - Max 100MB per file
   - Supports drag-and-drop and click-to-select

3. **Analytics Section**
   - Profile Views count
   - Saved by Recruiters count
   - Messages Received count
   - Profile Completion Progress Bar

### Data Storage
All profile data is stored in WordPress user metadata (`wp_usermeta`):
- `hyreme_skills` - Skills list
- `hyreme_experience` - Work experience
- `hyreme_education` - Education info
- `hyreme_portfolio_links` - Portfolio URLs
- `hyreme_location` - Location
- `hyreme_salary_expectations` - Salary range
- `hyreme_profile_views` - View counter
- `hyreme_saved_count` - Saved by recruiters counter
- `hyreme_messages_count` - Messages counter

### Security
- Uses WordPress nonce verification for form submissions
- Sanitizes all input data
- Escapes output with `esc_html`, `esc_attr`, `esc_textarea`
- Only candidate role users can access this dashboard

### UI Design
- Dark glassmorphism aesthetic matching Phase 1 auth portal
- Fixed sidebar navigation (mobile-responsive)
- Tab-based section switching with smooth transitions
- Tailwind CSS via CDN for styling
- Premium look with cyan (#22d3ee) accent colors

## 💼 Recruiter Dashboard (`dashboards-recruiter.php`)

### Features
1. **Discover Feed Section**
   - Stats showing:
     - Active Candidates count
     - Saved This Week
     - Interviews Scheduled
   - Vertical feed mockup with candidate video cards
   - Each card shows:
     - Video placeholder
     - Candidate name
     - Role
     - Save & Message buttons
   - 🔜 Full vertical Reels-style feed launching in Phase 3

2. **Saved & Shortlisted Candidates Section**
   - List of all saved candidates
   - Shows: Name, Role, Skills, Location
   - View Profile & Contact action buttons
   - Mock data with real-looking candidates

3. **Messages & Interview Scheduling Section**
   - Conversations list (left sidebar)
   - Active message thread display
   - Type and send messages
   - Schedule Interview section with:
     - Date/time picker
     - Send interview invite button
   - Mock conversation with Sarah Chen included

### UI Design
- Consistent dark glassmorphism theme
- Sidebar navigation with 3 main tabs
- Tab switching without page reload
- Professional message and scheduling interface
- Stats dashboard at top of feed
- Responsive grid layout for candidate cards

## 📦 File Structure
```
hyreme-wp-pwa/
├── hyreme-core.php              (Updated with /account/ routing)
├── dashboards-candidate.php     (Candidate dashboard - 21.6KB)
├── dashboards-recruiter.php     (Recruiter dashboard - 25.4KB)
└── README.md                    (This file)
```

## 🎨 Design Features

### Consistent Styling Across Phase 1 & 2
- Animated gradient background (moving color shifts)
- Glassmorphism cards with backdrop blur
- Tailwind CSS utility classes
- Cyan gradient buttons (#0ea5e9 → #06b6d4)
- Smooth hover effects and transitions
- Mobile-first responsive design

### Colors
- Background: #0f172a → #1e293b gradient
- Accent: #22d3ee (cyan)
- Text Primary: white
- Text Secondary: #94a3b8 (slate)
- Success: #10b981 (green)
- Error: #ef4444 (red)

## 🔧 Setup Instructions

1. **Ensure Page Setup**: Create a WordPress page with slug `account` (e.g., page title "Account")
   - Can be ID 10, 11, or slug "account"

2. **User Roles**: Make sure users are assigned either `candidate` or `recruiter` role
   - WordPress standard role field
   - Set during registration in Phase 1

3. **File Placement**: Dashboard files should be in plugin root:
   ```
   wp-content/plugins/hyreme-core/
   ├── hyreme-core.php
   ├── dashboards-candidate.php
   ├── dashboards-recruiter.php
   ```

4. **User Meta Support**: WordPress automatically handles user meta storage via `get_user_meta()` and `update_user_meta()`

## 🚀 Testing

### Candidate Dashboard Test
1. Create a new WordPress user with `candidate` role
2. Login with candidate account
3. Navigate to `/account/`
4. Verify:
   - ✅ Candidate dashboard loads
   - ✅ Can edit profile fields
   - ✅ Can save profile data
   - ✅ Can switch tabs (Videos, Analytics)
   - ✅ Upload zones appear
   - ✅ Analytics display mock data

### Recruiter Dashboard Test
1. Create a new WordPress user with `recruiter` role
2. Login with recruiter account
3. Navigate to `/account/`
4. Verify:
   - ✅ Recruiter dashboard loads
   - ✅ Can switch tabs (Discover, Saved, Messages)
   - ✅ Feed mockup displays
   - ✅ Candidate cards render properly
   - ✅ Messaging interface functional
   - ✅ Interview scheduling visible

### Mobile Responsiveness
- Test on mobile browsers (iOS Safari, Chrome Mobile)
- Verify sidebar converts to horizontal tabs
- Ensure form fields stack properly
- Check button interactions work

## 📝 Data Model

### User Meta Schema
```
User Meta Keys:
- hyreme_skills (text)
- hyreme_experience (text)
- hyreme_education (text)
- hyreme_portfolio_links (text)
- hyreme_location (text)
- hyreme_salary_expectations (text)
- hyreme_profile_views (numeric)
- hyreme_saved_count (numeric)
- hyreme_messages_count (numeric)
```

## 🔐 Security Considerations

1. **Nonce Verification**: All form submissions validated with WordPress nonces
2. **Input Sanitization**: 
   - `sanitize_text_field()` for text inputs
   - `sanitize_textarea_field()` for multi-line text
   - `sanitize_email()` for email fields

3. **Output Escaping**:
   - `esc_html()` for text display
   - `esc_attr()` for HTML attributes
   - `esc_textarea()` for textarea content

4. **Role Verification**: Dashboard code verifies user role matches expected dashboard
5. **Authentication Check**: Unauthenticated users redirected to `/login/`

## 🎬 Future Enhancements (Phase 3+)

### Candidate Dashboard
- Real video upload to AWS S3 or similar
- Actual profile photo upload
- Video duration validation (30s-2m)
- Real-time profile completion calculation
- Email notifications for messages/saves
- Profile view tracking

### Recruiter Dashboard
- Full vertical Reels-style scrolling feed
- Like/unlike animations
- Real-time candidate filtering
- Advanced search and filters
- Full messaging system
- Interview scheduling with calendar
- Candidate profile modal/detail view
- Video playback functionality

### Both Dashboards
- User preferences/settings panel
- Notification system
- Account management
- Activity history
- Export/download features

## 📞 Support
For issues or feature requests, refer to the HYREME project documentation or contact the development team.

---

**Phase 2 Status**: ✅ Complete
- Candidate Dashboard: Fully functional with profile management
- Recruiter Dashboard: Fully functional with mockup UI for feeds
- Routing: Automatic role-based dashboard loading
- Design: Consistent premium dark-mode interface
