<?php
/**
 * HYREME Recruiter Dashboard - Phase 3
 * Reels-Style Video Feed, Smart Filters, and Messaging System
 * Premium dark-mode glassmorphism UI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
if ( ! $current_user->ID || ! in_array( 'recruiter', (array) $current_user->roles ) ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

// --- FETCH REAL CANDIDATE DATA FROM DATABASE ---
$candidate_data = array();
$args = array(
    'role' => 'candidate',
    'orderby' => 'user_registered',
    'order' => 'DESC',
    'number' => -1  // Get all candidates
);

$candidates = new WP_User_Query($args);

if (!empty($candidates->get_results())) {
    foreach ($candidates->get_results() as $candidate) {
        $intro_video = get_user_meta($candidate->ID, 'hyreme_intro_video', true);
        
        // Only include candidates who have uploaded at least one video
        if (!empty($intro_video)) {
            $candidate_data[] = array(
                'id' => $candidate->ID,
                'name' => $candidate->first_name . ' ' . $candidate->last_name ?: $candidate->user_login,
                'role' => get_user_meta($candidate->ID, 'hyreme_title', true) ?: 'Candidate',
                'location' => get_user_meta($candidate->ID, 'hyreme_location', true) ?: 'Unknown',
                'avatar' => '👤',  // Can be extended with profile photo
                'video' => esc_url($intro_video),
                'skills' => get_user_meta($candidate->ID, 'hyreme_skills', true) ?: '',
                'saved' => false
            );
        }
    }
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 1.5rem;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.6);
        }
        .header h1 { 
            font-size: 2rem; 
            font-weight: bold; 
            color: #22d3ee; 
            margin-bottom: 0.25rem; 
        }
        .header p { 
            color: #94a3b8; 
            font-size: 0.9rem; 
        }
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
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
            background: rgba(34, 211, 238, 0.15);
            color: #22d3ee;
            border-color: #22d3ee;
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.2);
        }
        .section { display: none; }
        .section.active { display: block; animation: fadeIn 0.3s ease; }

        /* FEED SECTION STYLES */
        .feed-wrapper {
            display: flex;
            gap: 2rem;
            height: calc(100vh - 120px);
        }
        .filter-panel {
            width: 280px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        .filter-panel h3 {
            color: #22d3ee;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .filter-group {
            margin-bottom: 1.5rem;
        }
        .filter-label {
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        .filter-input, .filter-select {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s;
            outline: none;
        }
        .filter-input:focus, .filter-select:focus {
            border-color: #22d3ee;
            box-shadow: 0 0 10px rgba(34, 211, 238, 0.2);
        }
        .filter-select option {
            background: #0f172a;
            color: white;
        }
        .filter-btn {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
        }

        /* REELS FEED STYLES */
        .reels-feed {
            flex: 1;
            height: calc(100vh - 120px);
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scroll-behavior: smooth;
            padding-right: 1rem;
        }
        .reels-feed::-webkit-scrollbar {
            width: 6px;
        }
        .reels-feed::-webkit-scrollbar-track {
            background: transparent;
        }
        .reels-feed::-webkit-scrollbar-thumb {
            background: rgba(34, 211, 238, 0.3);
            border-radius: 10px;
        }
        .reels-feed::-webkit-scrollbar-thumb:hover {
            background: rgba(34, 211, 238, 0.6);
        }
        .video-reel {
            width: 100%;
            height: 100%;
            scroll-snap-align: start;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .video-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            aspect-ratio: 9 / 16;
            background: #000;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
        }
        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .video-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            top: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.3) 30%, transparent 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5rem;
            pointer-events: none;
        }
        .candidate-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(34, 211, 238, 0.2);
            border: 2px solid #22d3ee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .candidate-header-info h3 {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }
        .candidate-header-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            margin: 0;
        }
        .video-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: auto;
            align-self: flex-end;
        }
        .action-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .action-btn:hover {
            background: rgba(34, 211, 238, 0.3);
            transform: scale(1.1);
            border-color: #22d3ee;
        }
        .action-btn.saved {
            background: rgba(239, 68, 68, 0.3);
            border-color: #ef4444;
        }

        /* MESSAGES SECTION STYLES */
        .messages-wrapper {
            display: flex;
            gap: 2rem;
            height: calc(100vh - 120px);
        }
        .chat-list {
            width: 300px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .chat-search {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .chat-search input {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            outline: none;
        }
        .chat-search input::placeholder {
            color: #64748b;
        }
        .chat-items {
            flex: 1;
            overflow-y: auto;
        }
        .chat-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
        }
        .chat-item:hover {
            background: rgba(30, 41, 59, 0.6);
        }
        .chat-item.active {
            background: rgba(34, 211, 238, 0.1);
            border-left: 3px solid #22d3ee;
            padding-left: calc(1rem - 3px);
        }
        .chat-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .chat-item-name {
            font-weight: 600;
            color: #cbd5e1;
        }
        .chat-item-time {
            font-size: 0.75rem;
            color: #64748b;
        }
        .chat-item-preview {
            font-size: 0.85rem;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* CHAT WINDOW */
        .chat-window {
            flex: 1;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h3 {
            color: #22d3ee;
            font-size: 1.2rem;
            margin: 0;
        }
        .chat-header-actions {
            display: flex;
            gap: 0.5rem;
        }
        .chat-header-btn {
            background: rgba(34, 211, 238, 0.15);
            color: #22d3ee;
            border: 1px solid rgba(34, 211, 238, 0.3);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .chat-header-btn:hover {
            background: rgba(34, 211, 238, 0.25);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message-bubble {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            animation: fadeIn 0.3s ease;
        }
        .message-bubble.sent {
            flex-direction: row-reverse;
        }
        .message-content {
            max-width: 70%;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            word-wrap: break-word;
        }
        .message-bubble.received .message-content {
            background: rgba(34, 211, 238, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(34, 211, 238, 0.2);
        }
        .message-bubble.sent .message-content {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
        }
        .message-time {
            font-size: 0.75rem;
            color: #64748b;
            align-self: flex-end;
            margin: 0 0.5rem;
        }
        .typing-indicator {
            display: flex;
            gap: 0.35rem;
            align-items: center;
            padding: 0.85rem 1rem;
            background: rgba(34, 211, 238, 0.1);
            border: 1px solid rgba(34, 211, 238, 0.2);
            border-radius: 1rem;
            width: fit-content;
        }
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22d3ee;
            animation: typing 1.4s infinite;
        }
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing {
            0%, 60%, 100% {
                opacity: 0.3;
                transform: translateY(0);
            }
            30% {
                opacity: 1;
                transform: translateY(-10px);
            }
        }
        .chat-input-area {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 0.75rem;
        }
        .chat-input-field {
            flex: 1;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
        }
        .chat-input-field:focus {
            border-color: #22d3ee;
            box-shadow: 0 0 10px rgba(34, 211, 238, 0.2);
        }
        .chat-input-field::placeholder {
            color: #64748b;
        }
        .chat-send-btn {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .chat-send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
        }

        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .filter-panel {
                width: 220px;
            }
            .feed-wrapper {
                gap: 1rem;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                padding: 1rem;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .main-content {
                margin-left: 0;
            }
            .feed-wrapper {
                flex-direction: column;
                height: auto;
            }
            .filter-panel {
                width: 100%;
                position: static;
            }
            .messages-wrapper {
                flex-direction: column;
                height: auto;
            }
            .chat-list {
                width: 100%;
            }
            .message-content {
                max-width: 100%;
            }
            .video-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR NAV -->
        <div class="sidebar">
            <div style="margin-bottom: 2rem;">
                <h2 style="color: #22d3ee; font-size: 1.5rem; margin: 0; font-weight: bold;">HYREME</h2>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0.5rem 0 0 0;">Recruiter</p>
            </div>
            <nav style="flex: 1;">
                <div class="nav-item active" onclick="switchSection('discover')">
                    <span>🎬</span>
                    <span>Discover Feed</span>
                </div>
                <div class="nav-item" onclick="switchSection('saved')">
                    <span>❤️</span>
                    <span>Saved Candidates</span>
                </div>
                <div class="nav-item" onclick="switchSection('messages')">
                    <span>💬</span>
                    <span>Messages</span>
                </div>
                <div class="nav-item" onclick="switchSection('profile')">
                    <span>⚙️</span>
                    <span>Settings</span>
                </div>
            </nav>
            <div style="padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="background: rgba(34, 211, 238, 0.1); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 1px solid rgba(34, 211, 238, 0.2);">
                    <p style="color: #cbd5e1; font-size: 0.85rem; margin: 0;">Welcome back,</p>
                    <p style="color: #22d3ee; font-weight: 600; margin: 0.25rem 0 0 0;"><?php echo esc_html($current_user->first_name ?: $current_user->user_login); ?></p>
                </div>
                <button onclick="location.href='<?php echo wp_logout_url(home_url('/login/')); ?>'" style="width: 100%; padding: 0.75rem; background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                    Logout
                </button>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- HEADER -->
            <div class="header">
                <h1>Discover Talent</h1>
                <p>Find and connect with top candidates in real-time</p>
            </div>

            <!-- CONTENT AREA -->
            <div class="content-area">

                <!-- DISCOVER FEED SECTION (MODULE 6 & 7) -->
                <div id="discover" class="section active">
                    <div class="feed-wrapper">
                        <!-- FILTER SIDEBAR (MODULE 7) -->
                        <div class="filter-panel">
                            <h3>📊 Filters</h3>
                            <div class="filter-group">
                                <label class="filter-label">Skills</label>
                                <select class="filter-select" id="skillFilter">
                                    <option value="">All Skills</option>
                                    <option value="javascript">JavaScript</option>
                                    <option value="python">Python</option>
                                    <option value="react">React</option>
                                    <option value="nodejs">Node.js</option>
                                    <option value="design">UI/UX Design</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Min. Experience</label>
                                <select class="filter-select" id="experienceFilter">
                                    <option value="">Any Level</option>
                                    <option value="0">0-1 years</option>
                                    <option value="1">1-3 years</option>
                                    <option value="3">3-5 years</option>
                                    <option value="5">5+ years</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Location</label>
                                <select class="filter-select" id="locationFilter">
                                    <option value="">Any Location</option>
                                    <option value="remote">Remote</option>
                                    <option value="us">United States</option>
                                    <option value="uk">United Kingdom</option>
                                    <option value="eu">Europe</option>
                                    <option value="asia">Asia</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Salary Range</label>
                                <input type="number" class="filter-input" id="salaryMin" placeholder="Min ($K)" min="0" max="500">
                                <input type="number" class="filter-input" id="salaryMax" placeholder="Max ($K)" min="0" max="500" style="margin-top: 0.5rem;">
                            </div>
                            <button class="filter-btn" id="applyFiltersBtn" onclick="applyFilters()">Apply Filters</button>
                        </div>

                        <!-- REELS FEED (MODULE 6) -->
                        <div class="reels-feed" id="reelsFeed">
                            <!-- VIDEO REELS WILL BE INSERTED HERE -->
                        </div>
                    </div>
                </div>

                <!-- SAVED CANDIDATES SECTION -->
                <div id="saved" class="section">
                    <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 rounded-xl p-8 border border-cyan-500/20 mb-8">
                        <h2 style="color: #22d3ee; font-size: 1.5rem; margin: 0;">❤️ Saved Candidates</h2>
                        <p style="color: #94a3b8; margin: 0.5rem 0 0 0;">Review candidates you've shortlisted</p>
                    </div>
                    <div id="savedList"></div>
                </div>

                <!-- MESSAGES SECTION (MODULE 8) -->
                <div id="messages" class="section">
                    <div class="messages-wrapper">
                        <!-- CHAT LIST -->
                        <div class="chat-list">
                            <div class="chat-search">
                                <input type="text" placeholder="Search conversations..." id="chatSearch">
                            </div>
                            <div class="chat-items" id="chatItems">
                                <!-- CHATS WILL BE INSERTED HERE -->
                            </div>
                        </div>

                        <!-- CHAT WINDOW -->
                        <div class="chat-window" id="chatWindow">
                            <div class="chat-header">
                                <h3>Select a conversation to start</h3>
                                <div class="chat-header-actions">
                                    <button class="chat-header-btn">📞 Call</button>
                                    <button class="chat-header-btn">📅 Schedule</button>
                                </div>
                            </div>
                            <div class="chat-messages" id="chatMessages"></div>
                            <div class="chat-input-area" id="chatInputArea" style="display: none;">
                                <input type="text" class="chat-input-field" placeholder="Type a message..." id="chatInput">
                                <button class="chat-send-btn" onclick="sendMessage()">Send</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROFILE SECTION -->
                <div id="profile" class="section">
                    <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 rounded-xl p-8 border border-cyan-500/20">
                        <h2 style="color: #22d3ee; font-size: 1.5rem; margin: 0;">⚙️ Settings</h2>
                        <p style="color: #94a3b8; margin: 0.5rem 0 0 0;">Manage your recruiter profile and preferences</p>
                        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(30, 41, 59, 0.6); border-radius: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <p style="color: #cbd5e1; margin: 0;">Profile settings coming soon...</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Security nonce for AJAX
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_nonce = '<?php echo wp_create_nonce('hyreme_nonce'); ?>';
        
        // REAL CANDIDATE DATA FROM DATABASE
        const candidateReels = <?php echo json_encode($candidate_data); ?>;

        // MOCK CHAT DATA
        const chats = [
            {
                id: 1,
                name: 'Sarah Chen',
                avatar: '👩‍💻',
                lastMessage: 'Thanks for the opportunity!',
                time: '2:30 PM',
                messages: [
                    { from: 'them', text: 'Hi, thanks for reaching out!', time: '2:15 PM' },
                    { from: 'me', text: 'Excited about your profile. Let\'s chat!', time: '2:20 PM' },
                    { from: 'them', text: 'Thanks for the opportunity!', time: '2:30 PM' }
                ]
            },
            {
                id: 2,
                name: 'Marcus Johnson',
                avatar: '👨‍🎨',
                lastMessage: 'When can we schedule a call?',
                time: '1:15 PM',
                messages: [
                    { from: 'me', text: 'Love your design portfolio!', time: '12:45 PM' },
                    { from: 'them', text: 'Thank you so much!', time: '1:00 PM' },
                    { from: 'them', text: 'When can we schedule a call?', time: '1:15 PM' }
                ]
            },
            {
                id: 3,
                name: 'Emma Rodriguez',
                avatar: '👩‍💼',
                lastMessage: 'Sounds interesting, tell me more',
                time: '12:30 PM',
                messages: [
                    { from: 'me', text: 'Great product sense. Interested?', time: '12:15 PM' },
                    { from: 'them', text: 'Sounds interesting, tell me more', time: '12:30 PM' }
                ]
            }
        ];

        let currentChat = null;

        // RENDER REELS FEED
        function renderReels() {
            const feed = document.getElementById('reelsFeed');
            feed.innerHTML = '';
            
            candidateReels.forEach(candidate => {
                const reel = document.createElement('div');
                reel.className = 'video-reel';
                reel.innerHTML = `
                    <div class="video-container">
                        <video id="video-${candidate.id}" loop muted playsinline preload="metadata" style="object-fit: cover;">
                            <source src="${candidate.video}" type="video/mp4">
                        </video>
                        <div class="video-overlay">
                            <div class="candidate-header">
                                <div class="candidate-avatar">${candidate.avatar}</div>
                                <div class="candidate-header-info">
                                    <h3>${candidate.name}</h3>
                                    <p>${candidate.role}</p>
                                </div>
                            </div>
                            <div class="video-actions">
                                <button class="action-btn save-btn" data-id="${candidate.id}" onclick="toggleSave(${candidate.id})" title="Save">❤️</button>
                                <button class="action-btn" onclick="openChat(${candidate.id})" title="Message">💬</button>
                                <button class="action-btn" title="View Resume">📄</button>
                                <button class="action-btn" title="Reject">❌</button>
                            </div>
                        </div>
                    </div>
                `;
                feed.appendChild(reel);
            });
            
            // Setup IntersectionObserver for auto-play/pause
            setupAutoPlay();
        }

        // TOGGLE SAVE CANDIDATE (AJAX)
        function toggleSave(id) {
            const candidate = candidateReels.find(c => c.id === id);
            if (!candidate) return;
            
            // Send AJAX request to save/unsave
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hyreme_save_candidate',
                    nonce: hyreme_nonce,
                    candidate_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    candidate.saved = data.data.is_saved;
                    
                    // Update button styling
                    const btn = document.querySelector(`[data-id="${id}"]`);
                    if (btn) {
                        if (candidate.saved) {
                            btn.style.color = '#ef4444';
                            btn.title = 'Saved';
                        } else {
                            btn.style.color = 'white';
                            btn.title = 'Save';
                        }
                    }
                    
                    renderSaved();
                } else {
                    console.error('Error saving candidate:', data.data);
                }
            })
            .catch(error => console.error('AJAX error:', error));
        }

        // RENDER SAVED CANDIDATES
        function renderSaved() {
            const saved = candidateReels.filter(c => c.saved);
            const list = document.getElementById('savedList');
            
            if (saved.length === 0) {
                list.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 2rem;">No saved candidates yet</div>';
                return;
            }
            
            list.innerHTML = saved.map(c => `
                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; color: #22d3ee; margin-bottom: 0.25rem;">${c.avatar} ${c.name}</div>
                        <div style="font-size: 0.9rem; color: #94a3b8;">${c.role}</div>
                        <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">📍 ${c.location}</div>
                    </div>
                    <button onclick="toggleSave(${c.id})" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">View Profile</button>
                </div>
            `).join('');
        }

        // RENDER CHAT LIST
        function renderChats() {
            const items = document.getElementById('chatItems');
            items.innerHTML = chats.map(chat => `
                <div class="chat-item${currentChat === chat.id ? ' active' : ''}" onclick="selectChat(${chat.id})">
                    <div class="chat-item-header">
                        <span class="chat-item-name">${chat.avatar} ${chat.name}</span>
                        <span class="chat-item-time">${chat.time}</span>
                    </div>
                    <div class="chat-item-preview">${chat.lastMessage}</div>
                </div>
            `).join('');
        }

        // SELECT CHAT
        function selectChat(chatId) {
            currentChat = chatId;
            const chat = chats.find(c => c.id === chatId);
            
            renderChats();
            
            const header = document.querySelector('.chat-header h3');
            header.textContent = `${chat.avatar} ${chat.name}`;
            
            const messages = document.getElementById('chatMessages');
            messages.innerHTML = chat.messages.map(msg => `
                <div class="message-bubble ${msg.from === 'me' ? 'sent' : 'received'}">
                    <div class="message-content">${msg.text}</div>
                    <div class="message-time">${msg.time}</div>
                </div>
            `).join('') + `
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            `;
            
            document.getElementById('chatInputArea').style.display = 'flex';
            messages.scrollTop = messages.scrollHeight;
        }

        // SEND MESSAGE
        function sendMessage() {
            const input = document.getElementById('chatInput');
            if (!input.value.trim()) return;
            
            const messages = document.getElementById('chatMessages');
            const msg = document.createElement('div');
            msg.className = 'message-bubble sent';
            msg.innerHTML = `
                <div class="message-content">${input.value}</div>
                <div class="message-time">now</div>
            `;
            messages.appendChild(msg);
            input.value = '';
            messages.scrollTop = messages.scrollHeight;
        }

        // SETUP AUTO-PLAY WITH INTERSECTION OBSERVER
        function setupAutoPlay() {
            const observerOptions = {
                threshold: 0.8
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target.querySelector('video');
                    if (!video) return;
                    
                    if (entry.isIntersecting) {
                        video.play().catch(() => {});
                    } else {
                        video.pause();
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.video-reel').forEach(reel => {
                observer.observe(reel);
            });
        }

        // APPLY FILTERS WITH REAL DATA FILTERING
        function applyFilters() {
            const btn = document.getElementById('applyFiltersBtn');
            const originalText = btn.textContent;
            
            // Show loading state
            btn.textContent = '⏳ Loading...';
            btn.disabled = true;
            
            // Get filter values
            const skillFilter = document.getElementById('skillFilter').value.toLowerCase();
            const experienceFilter = document.getElementById('experienceFilter').value;
            const locationFilter = document.getElementById('locationFilter').value.toLowerCase();
            
            // Simulate processing
            setTimeout(() => {
                // Create a copy of original candidates
                let filteredCandidates = [...candidateReels];
                
                // Apply skill filter
                if (skillFilter) {
                    filteredCandidates = filteredCandidates.filter(c => 
                        c.skills && c.skills.toLowerCase().includes(skillFilter)
                    );
                }
                
                // Apply location filter
                if (locationFilter) {
                    filteredCandidates = filteredCandidates.filter(c => 
                        c.location && c.location.toLowerCase().includes(locationFilter)
                    );
                }
                
                // Update the candidateReels with filtered results
                candidateReels.length = 0;
                candidateReels.push(...filteredCandidates);
                
                // Re-render the feed
                renderReels();
                
                // Reset button
                btn.textContent = originalText;
                btn.disabled = false;
            }, 800);
        }

        // SWITCH SECTIONS
        function switchSection(section) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(section).classList.add('active');
            event.target.closest('.nav-item').classList.add('active');
        }

        // INITIALIZE
        window.addEventListener('load', () => {
            renderReels();
            renderChats();
            
            document.getElementById('chatInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
        });
    </script>
</body>
</html>
