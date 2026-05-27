<?php
/**
 * HYREME Admin Dashboard
 * Glassmorphism analytics and moderation
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

$gate_user = get_option('hyreme_admin_gate_user');
$gate_pass = get_option('hyreme_admin_gate_pass');
if (empty($gate_user) || empty($gate_pass)) {
    $gate_user = 'AdminSite@123';
    $gate_pass = wp_hash_password('AdminSite@123');
    update_option('hyreme_admin_gate_user', $gate_user);
    update_option('hyreme_admin_gate_pass', $gate_pass);
}

$gate_error = '';
if (isset($_POST['hyreme_admin_gate_submit'])) {
    if (!isset($_POST['hyreme_admin_gate_nonce']) || !wp_verify_nonce($_POST['hyreme_admin_gate_nonce'], 'hyreme_admin_gate')) {
        $gate_error = 'Security check failed. Please try again.';
    } else {
        $input_user = sanitize_text_field($_POST['gate_user'] ?? '');
        $input_pass = $_POST['gate_pass'] ?? '';
        if ($input_user === $gate_user && wp_check_password($input_pass, $gate_pass)) {
            update_user_meta(get_current_user_id(), 'hyreme_admin_gate_until', time() + 43200);
            wp_safe_redirect(admin_url('admin.php?page=hyreme-dashboard'));
            exit;
        } else {
            $gate_error = 'Invalid admin credentials.';
        }
    }
}

$gate_until = intval(get_user_meta(get_current_user_id(), 'hyreme_admin_gate_until', true));
if ($gate_until < time()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HYREME Admin Access</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-slate-950 text-white flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-slate-900/80 border border-white/10 rounded-2xl p-8 shadow-2xl">
            <h1 class="text-2xl font-bold text-cyan-400 mb-2">Admin Access</h1>
            <p class="text-slate-400 mb-6">Enter the custom admin credentials to continue.</p>
            <?php if (!empty($gate_error)): ?>
                <div class="bg-red-500/20 border border-red-500/40 text-red-200 px-4 py-2 rounded mb-4 text-sm">
                    <?php echo esc_html($gate_error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <?php wp_nonce_field('hyreme_admin_gate', 'hyreme_admin_gate_nonce'); ?>
                <div>
                    <label class="text-sm text-slate-300">Admin Username</label>
                    <input type="text" name="gate_user" class="w-full mt-2 bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-cyan-500 outline-none" required>
                </div>
                <div>
                    <label class="text-sm text-slate-300">Admin Password</label>
                    <input type="password" name="gate_pass" class="w-full mt-2 bg-slate-800 border border-slate-700 rounded-lg p-3 text-white focus:ring-2 focus:ring-cyan-500 outline-none" required>
                </div>
                <button type="submit" name="hyreme_admin_gate_submit" class="w-full bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-semibold py-3 rounded-lg hover:scale-[1.01] transition">Unlock Admin Dashboard</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    return;
}

$args_candidates = array('role' => 'candidate', 'number' => -1);
$candidates_query = new WP_User_Query($args_candidates);
$total_candidates = $candidates_query->get_total();

$args_recruiters = array('role' => 'recruiter', 'number' => -1);
$recruiters_query = new WP_User_Query($args_recruiters);
$total_recruiters = $recruiters_query->get_total();

$all_users = get_users(array('number' => -1));
$total_videos = 0;
$videos = array();
$conversation_messages = 0;
$seen_conversations = array();

foreach ($all_users as $user) {
    $intro_video = get_user_meta($user->ID, 'hyreme_intro_video', true);
    $portfolio_video = get_user_meta($user->ID, 'hyreme_portfolio_video', true);
    $skill_video = get_user_meta($user->ID, 'hyreme_skill_video', true);
    if ($intro_video) $total_videos++;
    if ($portfolio_video) $total_videos++;
    if ($skill_video) $total_videos++;

    $video_types = array('intro' => 'Intro', 'portfolio' => 'Portfolio', 'skill' => 'Skill');
    foreach ($video_types as $type => $label) {
        $video_url = get_user_meta($user->ID, 'hyreme_' . $type . '_video', true);
        if ($video_url) {
            $videos[] = array(
                'user_id' => $user->ID,
                'user_name' => ($user->first_name . ' ' . $user->last_name) ?: $user->user_login,
                'user_email' => $user->user_email,
                'type' => $label,
                'url' => $video_url
            );
        }
    }

    $meta = get_user_meta($user->ID);
    foreach ($meta as $key => $values) {
        if (strpos($key, 'conv_') !== 0 || isset($seen_conversations[$key])) continue;
        $conv = get_user_meta($user->ID, $key, true);
        if (is_array($conv)) {
            $conversation_messages += count($conv);
            $seen_conversations[$key] = true;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYREME Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
        }
        @keyframes gradient-shift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .glass { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(16px); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8">
        <div class="glass rounded-2xl p-6 sm:p-8 shadow-2xl">
            <h1 class="text-2xl sm:text-3xl font-bold text-cyan-300">🎯 HYREME Admin Dashboard</h1>
            <p class="text-slate-300 mt-2">Platform overview and moderation controls</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="glass rounded-2xl p-6 border border-cyan-500/20">
                <p class="text-slate-300 text-sm uppercase tracking-wide">Total Candidates</p>
                <div class="text-3xl font-bold text-cyan-300 mt-2"><?php echo intval($total_candidates); ?></div>
            </div>
            <div class="glass rounded-2xl p-6 border border-amber-400/20">
                <p class="text-slate-300 text-sm uppercase tracking-wide">Total Recruiters</p>
                <div class="text-3xl font-bold text-amber-300 mt-2"><?php echo intval($total_recruiters); ?></div>
            </div>
            <div class="glass rounded-2xl p-6 border border-emerald-400/20">
                <p class="text-slate-300 text-sm uppercase tracking-wide">Total Videos Uploaded</p>
                <div class="text-3xl font-bold text-emerald-300 mt-2"><?php echo intval($total_videos); ?></div>
            </div>
            <div class="glass rounded-2xl p-6 border border-violet-400/20">
                <p class="text-slate-300 text-sm uppercase tracking-wide">Total Messages Sent</p>
                <div class="text-3xl font-bold text-violet-300 mt-2"><?php echo intval($conversation_messages); ?></div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <h2 class="text-xl font-semibold text-cyan-300">👥 User Management</h2>
                <input id="userSearch" type="text" placeholder="Search by email or name..." class="w-full sm:w-64 bg-slate-900/60 border border-slate-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-400/60">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-slate-300 uppercase tracking-wide">
                        <tr class="border-b border-slate-700/60">
                            <th class="py-3 pr-6">Avatar</th>
                            <th class="py-3 pr-6">Name</th>
                            <th class="py-3 pr-6">Role</th>
                            <th class="py-3 pr-6">Email</th>
                            <th class="py-3 pr-6">Join Date</th>
                            <th class="py-3 pr-6">Action</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <?php foreach ($all_users as $user): ?>
                            <?php
                                $role = in_array('recruiter', (array) $user->roles) ? 'Recruiter' : (in_array('candidate', (array) $user->roles) ? 'Candidate' : 'Admin');
                            ?>
                            <tr class="border-b border-slate-800/60 user-row">
                                <td class="py-3 pr-6">
                                    <div class="h-10 w-10 rounded-full overflow-hidden border border-cyan-500/40">
                                        <?php echo get_avatar($user->ID, 40); ?>
                                    </div>
                                </td>
                                <td class="py-3 pr-6">
                                    <div class="font-semibold text-white"><?php echo esc_html(($user->first_name . ' ' . $user->last_name) ?: $user->user_login); ?></div>
                                    <div class="text-slate-400 text-xs"><?php echo esc_html($user->user_login); ?></div>
                                </td>
                                <td class="py-3 pr-6">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $role === 'Recruiter' ? 'bg-amber-500/20 text-amber-200' : ($role === 'Candidate' ? 'bg-cyan-500/20 text-cyan-200' : 'bg-violet-500/20 text-violet-200'); ?>">
                                        <?php echo esc_html($role); ?>
                                    </span>
                                </td>
                                <td class="py-3 pr-6 text-slate-200"><?php echo esc_html($user->user_email); ?></td>
                                <td class="py-3 pr-6 text-slate-300"><?php echo esc_html(date('M d, Y', strtotime($user->user_registered))); ?></td>
                                <td class="py-3 pr-6">
                                    <?php if ($user->ID !== get_current_user_id()): ?>
                                        <button class="delete-user-btn bg-red-500/80 hover:bg-red-500 text-white text-xs font-semibold px-3 py-2 rounded-lg" data-user-id="<?php echo intval($user->ID); ?>">Delete User</button>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-xs">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 sm:p-8">
            <h2 class="text-xl font-semibold text-cyan-300 mb-6">🎬 Video Moderation</h2>
            <?php if (empty($videos)): ?>
                <div class="text-slate-300 text-center py-6">No uploaded videos available.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($videos as $video): ?>
                        <div class="bg-slate-900/60 border border-slate-700/60 rounded-2xl p-4 space-y-3">
                            <div class="text-sm text-slate-300">Type: <span class="text-cyan-300 font-semibold"><?php echo esc_html($video['type']); ?></span></div>
                            <div class="text-white font-semibold"><?php echo esc_html($video['user_name']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo esc_html($video['user_email']); ?></div>
                            <div class="flex items-center gap-2">
                                <button class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-semibold px-3 py-2 rounded-lg" onclick="window.open('<?php echo esc_url($video['url']); ?>', '_blank')">View Video</button>
                                <button class="delete-video-btn bg-red-500/80 hover:bg-red-500 text-white text-xs font-semibold px-3 py-2 rounded-lg" data-user-id="<?php echo intval($video['user_id']); ?>" data-video-type="<?php echo esc_attr(strtolower($video['type'])); ?>">Delete Video</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_admin_nonce = '<?php echo wp_create_nonce('hyreme_admin_nonce'); ?>';

        document.querySelectorAll('.delete-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const userId = btn.dataset.userId;
                if (!userId || !confirm('Delete this user? This action cannot be undone.')) return;
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'hyreme_admin_delete_user',
                        nonce: hyreme_admin_nonce,
                        user_id: userId
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        btn.closest('tr')?.remove();
                    } else {
                        alert('Error: ' + data.data);
                    }
                });
            });
        });

        document.querySelectorAll('.delete-video-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const userId = btn.dataset.userId;
                const videoType = btn.dataset.videoType;
                if (!userId || !videoType || !confirm('Delete this video?')) return;
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'hyreme_admin_delete_video',
                        nonce: hyreme_admin_nonce,
                        user_id: userId,
                        video_type: videoType
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        btn.closest('div')?.remove();
                    } else {
                        alert('Error: ' + data.data);
                    }
                });
            });
        });

        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('keyup', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.user-row').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
