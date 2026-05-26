<?php
/**
 * HYREME Recruiter Dashboard
 * Premium dark-mode, glassmorphism UI for recruiter feed, saved candidates, and messaging
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if user is logged in and is a recruiter
$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

// Verify user role
if ( ! in_array( 'recruiter', (array) $current_user->roles ) ) {
    wp_safe_redirect( home_url('/account/') );
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYREME - Recruiter Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: white;
            min-height: 100vh;
        }
        @keyframes gradient-shift { 
            0% { background-position: 0% 50%; } 
            50% { background-position: 100% 50%; } 
            100% { background-position: 0% 50%; } 
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 1.5rem;
            overflow-y: auto;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; font-weight: bold; color: #22d3ee; margin-bottom: 0.5rem; }
        .header p { color: #94a3b8; font-size: 0.95rem; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: 0.3s;
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 0.5rem;
            border: 1px solid transparent;
        }
        .nav-item:hover {
            background: rgba(30, 41, 59, 0.8);
            color: #22d3ee;
            border-color: rgba(34, 211, 238, 0.3);
        }
        .nav-item.active {
            background: rgba(34, 211, 238, 0.1);
            color: #22d3ee;
            border-color: #22d3ee;
        }
        .section { display: none; }
        .section.active { display: block; }
        .card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .card h2 { font-size: 1.5rem; color: #22d3ee; margin-bottom: 1.5rem; }
        .feed-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }
        .video-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            overflow: hidden;
            width: 300px;
            transition: 0.3s;
            cursor: pointer;
        }
        .video-card:hover {
            border-color: #22d3ee;
            box-shadow: 0 10px 30px rgba(34, 211, 238, 0.2);
        }
        .video-placeholder {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        .candidate-info {
            padding: 1rem;
        }
        .candidate-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #22d3ee;
            margin-bottom: 0.25rem;
        }
        .candidate-role {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }
        .button-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .btn-small {
            flex: 1;
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.3s;
            font-weight: 500;
        }
        .btn-save {
            background: rgba(34, 211, 238, 0.2);
            color: #22d3ee;
        }
        .btn-save:hover {
            background: rgba(34, 211, 238, 0.3);
        }
        .btn-message {
            background: rgba(16, 185, 129, 0.2);
            color: #86efac;
        }
        .btn-message:hover {
            background: rgba(16, 185, 129, 0.3);
        }
        .candidate-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .candidate-item {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.3s;
        }
        .candidate-item:hover {
            border-color: #22d3ee;
            background: rgba(30, 41, 59, 0.95);
        }
        .candidate-item-info {
            flex: 1;
        }
        .candidate-item-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #22d3ee;
            margin-bottom: 0.25rem;
        }
        .candidate-item-details {
            font-size: 0.9rem;
            color: #94a3b8;
        }
        .candidate-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.3s;
            font-weight: 500;
        }
        .btn-action-primary {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
        }
        .btn-action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6,182,212,0.3);
        }
        .btn-action-secondary {
            background: rgba(100, 116, 139, 0.2);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .btn-action-secondary:hover {
            background: rgba(100, 116, 139, 0.3);
        }
        .message-item {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .message-from {
            color: #22d3ee;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .message-text {
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        .message-time {
            font-size: 0.85rem;
            color: #64748b;
        }
        .placeholder-content {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        .placeholder-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(34, 211, 238, 0.1);
            border: 1px solid rgba(34, 211, 238, 0.3);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #22d3ee;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .logout-btn {
            text-align: center;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
            text-decoration: none;
            display: block;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        /* Mobile responsive */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                flex-direction: row;
                overflow-x: auto;
                overflow-y: visible;
                padding: 1rem;
                gap: 0.5rem;
            }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .nav-item { white-space: nowrap; flex: 0 0 auto; }
            .header h1 { font-size: 1.75rem; }
            .card { padding: 1.5rem; }
            .feed-container { flex-direction: column; }
            .video-card { width: 100%; }
            .candidate-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .candidate-item-actions {
                width: 100%;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR NAVIGATION -->
        <div class="sidebar">
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #22d3ee; font-size: 1.25rem; margin-bottom: 0.5rem;">HYREME</h3>
                <p style="color: #64748b; font-size: 0.85rem;">Recruiter Portal</p>
            </div>
            
            <nav onclick="switchSection(event)" style="flex: 1;">
                <div class="nav-item active" data-section="feed">
                    <span>🎬</span> Discover Feed
                </div>
                <div class="nav-item" data-section="saved">
                    <span>⭐</span> Saved Candidates
                </div>
                <div class="nav-item" data-section="messages">
                    <span>💬</span> Messages
                </div>
            </nav>

            <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-btn">🚪 Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="header">
                <h1>Discover Talent</h1>
                <p><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?> • Recruiter Account</p>
            </div>

            <!-- SECTION 1: FEED (MAIN DISCOVERY) -->
            <div id="feed" class="section active">
                <div class="card">
                    <h2>🎬 Video Feed - Discover Candidates</h2>
                    <p style="color: #94a3b8; margin-bottom: 2rem; font-size: 0.95rem;">Browse through candidate video resumes. Like, save, or message candidates to start the hiring conversation.</p>

                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">1.2K</div>
                            <div class="stat-label">Active Candidates</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">48</div>
                            <div class="stat-label">Saved This Week</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">12</div>
                            <div class="stat-label">Interview Scheduled</div>
                        </div>
                    </div>

                    <!-- Vertical Feed Preview -->
                    <div style="background: rgba(30, 41, 59, 0.8); border-radius: 1rem; padding: 2rem; margin-top: 2rem; text-align: center;">
                        <p style="color: #94a3b8; margin-bottom: 1.5rem;">👇 Vertical Reels-style Feed Coming Soon</p>
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <div class="video-card">
                                <div class="video-placeholder">🎥</div>
                                <div class="candidate-info">
                                    <div class="candidate-name">Sarah Chen</div>
                                    <div class="candidate-role">Full Stack Developer</div>
                                    <div class="button-group">
                                        <button class="btn-small btn-save">❤️ Save</button>
                                        <button class="btn-small btn-message">💬 Message</button>
                                    </div>
                                </div>
                            </div>
                            <div class="video-card">
                                <div class="video-placeholder">🎥</div>
                                <div class="candidate-info">
                                    <div class="candidate-name">James Rodriguez</div>
                                    <div class="candidate-role">UI/UX Designer</div>
                                    <div class="button-group">
                                        <button class="btn-small btn-save">❤️ Save</button>
                                        <button class="btn-small btn-message">💬 Message</button>
                                    </div>
                                </div>
                            </div>
                            <div class="video-card">
                                <div class="video-placeholder">🎥</div>
                                <div class="candidate-info">
                                    <div class="candidate-name">Emma Thompson</div>
                                    <div class="candidate-role">Product Manager</div>
                                    <div class="button-group">
                                        <button class="btn-small btn-save">❤️ Save</button>
                                        <button class="btn-small btn-message">💬 Message</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p style="color: #64748b; font-size: 0.9rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">🔜 Full vertical feed UI launching next phase</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: SAVED/SHORTLISTED CANDIDATES -->
            <div id="saved" class="section">
                <div class="card">
                    <h2>⭐ Saved & Shortlisted Candidates</h2>
                    <p style="color: #94a3b8; margin-bottom: 1.5rem; font-size: 0.95rem;">Candidates you've marked for follow-up. Organize and reach out to your top choices.</p>

                    <div class="candidate-list">
                        <div class="candidate-item">
                            <div class="candidate-item-info">
                                <div class="candidate-item-name">Sarah Chen</div>
                                <div class="candidate-item-details">Full Stack Developer • React, Node.js, Python • San Francisco, CA</div>
                            </div>
                            <div class="candidate-item-actions">
                                <button class="btn-action btn-action-primary">View Profile</button>
                                <button class="btn-action btn-action-secondary">Contact</button>
                            </div>
                        </div>

                        <div class="candidate-item">
                            <div class="candidate-item-info">
                                <div class="candidate-item-name">James Rodriguez</div>
                                <div class="candidate-item-details">UI/UX Designer • Figma, Prototyping, Design Systems • New York, NY</div>
                            </div>
                            <div class="candidate-item-actions">
                                <button class="btn-action btn-action-primary">View Profile</button>
                                <button class="btn-action btn-action-secondary">Contact</button>
                            </div>
                        </div>

                        <div class="candidate-item">
                            <div class="candidate-item-info">
                                <div class="candidate-item-name">Emma Thompson</div>
                                <div class="candidate-item-details">Product Manager • Strategy, Analytics, Leadership • Boston, MA</div>
                            </div>
                            <div class="candidate-item-actions">
                                <button class="btn-action btn-action-primary">View Profile</button>
                                <button class="btn-action btn-action-secondary">Contact</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: MESSAGING & INTERVIEW SCHEDULING -->
            <div id="messages" class="section">
                <div class="card">
                    <h2>💬 Messages & Interview Scheduling</h2>
                    <p style="color: #94a3b8; margin-bottom: 2rem; font-size: 0.95rem;">Communicate with candidates and schedule interviews.</p>

                    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
                        <!-- Conversations List -->
                        <div style="max-height: 500px; overflow-y: auto; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 1rem;">
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer;">
                                <div style="font-weight: 600; color: #22d3ee; margin-bottom: 0.25rem;">Sarah Chen</div>
                                <div style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Interested in the role...</div>
                            </div>
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer;">
                                <div style="font-weight: 600; color: #22d3ee; margin-bottom: 0.25rem;">James Rodriguez</div>
                                <div style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">When can we schedule a call?</div>
                            </div>
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer;">
                                <div style="font-weight: 600; color: #22d3ee; margin-bottom: 0.25rem;">Emma Thompson</div>
                                <div style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Thanks for reaching out!</div>
                            </div>
                        </div>

                        <!-- Message View -->
                        <div>
                            <div style="background: rgba(30, 41, 59, 0.8); border-radius: 0.75rem; padding: 1rem; max-height: 400px; overflow-y: auto; margin-bottom: 1rem;">
                                <div class="message-item">
                                    <div class="message-from">💬 Sarah Chen</div>
                                    <div class="message-text">Hi! Thanks for reaching out. I'm very interested in this opportunity.</div>
                                    <div class="message-time">Today at 10:30 AM</div>
                                </div>
                                <div class="message-item">
                                    <div class="message-from">📌 You</div>
                                    <div class="message-text">Great! I'd love to schedule a quick call this week. Do you have any availability?</div>
                                    <div class="message-time">Today at 11:15 AM</div>
                                </div>
                                <div class="message-item">
                                    <div class="message-from">💬 Sarah Chen</div>
                                    <div class="message-text">Tuesday or Wednesday afternoon works well for me!</div>
                                    <div class="message-time">Today at 11:45 AM</div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.75rem;">
                                <input type="text" placeholder="Type your message..." style="flex: 1; background: rgba(30, 41, 59, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: 0.3s;" onfocus="this.style.borderColor = '#06b6d4';" onblur="this.style.borderColor = 'rgba(255, 255, 255, 0.15)';">
                                <button style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; border: none; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform = 'translateY(-2px)';" onmouseout="this.style.transform = 'translateY(0)';">Send</button>
                            </div>

                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
                                <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">📅 Schedule Interview</h3>
                                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 1rem;">
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; color: #cbd5e1; font-weight: 500; margin-bottom: 0.5rem;">Preferred Date & Time</label>
                                        <input type="datetime-local" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.15); color: white; width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: 0.3s;">
                                    </div>
                                    <button style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; border: none; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s;">Send Interview Invite</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchSection(event) {
            const item = event.target.closest('.nav-item');
            if (!item) return;

            const sectionId = item.getAttribute('data-section');
            
            // Hide all sections
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            
            // Show selected section & highlight nav item
            document.getElementById(sectionId).classList.add('active');
            item.classList.add('active');
        }
    </script>
</body>
</html>
<?php exit; ?>
