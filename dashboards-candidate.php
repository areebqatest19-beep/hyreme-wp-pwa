<?php
/**
 * HYREME Candidate Dashboard
 * Premium dark-mode, glassmorphism UI for candidate profile management, video resume, and analytics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

if ( ! in_array( 'candidate', (array) $current_user->roles ) ) {
    wp_safe_redirect( home_url('/account/') );
    exit;
}

$update_msg = '';
$error_msg = '';

if ( isset($_POST['hyreme_profile_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    if ( ! isset($_POST['hyreme_profile_nonce']) || ! wp_verify_nonce($_POST['hyreme_profile_nonce'], 'hyreme_profile_action') ) {
        $error_msg = 'Security check failed. Please try again.';
    } else {
        $user_id = $current_user->ID;
        wp_update_user( array(
            'ID' => $user_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
        ) );
        update_user_meta($user_id, 'hyreme_skills', sanitize_textarea_field($_POST['skills']));
        update_user_meta($user_id, 'hyreme_experience', sanitize_textarea_field($_POST['experience']));
        update_user_meta($user_id, 'hyreme_education', sanitize_textarea_field($_POST['education']));
        update_user_meta($user_id, 'hyreme_portfolio_links', sanitize_textarea_field($_POST['portfolio_links']));
        update_user_meta($user_id, 'hyreme_location', sanitize_text_field($_POST['location']));
        update_user_meta($user_id, 'hyreme_salary_expectations', sanitize_text_field($_POST['salary_expectations']));
        $update_msg = '✅ Profile updated successfully!';
    }
}

$user_id = $current_user->ID;
$intro_video_url = get_user_meta($user_id, 'hyreme_intro_video', true);
$portfolio_video_url = get_user_meta($user_id, 'hyreme_portfolio_video', true);
$skill_video_url = get_user_meta($user_id, 'hyreme_skill_video', true);

$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$skills = get_user_meta($user_id, 'hyreme_skills', true);
$experience = get_user_meta($user_id, 'hyreme_experience', true);
$education = get_user_meta($user_id, 'hyreme_education', true);
$portfolio_links = get_user_meta($user_id, 'hyreme_portfolio_links', true);
$location = get_user_meta($user_id, 'hyreme_location', true);
$salary_expectations = get_user_meta($user_id, 'hyreme_salary_expectations', true);

$views_count = get_user_meta($user_id, 'hyreme_profile_views', true) ?: 0;
$saved_by_recruiters = get_user_meta($user_id, 'hyreme_saved_count', true) ?: 0;
$messages_count = get_user_meta($user_id, 'hyreme_messages_count', true) ?: 0;
$resume_url = get_user_meta($user_id, 'hyreme_resume', true) ?: '';
$notifications = get_user_meta($user_id, 'hyreme_notifications', true) ?: array();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYREME - Candidate Dashboard</title>
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
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 2rem 1.5rem; position: fixed; left: 0; top: 0; height: 100vh; display: flex; flex-direction: column; }
        .main-content { margin-left: 250px; flex: 1; padding: 2rem; overflow-y: auto; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; font-weight: bold; color: #22d3ee; margin-bottom: 0.5rem; }
        .header p { color: #94a3b8; font-size: 0.95rem; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; cursor: pointer; transition: 0.3s; color: #94a3b8; font-weight: 500; margin-bottom: 0.5rem; border: 1px solid transparent; }
        .nav-item:hover { background: rgba(30, 41, 59, 0.8); color: #22d3ee; border-color: rgba(34, 211, 238, 0.3); }
        .nav-item.active { background: rgba(34, 211, 238, 0.1); color: #22d3ee; border-color: #22d3ee; }
        .section { display: none; }
        .section.active { display: block; }
        .card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .card h2 { font-size: 1.5rem; color: #22d3ee; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: #cbd5e1; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .input-field, textarea { background: rgba(30, 41, 59, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.15); color: white; width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: 0.3s; font-family: inherit; }
        .input-field:focus, textarea:focus { border-color: #06b6d4; }
        textarea { resize: vertical; min-height: 100px; }
        .btn-submit { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6,182,212,0.3); }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #86efac; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .analytics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.75rem; padding: 1.5rem; text-align: center; }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #22d3ee; margin-bottom: 0.5rem; }
        .stat-card .label { color: #94a3b8; font-size: 0.9rem; }
        .upload-zone { background: rgba(30, 41, 59, 0.8); border: 2px dashed rgba(34, 211, 238, 0.5); border-radius: 0.75rem; padding: 2rem; text-align: center; cursor: pointer; transition: 0.3s; margin-bottom: 1.5rem; }
        .upload-zone:hover { border-color: #22d3ee; background: rgba(30, 41, 59, 0.95); }
        .upload-zone p { color: #94a3b8; margin-bottom: 0.5rem; }
        .upload-zone .note { font-size: 0.85rem; color: #64748b; }
        .logout-btn { text-align: center; padding: 0.75rem; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; border-radius: 0.5rem; cursor: pointer; transition: 0.3s; font-weight: 500; text-decoration: none; display: block; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.3); }
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1); flex-direction: row; overflow-x: auto; padding: 1rem; gap: 0.5rem; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .nav-item { white-space: nowrap; flex: 0 0 auto; }
            .header h1 { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #22d3ee; font-size: 1.25rem; margin-bottom: 0.5rem;">HYREME</h3>
                <p style="color: #64748b; font-size: 0.85rem;">Candidate Portal</p>
            </div>
            
            <nav onclick="switchSection(event)" style="flex: 1;">
                <div class="nav-item active" data-section="profile"><span>👤</span> Profile</div>
                <div class="nav-item" data-section="videos"><span>🎬</span> Video Resume</div>
                <div class="nav-item" data-section="analytics"><span>📊</span> Analytics</div>
                <div class="nav-item" data-section="messages"><span>💬</span> Messages</div>
                <div class="nav-item" data-section="notifications"><span>🔔</span> Notifications</div>
            </nav>

            <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-btn">🚪 Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Welcome Back!</h1>
                <p><?php echo esc_html($first_name . ' ' . $last_name); ?> • Candidate Account</p>
            </div>

            <div id="profile" class="section active">
                <div class="card">
                    <h2>📋 Profile Information</h2>
                    <?php if (!empty($update_msg)): ?><div class="alert alert-success"><?php echo esc_html($update_msg); ?></div><?php endif; ?>
                    <?php if (!empty($error_msg)): ?><div class="alert alert-error"><?php echo esc_html($error_msg); ?></div><?php endif; ?>

                    <form method="POST" style="display: flex; flex-direction: column; gap: 0;">
                        <?php wp_nonce_field('hyreme_profile_action', 'hyreme_profile_nonce'); ?>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="input-field" value="<?php echo esc_attr($first_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="input-field" value="<?php echo esc_attr($last_name); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" class="input-field" value="<?php echo esc_attr($location); ?>">
                        </div>
                        <div class="form-group">
                            <label for="skills">Skills (comma-separated)</label>
                            <textarea id="skills" name="skills"><?php echo esc_textarea($skills); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="experience">Professional Experience</label>
                            <textarea id="experience" name="experience"><?php echo esc_textarea($experience); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="education">Education</label>
                            <textarea id="education" name="education"><?php echo esc_textarea($education); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="portfolio_links">Portfolio Links</label>
                            <textarea id="portfolio_links" name="portfolio_links"><?php echo esc_textarea($portfolio_links); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="salary_expectations">Salary Expectations (Annual)</label>
                            <input type="text" id="salary_expectations" name="salary_expectations" class="input-field" value="<?php echo esc_attr($salary_expectations); ?>">
                        </div>
                        <button type="submit" name="hyreme_profile_submit" class="btn-submit" style="align-self: flex-start;">💾 Save Profile</button>
                    </form>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <h2>📄 Resume Upload</h2>
                    <p style="color: #94a3b8; margin-bottom: 1.5rem; font-size: 0.95rem;">Upload your resume as PDF or DOC format (Max 10MB)</p>
                    
                    <?php if (!empty($resume_url)): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="color: #10b981; font-weight: 600;">✅ Resume Uploaded</div>
                                <div style="color: #cbd5e1; font-size: 0.9rem; margin-top: 0.25rem;"><a href="<?php echo esc_url($resume_url); ?>" target="_blank" style="color: #22d3ee; text-decoration: underline;">View Resume</a></div>
                            </div>
                            <button onclick="deleteResume()" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.6rem 1rem; border-radius: 0.5rem; cursor: pointer;">🗑️ Delete</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="upload-zone" id="resume-upload-zone" style="padding: 3rem 1.5rem;">
                        <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">📄 Drag and drop your resume here</p>
                        <p class="note">PDF or DOC format • Max 10MB</p>
                        <input type="file" id="resumeFileInput" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display: none;">
                    </div>
                    <div id="resume-preview" style="margin-top: 1rem;"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="videos" class="section">
                <div class="card">
                    <h2>🎬 Video Resume Upload</h2>
                    <p style="color: #94a3b8; margin-bottom: 2rem; font-size: 0.95rem;">Upload your resume videos to showcase your talents.</p>

                    <input type="hidden" id="hyreme_video_nonce" value="<?php echo wp_create_nonce('hyreme_video_action'); ?>">

                    <div>
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Intro Video</h3>
                        <?php if (!empty($intro_video_url)): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; overflow: hidden;">
                            <video style="width: 100%; height: 300px; object-fit: contain; background: #000;" controls>
                                <source src="<?php echo esc_url($intro_video_url); ?>" type="video/mp4">
                            </video>
                            <div style="padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: #10b981; font-weight: 600;">✅ Video Active</div>
                                    <button onclick="deleteVideoAJAX('intro')" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.6rem 1rem; border-radius: 0.5rem; cursor: pointer;">🗑️ Delete & Replace</button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="upload-zone" data-video-type="intro">
                            <p>📹 Drag and drop your intro video here</p>
                            <p class="note">Max 5MB • MP4, WebM, or MOV</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                        <?php endif; ?>
                        <div id="intro-preview" style="margin-top: 1rem;"></div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Portfolio Showcase Video</h3>
                        <?php if (!empty($portfolio_video_url)): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; overflow: hidden;">
                            <video style="width: 100%; height: 300px; object-fit: contain; background: #000;" controls>
                                <source src="<?php echo esc_url($portfolio_video_url); ?>" type="video/mp4">
                            </video>
                            <div style="padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: #10b981; font-weight: 600;">✅ Video Active</div>
                                    <button onclick="deleteVideoAJAX('portfolio')" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.6rem 1rem; border-radius: 0.5rem; cursor: pointer;">🗑️ Delete & Replace</button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="upload-zone" data-video-type="portfolio">
                            <p>📹 Drag and drop your portfolio video here</p>
                            <p class="note">Max 5MB • MP4, WebM, or MOV</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                        <?php endif; ?>
                        <div id="portfolio-preview" style="margin-top: 1rem;"></div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Skill Showcase Video</h3>
                        <?php if (!empty($skill_video_url)): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; overflow: hidden;">
                            <video style="width: 100%; height: 300px; object-fit: contain; background: #000;" controls>
                                <source src="<?php echo esc_url($skill_video_url); ?>" type="video/mp4">
                            </video>
                            <div style="padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: #10b981; font-weight: 600;">✅ Video Active</div>
                                    <button onclick="deleteVideoAJAX('skill')" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.6rem 1rem; border-radius: 0.5rem; cursor: pointer;">🗑️ Delete & Replace</button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="upload-zone" data-video-type="skill">
                            <p>📹 Drag and drop your skill video here</p>
                            <p class="note">Max 5MB • MP4, WebM, or MOV</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                        <?php endif; ?>
                        <div id="skill-preview" style="margin-top: 1rem;"></div>
                    </div>
                </div>
            </div>

            <div id="analytics" class="section">
                <div class="card">
                    <h2>📊 Your Profile Analytics</h2>
                    <div class="analytics">
                        <div class="stat-card"><div class="number"><?php echo intval($views_count); ?></div><div class="label">Profile Views</div></div>
                        <div class="stat-card"><div class="number"><?php echo intval($saved_by_recruiters); ?></div><div class="label">Saved by Recruiters</div></div>
                        <div class="stat-card"><div class="number"><?php echo intval($messages_count); ?></div><div class="label">Messages Received</div></div>
                    </div>
                </div>
            </div>

            <div id="messages" class="section">
                <div class="card">
                    <h2>💬 Messages from Recruiters</h2>
                    <div id="recruiters-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;"></div>
                </div>
            </div>

            <div id="notifications" class="section">
                <div class="card">
                    <h2>🔔 Notifications</h2>
                    <div id="notifications-list">
                        <?php if (empty($notifications)): ?>
                        <div style="color: #94a3b8; text-align: center; padding: 2rem;">No notifications yet</div>
                        <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div style="background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.2); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                            <div style="color: #22d3ee; font-weight: 600; margin-bottom: 0.25rem;"><?php echo isset($notif['type']) ? esc_html($notif['type']) : 'Activity'; ?></div>
                            <div style="color: #cbd5e1;"><?php echo isset($notif['message']) ? esc_html($notif['message']) : ''; ?></div>
                            <div style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem;"><?php echo isset($notif['time']) ? esc_html($notif['time']) : ''; ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
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
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            item.classList.add('active');
        }

        document.querySelectorAll('.upload-zone').forEach(zone => {
            const fileInput = zone.querySelector('input[type="file"]');
            const videoType = zone.getAttribute('data-video-type');
            
            zone.addEventListener('click', () => fileInput.click());
            zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.style.borderColor = '#22d3ee'; });
            zone.addEventListener('dragleave', () => { zone.style.borderColor = 'rgba(34, 211, 238, 0.5)'; });
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('video/')) uploadVideoAJAX(file, videoType);
            });
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('video/')) uploadVideoAJAX(file, videoType);
            });
        });

        function uploadVideoAJAX(file, videoType) {
            const previewContainer = document.getElementById(videoType + '-preview');
            if (!previewContainer) return;
            
            const videoUrl = URL.createObjectURL(file);
            previewContainer.innerHTML = `
                <div style="background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.75rem; padding: 1rem;">
                    <video style="width: 100%; height: 300px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 1rem;" controls>
                        <source src="${videoUrl}" type="video/mp4">
                    </video>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="confirmUpload('${videoType}', this)" style="flex: 1; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">💾 Confirm & Save Video</button>
                        <button onclick="cancelUpload('${videoType}')" style="flex: 1; background: rgba(100, 116, 139, 0.2); color: #cbd5e1; padding: 0.75rem; border: 1px solid rgba(100, 116, 139, 0.3); border-radius: 0.5rem; cursor: pointer;">Cancel</button>
                    </div>
                </div>
            `;
            window['tempFile_' + videoType] = file;
        }
        
        function confirmUpload(videoType, button) {
            const file = window['tempFile_' + videoType];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('action', 'hyreme_upload_video');
            formData.append('nonce', document.getElementById('hyreme_video_nonce').value);
            formData.append('video_file', file);
            formData.append('video_type', videoType);
            
            const previewContainer = document.getElementById(videoType + '-preview');
            previewContainer.innerHTML = `
                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.5rem; height: 8px; margin-bottom: 0.5rem;">
                    <div class="upload-progress" style="background: linear-gradient(90deg, #0ea5e9, #06b6d4); height: 100%; width: 0%; transition: width 0.1s ease;"></div>
                </div>
                <div class="upload-status" style="text-align: center; color: #94a3b8; font-size: 0.85rem;">Uploading...</div>
            `;
            
            const xhr = new XMLHttpRequest();
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    const progressBar = previewContainer.querySelector('.upload-progress');
                    if (progressBar) progressBar.style.width = percentComplete + '%';
                }
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        previewContainer.innerHTML = '<div style="color: #10b981; font-weight: 600; text-align: center; padding: 1rem;">✅ Video Uploaded Successfully! Refreshing...</div>';
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        previewContainer.innerHTML = '<div style="color: #ef4444; text-align: center;">❌ ' + (response.data || 'Upload failed') + '</div>';
                    }
                }
            };
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.send(formData);
        }
        
        function cancelUpload(videoType) {
            document.getElementById(videoType + '-preview').innerHTML = '';
            window['tempFile_' + videoType] = null;
        }
        
        function deleteVideoAJAX(videoType) {
            if (!confirm('Are you sure you want to delete this video?')) return;
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'hyreme_delete_video', nonce: document.getElementById('hyreme_video_nonce').value, video_type: videoType })
            })
            .then(response => response.json())
            .then(data => { if (data.success) location.reload(); else alert('Error: ' + data.data); })
            .catch(() => alert('Error deleting video'));
        }

        // ============================================================
        // RESUME UPLOAD
        // ============================================================

        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const hyreme_nonce = '<?php echo wp_create_nonce('hyreme_nonce'); ?>';

        const resumeZone = document.getElementById('resume-upload-zone');
        if (resumeZone) {
            const fileInput = document.getElementById('resumeFileInput');
            
            resumeZone.addEventListener('click', () => fileInput.click());
            resumeZone.addEventListener('dragover', (e) => { 
                e.preventDefault(); 
                resumeZone.style.borderColor = '#22d3ee'; 
                resumeZone.style.background = 'rgba(30, 41, 59, 0.95)';
            });
            resumeZone.addEventListener('dragleave', () => { 
                resumeZone.style.borderColor = 'rgba(34, 211, 238, 0.5)'; 
                resumeZone.style.background = 'rgba(30, 41, 59, 0.8)';
            });
            resumeZone.addEventListener('drop', (e) => {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (file && (file.type === 'application/pdf' || file.type === 'application/msword' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
                    uploadResume(file);
                } else {
                    alert('Please upload a PDF or DOC file');
                }
            });
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) uploadResume(file);
            });
        }

        function uploadResume(file) {
            const previewContainer = document.getElementById('resume-preview');
            previewContainer.innerHTML = `
                <div style="background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.75rem; padding: 1.5rem;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                        <div style="font-size: 2rem;">📄</div>
                        <div>
                            <div style="color: #cbd5e1; font-weight: 600; word-break: break-all;">${file.name}</div>
                            <div style="color: #94a3b8; font-size: 0.9rem;">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="confirmResumeUpload(this)" style="flex: 1; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">✅ Upload Resume</button>
                        <button onclick="cancelResumeUpload()" style="flex: 1; background: rgba(100, 116, 139, 0.2); color: #cbd5e1; padding: 0.75rem; border: 1px solid rgba(100, 116, 139, 0.3); border-radius: 0.5rem; cursor: pointer;">Cancel</button>
                    </div>
                </div>
            `;
            window.tempResumeFile = file;
        }

        function confirmResumeUpload(button) {
            const file = window.tempResumeFile;
            if (!file) return;
            
            const formData = new FormData();
            formData.append('action', 'hyreme_upload_resume');
            formData.append('nonce', hyreme_nonce);
            formData.append('resume_file', file);
            
            const previewContainer = document.getElementById('resume-preview');
            previewContainer.innerHTML = '<div style="text-align: center; color: #94a3b8;">Uploading...</div>';
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    previewContainer.innerHTML = '<div style="color: #10b981; font-weight: 600; text-align: center; padding: 1rem;">✅ Resume Uploaded Successfully! Refreshing...</div>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    previewContainer.innerHTML = '<div style="color: #ef4444; text-align: center;">❌ ' + (data.data || 'Upload failed') + '</div>';
                }
            })
            .catch(err => {
                previewContainer.innerHTML = '<div style="color: #ef4444; text-align: center;">❌ Error: ' + err + '</div>';
            });
        }

        function cancelResumeUpload() {
            document.getElementById('resume-preview').innerHTML = '';
            window.tempResumeFile = null;
        }

        function deleteResume() {
            if (!confirm('Are you sure you want to delete your resume?')) return;
            // Mark as deleted by clearing the meta and refreshing
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    action: 'hyreme_delete_resume', 
                    nonce: hyreme_nonce 
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.data);
            })
            .catch(err => alert('Error: ' + err));
        }

        // ============================================================
        // MESSAGING SYSTEM
        // ============================================================

        let currentChatUserId = null;
        let messageRefreshInterval = null;

        function loadRecruiters() {
            const recruitersList = document.getElementById('recruiters-list');
            recruitersList.innerHTML = '<div style="grid-column: 1/-1; color:#94a3b8; text-align:center;">Loading conversations...</div>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    action: 'hyreme_get_recruiters', 
                    nonce: hyreme_nonce
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const recruiters = data.data || [];
                    if (recruiters.length === 0) {
                        recruitersList.innerHTML = '<div style="grid-column: 1/-1; color:#94a3b8; text-align:center; padding: 2rem;">No messages yet. Start conversations with recruiters who message you!</div>';
                        return;
                    }
                    recruitersList.innerHTML = '';
                    recruiters.forEach(recruiter => {
                        const card = document.createElement('div');
                        card.style.cssText = 'background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; cursor: pointer; transition: 0.3s;';
                        card.innerHTML = `<div style="color: #22d3ee; font-weight: 600; margin-bottom: 0.5rem;">${recruiter.name}</div><div style="color: #94a3b8; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${recruiter.preview}</div>`;
                        card.onclick = () => openChat(recruiter.id, recruiter.name);
                        recruitersList.appendChild(card);
                    });
                }
            });
        }

        function openChat(recruiterId, recruiterName) {
            currentChatUserId = recruiterId;
            document.getElementById('messages').scrollIntoView({ behavior: 'smooth' });
            loadMessages();
            
            // Refresh messages every 2 seconds
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
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.data.messages, data.data.current_user_id);
                }
            });
        }

        function displayMessages(messages, currentUserId) {
            // Messages display would go here - simple for now
        }

        // Load recruiters on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadRecruiters();
        });
    </script>
</body>
</html>
<?php exit; ?>