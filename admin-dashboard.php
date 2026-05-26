<?php
/**
 * HYREME Admin Dashboard
 * Platform analytics and user management
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get analytics data
$args_candidates = array('role' => 'candidate', 'number' => -1);
$candidates_query = new WP_User_Query($args_candidates);
$total_candidates = $candidates_query->get_total();

$args_recruiters = array('role' => 'recruiter', 'number' => -1);
$recruiters_query = new WP_User_Query($args_recruiters);
$total_recruiters = $recruiters_query->get_total();

// Count total videos
$total_videos = 0;
$all_users = get_users(array('number' => -1));
foreach ($all_users as $user) {
    if (get_user_meta($user->ID, 'hyreme_intro_video', true)) $total_videos++;
    if (get_user_meta($user->ID, 'hyreme_portfolio_video', true)) $total_videos++;
    if (get_user_meta($user->ID, 'hyreme_skill_video', true)) $total_videos++;
}

// Handle user deletion
if (isset($_POST['delete_user']) && wp_verify_nonce($_POST['hyreme_nonce'], 'hyreme_admin_action')) {
    $user_id = intval($_POST['user_id']);
    if ($user_id && $user_id != get_current_user_id()) {
        wp_delete_user($user_id);
        echo '<div style="background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">✅ User deleted successfully</div>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYREME Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 2rem;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 2rem; font-size: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #0ea5e9;
        }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .number { font-size: 2.5rem; color: #0ea5e9; font-weight: bold; }
        .section { background: white; border-radius: 0.75rem; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .section h2 { color: #333; margin-bottom: 1.5rem; font-size: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f8f8; padding: 1rem; text-align: left; font-weight: 600; color: #666; border-bottom: 1px solid #e0e0e0; }
        table td { padding: 1rem; border-bottom: 1px solid #f0f0f0; }
        table tr:hover { background: #fafafa; }
        .btn { 
            padding: 0.5rem 1rem; 
            border: none; 
            border-radius: 0.5rem; 
            cursor: pointer; 
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-candidate { background: #dbeafe; color: #1e40af; }
        .status-recruiter { background: #fcd34d; color: #92400e; }
        .search-box { margin-bottom: 1.5rem; }
        .search-box input { 
            width: 100%; 
            max-width: 300px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 HYREME Admin Dashboard</h1>
        
        <!-- ANALYTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👥 Total Candidates</h3>
                <div class="number"><?php echo $total_candidates; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h3>💼 Total Recruiters</h3>
                <div class="number" style="color: #f59e0b;"><?php echo $total_recruiters; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <h3>🎬 Total Videos</h3>
                <div class="number" style="color: #10b981;"><?php echo $total_videos; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <h3>👤 Total Users</h3>
                <div class="number" style="color: #8b5cf6;"><?php echo count($all_users); ?></div>
            </div>
        </div>

        <!-- USERS MANAGEMENT -->
        <div class="section">
            <h2>👥 User Management</h2>
            <div class="search-box">
                <input type="text" id="userSearch" placeholder="Search by email or name...">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Videos</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="usersTable">
                    <?php foreach ($all_users as $user): 
                        $role = in_array('recruiter', (array) $user->roles) ? 'Recruiter' : (in_array('candidate', (array) $user->roles) ? 'Candidate' : 'Admin');
                        $intro_video = get_user_meta($user->ID, 'hyreme_intro_video', true) ? '✓' : '';
                        $portfolio_video = get_user_meta($user->ID, 'hyreme_portfolio_video', true) ? '✓' : '';
                        $skill_video = get_user_meta($user->ID, 'hyreme_skill_video', true) ? '✓' : '';
                        $video_count = ($intro_video ? 1 : 0) + ($portfolio_video ? 1 : 0) + ($skill_video ? 1 : 0);
                    ?>
                    <tr class="user-row">
                        <td>
                            <strong><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></strong>
                            <br><small style="color: #666;"><?php echo esc_html($user->user_login); ?></small>
                        </td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($role); ?>">
                                <?php echo $role; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user->user_registered)); ?></td>
                        <td><?php echo $video_count; ?> video<?php echo $video_count !== 1 ? 's' : ''; ?></td>
                        <td>
                            <?php if ($user->ID !== get_current_user_id()): ?>
                                <form method="POST" style="display: inline;">
                                    <?php wp_nonce_field('hyreme_admin_action', 'hyreme_nonce'); ?>
                                    <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure? This action cannot be undone.');">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999;">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- VIDEO MANAGEMENT -->
        <div class="section">
            <h2>🎬 Video Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($all_users as $user) {
                        $video_types = array('intro' => 'Intro', 'portfolio' => 'Portfolio', 'skill' => 'Skill');
                        foreach ($video_types as $type => $label) {
                            $video_url = get_user_meta($user->ID, 'hyreme_' . $type . '_video', true);
                            if ($video_url) {
                                $user_name = $user->first_name . ' ' . $user->last_name ?: $user->user_login;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user_name); ?></strong>
                            <br><small style="color: #666;"><?php echo esc_html($user->user_email); ?></small>
                        </td>
                        <td><?php echo $label; ?></td>
                        <td><span class="status-badge status-candidate">Active</span></td>
                        <td><?php echo esc_html(basename($video_url)); ?></td>
                        <td>
                            <button class="btn btn-secondary" onclick="window.open('<?php echo esc_url($video_url); ?>', '_blank')">View</button>
                            <button class="btn btn-danger" onclick="deleteVideo(<?php echo $user->ID; ?>, '<?php echo $type; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php 
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_admin_nonce = '<?php echo wp_create_nonce('hyreme_admin_nonce'); ?>';

        function deleteVideo(userId, videoType) {
            if (!confirm('Delete this video?')) return;
            
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
                    alert('✅ Video deleted');
                    location.reload();
                } else {
                    alert('Error: ' + data.data);
                }
            });
        }

        // Search functionality
        document.getElementById('userSearch').addEventListener('keyup', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.user-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
