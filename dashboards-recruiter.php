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
                'avatar' => '👤', 
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
        @keyframes gradient-shift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* TIKTOK LIKE ANIMATION */
        @keyframes heartPop { 
            0% { transform: scale(1); } 
            50% { transform: scale(1.4); fill: #ef4444; color: #ef4444; } 
            100% { transform: scale(1); fill: #ef4444; color: #ef4444; } 
        }
        .liked-anim { 
            animation: heartPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important; 
            color: #ef4444 !important; 
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.2) !important;
        }

        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 2rem 1.5rem; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 50; }
        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header { padding: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); background: rgba(15, 23, 42, 0.6); }
        .header h1 { font-size: 2rem; font-weight: bold; color: #22d3ee; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .content-area { flex: 1; overflow-y: auto; padding: 2rem; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: 0.75rem; cursor: pointer; transition: all 0.3s; color: #94a3b8; font-weight: 500; margin-bottom: 0.5rem; border: 1px solid transparent; }
        .nav-item:hover { background: rgba(30, 41, 59, 0.8); color: #22d3ee; border-color: rgba(34, 211, 238, 0.3); }
        .nav-item.active { background: rgba(34, 211, 238, 0.15); color: #22d3ee; border-color: #22d3ee; box-shadow: 0 0 15px rgba(34, 211, 238, 0.2); }
        .section { display: none; }
        .section.active { display: block; animation: fadeIn 0.3s ease; }

        /* FEED SECTION */
        .feed-wrapper { display: flex; gap: 2rem; height: calc(100vh - 120px); }
        .filter-panel { width: 280px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.25rem; padding: 1.5rem; height: fit-content; position: sticky; top: 2rem; }
        .filter-panel h3 { color: #22d3ee; font-size: 1.1rem; margin-bottom: 1.5rem; font-weight: 600; }
        .filter-group { margin-bottom: 1.5rem; }
        .filter-label { color: #cbd5e1; font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem; display: block; }
        .filter-input, .filter-select { width: 100%; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.65rem 0.85rem; border-radius: 0.5rem; font-size: 0.9rem; transition: all 0.3s; outline: none; }
        .filter-input:focus, .filter-select:focus { border-color: #22d3ee; box-shadow: 0 0 10px rgba(34, 211, 238, 0.2); }
        .filter-select option { background: #0f172a; color: white; }
        .filter-btn { width: 100%; background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.3s; margin-top: 1rem; }
        .filter-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3); }

        .reels-feed { flex: 1; height: calc(100vh - 120px); overflow-y: scroll; scroll-snap-type: y mandatory; scroll-behavior: smooth; padding-right: 1rem; }
        .reels-feed::-webkit-scrollbar { width: 6px; }
        .reels-feed::-webkit-scrollbar-track { background: transparent; }
        .reels-feed::-webkit-scrollbar-thumb { background: rgba(34, 211, 238, 0.3); border-radius: 10px; }
        .video-reel { width: 100%; height: 100%; scroll-snap-align: start; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .video-container { position: relative; width: 100%; max-width: 500px; aspect-ratio: 9 / 16; background: #000; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8); }
        .video-container video { width: 100%; height: 100%; object-fit: contain; }
        .video-overlay { position: absolute; bottom: 0; left: 0; right: 0; top: 0; background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.3) 30%, transparent 100%); display: flex; flex-direction: column; justify-content: space-between; padding: 1.5rem; pointer-events: none; }
        .candidate-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem; }
        .candidate-avatar { width: 50px; height: 50px; border-radius: 50%; background: rgba(34, 211, 238, 0.2); border: 2px solid #22d3ee; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .candidate-header-info h3 { color: white; font-weight: 600; font-size: 1.1rem; margin: 0; }
        .candidate-header-info p { color: rgba(255, 255, 255, 0.8); font-size: 0.85rem; margin: 0; }
        .video-actions { display: flex; flex-direction: column; gap: 0.75rem; pointer-events: auto; align-self: flex-end; }
        .action-btn { width: 50px; height: 50px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: all 0.3s; background: rgba(255, 255, 255, 0.15); color: white; backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .action-btn:hover { background: rgba(34, 211, 238, 0.3); transform: scale(1.1); border-color: #22d3ee; }

        /* MESSAGES */
        .messages-wrapper { display: flex; gap: 2rem; height: calc(100vh - 120px); }
        .chat-list { width: 300px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; }
        .chat-search { padding: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .chat-search input { width: 100%; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.65rem 0.85rem; border-radius: 0.5rem; outline: none; }
        .chat-items { flex: 1; overflow-y: auto; }
        .chat-item { padding: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05); cursor: pointer; transition: all 0.3s; }
        .chat-item:hover { background: rgba(30, 41, 59, 0.6); }
        .chat-item.active { background: rgba(34, 211, 238, 0.1); border-left: 3px solid #22d3ee; padding-left: calc(1rem - 3px); }
        .chat-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .chat-item-name { font-weight: 600; color: #cbd5e1; }
        .chat-item-time { font-size: 0.75rem; color: #64748b; }
        .chat-item-preview { font-size: 0.85rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-window { flex: 1; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.25rem; display: flex; flex-direction: column; overflow: hidden; }
        .chat-header { padding: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; justify-content: space-between; align-items: center; }
        .chat-header h3 { color: #22d3ee; font-size: 1.2rem; margin: 0; }
        .chat-header-actions button { background: rgba(34, 211, 238, 0.15); color: #22d3ee; border: 1px solid rgba(34, 211, 238, 0.3); padding: 0.5rem 0.75rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
        .chat-input-area { padding: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; gap: 0.75rem; }
        .chat-input-field { flex: 1; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.85rem 1rem; border-radius: 0.75rem; outline: none; }
        .chat-send-btn { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; border: none; padding: 0.85rem 1.5rem; border-radius: 0.75rem; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div style="margin-bottom: 2rem;">
                <h2 style="color: #22d3ee; font-size: 1.5rem; margin: 0; font-weight: bold;">HYREME</h2>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0.5rem 0 0 0;">Recruiter</p>
            </div>
            <nav style="flex: 1;">
                <div class="nav-item active" data-section="discover" onclick="switchSection('discover')"><span>🎬</span> Discover Feed</div>
                <div class="nav-item" data-section="saved" onclick="switchSection('saved')"><span>❤️</span> Saved Candidates</div>
                <div class="nav-item" data-section="messages" onclick="switchSection('messages')"><span>💬</span> Messages</div>
                <div class="nav-item" data-section="profile" onclick="switchSection('profile')"><span>⚙️</span> Settings</div>
            </nav>
            <div style="padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="background: rgba(34, 211, 238, 0.1); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 1px solid rgba(34, 211, 238, 0.2);">
                    <p style="color: #cbd5e1; font-size: 0.85rem; margin: 0;">Welcome back,</p>
                    <p style="color: #22d3ee; font-weight: 600; margin: 0.25rem 0 0 0;"><?php echo esc_html($current_user->first_name ?: $current_user->user_login); ?></p>
                </div>
                <button onclick="location.href='<?php echo wp_logout_url(home_url('/login/')); ?>'" style="width: 100%; padding: 0.75rem; background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Logout</button>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Discover Talent</h1>
                <p>Find and connect with top candidates in real-time</p>
            </div>

            <div class="content-area">
                <div id="discover" class="section active">
                    <div class="feed-wrapper">
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
                                <label class="filter-label">Location</label>
                                <select class="filter-select" id="locationFilter">
                                    <option value="">Any Location</option>
                                    <option value="remote">Remote</option>
                                    <option value="mumbai">Mumbai</option>
                                    <option value="us">United States</option>
                                    <option value="uk">United Kingdom</option>
                                </select>
                            </div>
                            <button class="filter-btn" id="applyFiltersBtn" onclick="applyFilters()">Apply Filters</button>
                        </div>

                        <div class="reels-feed" id="reelsFeed"></div>
                    </div>
                </div>

                <div id="saved" class="section">
                    <div style="background: rgba(34, 211, 238, 0.1); padding: 2rem; border-radius: 1rem; border: 1px solid rgba(34,211,238,0.2); margin-bottom: 2rem;">
                        <h2 style="color: #22d3ee; font-size: 1.5rem; margin: 0;">❤️ Saved Candidates</h2>
                    </div>
                    <div id="savedList"></div>
                </div>

                <div id="messages" class="section">
                    <div class="messages-wrapper">
                        <div class="chat-list">
                            <div class="chat-search"><input type="text" placeholder="Search..."></div>
                            <div class="chat-items" id="chatItems"></div>
                        </div>
                        <div class="chat-window" id="chatWindow">
                            <div class="chat-header">
                                <h3 id="chatHeaderTitle">Select a conversation</h3>
                                <div class="chat-header-actions">
                                    <button onclick="openScheduleModal()" style="background: rgba(34, 211, 238, 0.15); color: #22d3ee; border: 1px solid rgba(34, 211, 238, 0.3); padding: 0.5rem 0.75rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.85rem;">📅 Schedule</button>
                                </div>
                            </div>
                            <div class="chat-messages" id="chatMessages"></div>
                            <div class="chat-input-area" id="chatInputArea" style="display: none;">
                                <input type="text" class="chat-input-field" placeholder="Type a message..." id="chatInput">
                                <button class="chat-send-btn">Send</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="profile" class="section">
                    <div style="background: rgba(30, 41, 59, 0.6); padding: 2rem; border-radius: 1rem;">
                        <h2 style="color: #22d3ee;">⚙️ Settings coming soon...</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCHEDULE INTERVIEW MODAL -->
    <div id="scheduleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 1rem; padding: 2rem; max-width: 400px; width: 90%;">
            <h2 style="color: #22d3ee; margin-bottom: 1.5rem;">📅 Schedule Interview</h2>
            <div style="margin-bottom: 1.5rem;">
                <label style="color: #cbd5e1; display: block; margin-bottom: 0.5rem; font-weight: 500;">Interview Date</label>
                <input type="date" id="interviewDate" style="width: 100%; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.75rem; border-radius: 0.5rem; outline: none;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="color: #cbd5e1; display: block; margin-bottom: 0.5rem; font-weight: 500;">Interview Time</label>
                <input type="time" id="interviewTime" style="width: 100%; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: white; padding: 0.75rem; border-radius: 0.5rem; outline: none;">
            </div>
            <div style="display: flex; gap: 1rem;">
                <button onclick="confirmSchedule()" style="flex: 1; background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">✅ Schedule</button>
                <button onclick="closeScheduleModal()" style="flex: 1; background: rgba(100, 116, 139, 0.2); color: #cbd5e1; padding: 0.75rem; border: 1px solid rgba(100, 116, 139, 0.3); border-radius: 0.5rem; cursor: pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_nonce = '<?php echo wp_create_nonce('hyreme_nonce'); ?>';
        
        // IMMUTABLE CANDIDATE DATA FOR FILTERING
        window.allCandidates = <?php echo json_encode($candidate_data); ?> || [];
        let activeCandidates = [...window.allCandidates];

        function renderReels() {
            const feed = document.getElementById('reelsFeed');
            feed.innerHTML = '';
            
            if (activeCandidates.length === 0) {
                feed.innerHTML = '<div style="color:#94a3b8; text-align:center; padding: 4rem;">No candidates match your filters.</div>';
                return;
            }

            activeCandidates.forEach(candidate => {
                const isSavedClass = candidate.saved ? 'liked-anim' : '';
                const reel = document.createElement('div');
                reel.className = 'video-reel';
                reel.innerHTML = `
                    <div class="video-container">
                        <video id="video-${candidate.id}" loop muted playsinline preload="metadata">
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
                                <button class="action-btn save-btn ${isSavedClass}" data-id="${candidate.id}" onclick="toggleSave(${candidate.id})" title="Save">❤️</button>
                                <button class="action-btn" onclick="jumpToMessages(${candidate.id}, '${candidate.name.replace(/'/g, "\\'")}')" title="Message">💬</button>
                                <button class="action-btn" title="View Resume">📄</button>
                            </div>
                        </div>
                    </div>
                `;
                feed.appendChild(reel);
            });
            setupAutoPlay();
        }

        // SHOW TOAST NOTIFICATION
        function showToast(msg) {
            const toast = document.createElement('div');
            toast.textContent = msg;
            toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 1rem 2rem; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 9999; transition: opacity 0.3s;';
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        function toggleSave(id) {
            const candidate = activeCandidates.find(c => c.id === id);
            if (!candidate) return;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'hyreme_save_candidate', nonce: hyreme_nonce, candidate_id: id })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    candidate.saved = data.data.is_saved;
                    const btn = document.querySelector(`[data-id="${id}"]`);
                    if (btn) {
                        if (candidate.saved) {
                            btn.classList.add('liked-anim');
                            btn.title = 'Saved';
                            showToast('✅ Candidate Saved!');
                        } else {
                            btn.classList.remove('liked-anim');
                            btn.style.color = 'white';
                            btn.title = 'Save';
                        }
                    }
                }
            });
        }

        // CHAT JUMP FIX
        function jumpToMessages(id, name) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            
            document.getElementById('messages').classList.add('active');
            document.querySelector('[data-section="messages"]').classList.add('active');
            
            document.getElementById('chatHeaderTitle').textContent = `💬 Chat with ${name}`;
            document.getElementById('chatInputArea').style.display = 'flex';
            document.getElementById('chatMessages').innerHTML = `<div style="text-align:center; color:#94a3b8; margin-top:2rem;">Start the conversation with ${name}</div>`;
            document.getElementById('chatInput').focus();
        }

        function applyFilters() {
            const btn = document.getElementById('applyFiltersBtn');
            const ogText = btn.textContent;
            btn.textContent = '⏳ Loading...';
            
            const skillFilter = document.getElementById('skillFilter').value.toLowerCase();
            const locationFilter = document.getElementById('locationFilter').value.toLowerCase();
            
            setTimeout(() => {
                let filtered = window.allCandidates.filter(c => {
                    let matchSkill = !skillFilter || (c.skills && c.skills.toLowerCase().includes(skillFilter));
                    let matchLoc = !locationFilter || (c.location && c.location.toLowerCase().includes(locationFilter));
                    return matchSkill && matchLoc;
                });
                
                activeCandidates = [...filtered];
                renderReels();
                btn.textContent = ogText;
            }, 600);
        }

        function switchSection(section) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(section).classList.add('active');
            event.target.closest('.nav-item').classList.add('active');
        }

        function setupAutoPlay() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target.querySelector('video');
                    if (!video) return;
                    if (entry.isIntersecting) { video.play().catch(()=>{}); } 
                    else { video.pause(); }
                });
            }, { threshold: 0.8 });

            document.querySelectorAll('.video-reel').forEach(reel => observer.observe(reel));
        }

        // ============================================================
        // MESSAGING SYSTEM
        // ============================================================
        
        let currentChatUserId = null;
        let messageRefreshInterval = null;
        let heartbeatInterval = null;
        const messageUnreadCounts = {}; // Track unread messages per user

        function loadConversations() {
            const chatItems = document.getElementById('chatItems');
            chatItems.innerHTML = '<div style="padding:1rem; color:#94a3b8; text-align:center;">Loading conversations...</div>';
            
            // For simplicity, show all candidates as potential conversations
            chatItems.innerHTML = '';
            window.allCandidates.forEach(candidate => {
                const item = document.createElement('div');
                item.className = 'chat-item';
                const unreadCount = messageUnreadCounts[candidate.id] || 0;
                const badge = unreadCount > 0 ? `<span style="background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">${unreadCount}</span>` : '';
                item.innerHTML = `<div class="chat-item-header"><div class="chat-item-name">${candidate.name}</div>${badge}</div><div class="chat-item-preview">Click to open</div>`;
                item.onclick = () => openChat(candidate.id, candidate.name);
                chatItems.appendChild(item);
            });
        }

        function openChat(userId, userName) {
            currentChatUserId = userId;
            messageUnreadCounts[userId] = 0; // Clear unread count
            
            document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
            event.target.closest('.chat-item').classList.add('active');
            
            document.getElementById('chatHeaderTitle').textContent = `💬 ${userName}`;
            document.getElementById('chatInputArea').style.display = 'flex';
            
            loadMessages();
            
            // Refresh messages every 2 seconds while chat is open
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(loadMessages, 2000);
        }

        function loadMessages() {
            if (!currentChatUserId) return;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    action: 'hyreme_get_messages', 
                    nonce: hyreme_nonce, 
                    other_user_id: currentChatUserId 
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    displayMessages(data.data.messages, data.data.current_user_id);
                }
            }).catch(err => console.error('Error loading messages:', err));
        }

        function displayMessages(messages, currentUserId) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = '';
            
            if (messages.length === 0) {
                chatMessages.innerHTML = '<div style="text-align:center; color:#94a3b8; margin-top:2rem;">Start the conversation</div>';
                return;
            }
            
            messages.forEach(msg => {
                const isOwn = msg.from == currentUserId;
                const bubble = document.createElement('div');
                bubble.style.cssText = `display:flex; justify-content:${isOwn ? 'flex-end' : 'flex-start'}; margin-bottom:0.75rem;`;
                bubble.innerHTML = `
                    <div style="max-width:70%; background:${isOwn ? 'rgba(6,182,212,0.3)' : 'rgba(30,41,59,0.8)'}; border:1px solid ${isOwn ? 'rgba(6,182,212,0.5)' : 'rgba(255,255,255,0.1)'}; padding:0.75rem 1rem; border-radius:0.75rem; color:white; word-wrap:break-word;">
                        <div>${msg.text}</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">${new Date(msg.time).toLocaleTimeString()}</div>
                    </div>
                `;
                chatMessages.appendChild(bubble);
            });
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function sendMessage() {
            if (!currentChatUserId) return;
            
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    action: 'hyreme_send_message', 
                    nonce: hyreme_nonce, 
                    recipient_id: currentChatUserId,
                    message: message
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages();
                } else {
                    alert('Failed to send message: ' + data.data);
                }
            }).catch(err => alert('Error sending message: ' + err));
        }

        // ============================================================
        // INTERVIEW SCHEDULER
        // ============================================================

        function openScheduleModal() {
            if (!currentChatUserId) {
                alert('Please select a candidate first');
                return;
            }
            document.getElementById('scheduleModal').style.display = 'flex';
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
            document.getElementById('interviewDate').value = '';
            document.getElementById('interviewTime').value = '';
        }

        function confirmSchedule() {
            const date = document.getElementById('interviewDate').value;
            const time = document.getElementById('interviewTime').value;
            
            if (!date || !time) {
                alert('Please select both date and time');
                return;
            }
            
            const scheduleData = {
                date: date,
                time: time,
                candidateId: currentChatUserId,
                recruiterId: '<?php echo get_current_user_id(); ?>',
                scheduledAt: new Date().toISOString()
            };
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    action: 'hyreme_schedule_interview', 
                    nonce: hyreme_nonce, 
                    candidate_id: currentChatUserId,
                    date: date,
                    time: time
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    alert('✅ Interview scheduled successfully!');
                    closeScheduleModal();
                    loadMessages();
                    // Send notification message
                    sendMessage('📅 I\'ve scheduled an interview for ' + date + ' at ' + time);
                } else {
                    alert('Error: ' + data.data);
                }
            }).catch(err => alert('Error scheduling: ' + err));
        }

        // Add event listeners for messaging
        document.addEventListener('DOMContentLoaded', () => {
            const chatSendBtn = document.querySelector('.chat-send-btn');
            const chatInput = document.getElementById('chatInput');
            
            if (chatSendBtn) {
                chatSendBtn.addEventListener('click', sendMessage);
            }
            
            if (chatInput) {
                chatInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
            
            // Load conversations when messages tab is opened
            document.querySelector('[data-section="messages"]')?.addEventListener('click', () => {
                setTimeout(loadConversations, 100);
            });
        });

        // Heartbeat polling for messages every 3 seconds
        function startHeartbeat() {
            if (heartbeatInterval) clearInterval(heartbeatInterval);
            heartbeatInterval = setInterval(() => {
                if (currentChatUserId) {
                    loadMessages();
                }
            }, 3000);
        }

        window.addEventListener('load', () => { 
            renderReels(); 
            loadConversations();
            startHeartbeat();
        });
    </script>
</body>
</html>
<?php exit; ?>