<?php
/**
 * HYREME Recruiter Dashboard - Phase 4 (Finalized)
 * Reels-Style Video Feed, Smart Filters, Real-Time Messages, and Like Animations
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
    'number' => -1 
);

$candidates = new WP_User_Query($args);

if (!empty($candidates->get_results())) {
    foreach ($candidates->get_results() as $candidate) {
        $intro_video = get_user_meta($candidate->ID, 'hyreme_intro_video', true);
        if (!empty($intro_video)) {
            $candidate_data[] = array(
                'id' => $candidate->ID,
                'name' => ($candidate->first_name . ' ' . $candidate->last_name) ?: $candidate->user_login,
                'role' => get_user_meta($candidate->ID, 'hyreme_title', true) ?: 'Candidate',
                'location' => get_user_meta($candidate->ID, 'hyreme_location', true) ?: 'Unknown',
                'avatar' => '👤', 
                'video' => esc_url($intro_video),
                'skills' => get_user_meta($candidate->ID, 'hyreme_skills', true) ?: '',
                'saved' => in_array($candidate->ID, get_user_meta($current_user->ID, 'hyreme_saved_profiles', true) ?: array())
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
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); color: white; min-height: 100vh; font-family: sans-serif; }
        
        /* TIKTOK LIKE ANIMATION */
        @keyframes heartPop { 
            0% { transform: scale(1); } 
            50% { transform: scale(1.4); fill: #ef4444; color: #ef4444; } 
            100% { transform: scale(1); fill: #ef4444; color: #ef4444; } 
        }
        .liked-anim { animation: heartPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important; color: #ef4444 !important; border-color: #ef4444 !important; background: rgba(239, 68, 68, 0.2) !important; }

        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 2rem 1.5rem; position: fixed; height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: 0.75rem; cursor: pointer; color: #94a3b8; margin-bottom: 0.5rem; }
        .nav-item.active { background: rgba(34, 211, 238, 0.15); color: #22d3ee; }
        .section { display: none; }
        .section.active { display: block; }
        
        /* FEED */
        .reels-feed { height: calc(100vh - 120px); overflow-y: scroll; scroll-snap-type: y mandatory; }
        .video-reel { height: 100%; scroll-snap-align: start; display: flex; align-items: center; justify-content: center; }
        .video-container { position: relative; width: 100%; max-width: 500px; aspect-ratio: 9 / 16; background: #000; border-radius: 1.5rem; overflow: hidden; }
        .video-container video { width: 100%; height: 100%; object-fit: contain; }
        .video-actions { position: absolute; right: 1.5rem; bottom: 2rem; display: flex; flex-direction: column; gap: 1rem; }
        .action-btn { width: 50px; height: 50px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: 0.3s; }
        .action-btn:hover { background: rgba(34, 211, 238, 0.3); }

        /* MESSAGING */
        .message-bubble { padding: 0.85rem 1rem; border-radius: 1rem; margin-bottom: 0.5rem; max-width: 70%; }
        .message-bubble.sent { background: linear-gradient(135deg, #0ea5e9, #06b6d4); align-self: flex-end; }
        .message-bubble.received { background: rgba(34, 211, 238, 0.15); }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2 class="text-xl font-bold text-cyan-400 mb-8">HYREME</h2>
            <nav onclick="switchSection(event)">
                <div class="nav-item active" data-section="discover">🎬 Discover Feed</div>
                <div class="nav-item" data-section="saved">❤️ Saved</div>
                <div class="nav-item" data-section="messages">💬 Messages</div>
            </nav>
        </div>

        <div class="main-content">
            <div class="p-8">
                <div id="discover" class="section active">
                    <div class="flex gap-8">
                        <div class="w-64">
                            <h3 class="text-cyan-400 font-bold mb-4">Filters</h3>
                            <select id="skillFilter" class="w-full bg-slate-800 p-2 rounded mb-4 text-white">
                                <option value="">All Skills</option>
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                            </select>
                            <button onclick="applyFilters()" class="bg-cyan-500 w-full p-2 rounded text-white font-bold">Apply Filters</button>
                        </div>
                        <div id="reelsFeed" class="reels-feed w-full"></div>
                    </div>
                </div>

                <div id="messages" class="section">
                    <div class="flex h-[70vh] bg-slate-900 rounded-xl overflow-hidden border border-white/10">
                        <div id="chatMessages" class="flex-1 p-6 overflow-y-auto flex flex-col"></div>
                        <div id="chatInputArea" class="p-4 border-t border-white/10 flex gap-2" style="display:none;">
                            <input type="text" id="chatInput" class="flex-1 bg-slate-800 p-2 rounded text-white" placeholder="Type a message...">
                            <button onclick="sendMessage()" class="bg-cyan-500 px-4 rounded">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_nonce = '<?php echo wp_create_nonce('hyreme_nonce'); ?>';
        window.allCandidates = <?php echo json_encode($candidate_data); ?>;
        let activeCandidates = [...window.allCandidates];
        window.activeChatId = null;

        function renderReels() {
            const feed = document.getElementById('reelsFeed');
            feed.innerHTML = activeCandidates.map(c => `
                <div class="video-reel">
                    <div class="video-container">
                        <video loop muted playsinline preload="metadata"><source src="${c.video}" type="video/mp4"></video>
                        <div class="video-overlay p-6 pointer-events-none">
                            <div class="text-white"><h3>${c.name}</h3><p>${c.role}</p></div>
                            <div class="video-actions pointer-events-auto">
                                <button class="action-btn save-btn ${c.saved ? 'liked-anim' : ''}" data-id="${c.id}" onclick="toggleSave(${c.id})">❤️</button>
                                <button class="action-btn" onclick="jumpToMessages(${c.id}, '${c.name}')">💬</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            setupAutoPlay();
        }

        function toggleSave(id) {
            const btn = document.querySelector(`[data-id="${id}"]`);
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({ action: 'hyreme_save_candidate', nonce: hyreme_nonce, candidate_id: id })
            }).then(r => r.json()).then(d => {
                if(d.success && d.data.is_saved) {
                    btn.classList.add('liked-anim');
                    showToast("✅ Candidate Saved!");
                } else {
                    btn.classList.remove('liked-anim');
                }
            });
        }

        function applyFilters() {
            const skill = document.getElementById('skillFilter').value.toLowerCase();
            activeCandidates = window.allCandidates.filter(c => !skill || c.skills.toLowerCase().includes(skill));
            renderReels();
        }

        function jumpToMessages(id, name) {
            switchSection(null, 'messages');
            document.getElementById('chatHeaderTitle').textContent = "💬 Chat with " + name;
            window.activeChatId = id;
            loadMessages(id);
        }

        function loadMessages(otherId) {
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({ action: 'hyreme_get_messages', nonce: hyreme_nonce, other_id: otherId })
            }).then(r => r.json()).then(d => {
                document.getElementById('chatMessages').innerHTML = d.data.map(m => `
                    <div class="message-bubble ${m.from == <?php echo get_current_user_id(); ?> ? 'sent' : 'received'}">
                        ${m.text}
                    </div>
                `).join('');
                document.getElementById('chatInputArea').style.display = 'flex';
            });
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({ action: 'hyreme_send_message', nonce: hyreme_nonce, recipient_id: window.activeChatId, message: input.value })
            }).then(() => { input.value = ''; loadMessages(window.activeChatId); });
        }

        function showToast(msg) {
            const t = document.createElement('div');
            t.className = 'fixed bottom-5 right-5 bg-green-500 text-white p-4 rounded z-50';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2000);
        }

        function setupAutoPlay() {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    const v = e.target.querySelector('video');
                    if(v) e.isIntersecting ? v.play() : v.pause();
                });
            }, { threshold: 0.8 });
            document.querySelectorAll('.video-reel').forEach(r => obs.observe(r));
        }

        function switchSection(e, id) {
            if(e) {
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                e.target.closest('.nav-item').classList.add('active');
                document.getElementById(id || e.target.closest('.nav-item').dataset.section).classList.add('active');
            } else {
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.getElementById(id).classList.add('active');
            }
        }
        window.onload = renderReels;
    </script>
</body>
</html>