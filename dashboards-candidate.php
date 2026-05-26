<?php
/**
 * HYREME Candidate Dashboard
 * Premium dark-mode, glassmorphism UI for candidate profile management, video resume, and analytics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if user is logged in and is a candidate
$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

// Verify user role
if ( ! in_array( 'candidate', (array) $current_user->roles ) ) {
    wp_safe_redirect( home_url('/account/') );
    exit;
}

// --- HANDLE PROFILE UPDATE ---
$update_msg = '';
$error_msg = '';

if ( isset($_POST['hyreme_profile_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    // Verify nonce
    if ( ! isset($_POST['hyreme_profile_nonce']) || ! wp_verify_nonce($_POST['hyreme_profile_nonce'], 'hyreme_profile_action') ) {
        $error_msg = 'Security check failed. Please try again.';
    } else {
        $user_id = $current_user->ID;
        
        // Update basic user info
        wp_update_user( array(
            'ID' => $user_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
        ) );
        
        // Update user meta fields
        update_user_meta($user_id, 'hyreme_skills', sanitize_textarea_field($_POST['skills']));
        update_user_meta($user_id, 'hyreme_experience', sanitize_textarea_field($_POST['experience']));
        update_user_meta($user_id, 'hyreme_education', sanitize_textarea_field($_POST['education']));
        update_user_meta($user_id, 'hyreme_portfolio_links', sanitize_textarea_field($_POST['portfolio_links']));
        update_user_meta($user_id, 'hyreme_location', sanitize_text_field($_POST['location']));
        update_user_meta($user_id, 'hyreme_salary_expectations', sanitize_text_field($_POST['salary_expectations']));
        
        $update_msg = '✅ Profile updated successfully!';
    }
}

// Fetch current profile data
$user_id = $current_user->ID;
$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$skills = get_user_meta($user_id, 'hyreme_skills', true);
$experience = get_user_meta($user_id, 'hyreme_experience', true);
$education = get_user_meta($user_id, 'hyreme_education', true);
$portfolio_links = get_user_meta($user_id, 'hyreme_portfolio_links', true);
$location = get_user_meta($user_id, 'hyreme_location', true);
$salary_expectations = get_user_meta($user_id, 'hyreme_salary_expectations', true);

// Analytics placeholder
$views_count = get_user_meta($user_id, 'hyreme_profile_views', true) ?: 0;
$saved_by_recruiters = get_user_meta($user_id, 'hyreme_saved_count', true) ?: 0;
$messages_count = get_user_meta($user_id, 'hyreme_messages_count', true) ?: 0;

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
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: #cbd5e1; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .input-field, textarea {
            background: rgba(30, 41, 59, 0.8);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            color: white;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            outline: none;
            transition: 0.3s;
            font-family: inherit;
        }
        .input-field:focus, textarea:focus { border-color: #06b6d4; }
        textarea { resize: vertical; min-height: 100px; }
        .btn-submit {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6,182,212,0.3); }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #86efac;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }
        .analytics {
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
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #22d3ee; margin-bottom: 0.5rem; }
        .stat-card .label { color: #94a3b8; font-size: 0.9rem; }
        .upload-zone {
            background: rgba(30, 41, 59, 0.8);
            border: 2px dashed rgba(34, 211, 238, 0.5);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 1.5rem;
        }
        .upload-zone:hover {
            border-color: #22d3ee;
            background: rgba(30, 41, 59, 0.95);
        }
        .upload-zone p { color: #94a3b8; margin-bottom: 0.5rem; }
        .upload-zone .note { font-size: 0.85rem; color: #64748b; }
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR NAVIGATION -->
        <div class="sidebar">
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #22d3ee; font-size: 1.25rem; margin-bottom: 0.5rem;">HYREME</h3>
                <p style="color: #64748b; font-size: 0.85rem;">Candidate Portal</p>
            </div>
            
            <nav onclick="switchSection(event)" style="flex: 1;">
                <div class="nav-item active" data-section="profile">
                    <span>👤</span> Profile
                </div>
                <div class="nav-item" data-section="videos">
                    <span>🎬</span> Video Resume
                </div>
                <div class="nav-item" data-section="analytics">
                    <span>📊</span> Analytics
                </div>
            </nav>

            <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-btn">🚪 Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="header">
                <h1>Welcome Back!</h1>
                <p><?php echo esc_html($first_name . ' ' . $last_name); ?> • Candidate Account</p>
            </div>

            <!-- SECTION 1: PROFILE MANAGEMENT -->
            <div id="profile" class="section active">
                <div class="card">
                    <h2>📋 Profile Information</h2>
                    
                    <?php if (!empty($update_msg)): ?>
                    <div class="alert alert-success"><?php echo esc_html($update_msg); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error"><?php echo esc_html($error_msg); ?></div>
                    <?php endif; ?>

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
                            <input type="text" id="location" name="location" class="input-field" placeholder="e.g., San Francisco, CA" value="<?php echo esc_attr($location); ?>">
                        </div>

                        <div class="form-group">
                            <label for="skills">Skills (comma-separated)</label>
                            <textarea id="skills" name="skills" placeholder="e.g., React, Node.js, Python, UI Design..."><?php echo esc_textarea($skills); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="experience">Professional Experience</label>
                            <textarea id="experience" name="experience" placeholder="Describe your work experience, roles, and achievements..."><?php echo esc_textarea($experience); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="education">Education</label>
                            <textarea id="education" name="education" placeholder="Bachelor's in CS, MIT; Certifications, etc..."><?php echo esc_textarea($education); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="portfolio_links">Portfolio Links</label>
                            <textarea id="portfolio_links" name="portfolio_links" placeholder="GitHub, Dribbble, Portfolio website URLs (one per line)..."><?php echo esc_textarea($portfolio_links); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="salary_expectations">Salary Expectations (Annual)</label>
                            <input type="text" id="salary_expectations" name="salary_expectations" class="input-field" placeholder="e.g., $80,000 - $120,000" value="<?php echo esc_attr($salary_expectations); ?>">
                        </div>

                        <button type="submit" name="hyreme_profile_submit" class="btn-submit" style="align-self: flex-start;">💾 Save Profile</button>
                    </form>
                </div>
            </div>

            <!-- SECTION 2: VIDEO RESUME UPLOAD -->
            <div id="videos" class="section">
                <div class="card">
                    <h2>🎬 Video Resume Upload</h2>
                    <p style="color: #94a3b8; margin-bottom: 2rem; font-size: 0.95rem;">Upload your resume videos to showcase your talents. Each video should be between 30 seconds and 2 minutes.</p>

                    <!-- Intro Video -->
                    <div>
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Intro Video</h3>
                        <div class="upload-zone">
                            <p>📹 Drag and drop your intro video here</p>
                            <p style="font-size: 0.9rem; color: #64748b;">or click to select</p>
                            <p class="note">Max 2 minutes • MP4, WebM, or MOV • Up to 100MB</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                    </div>

                    <!-- Portfolio Video -->
                    <div>
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Portfolio/Project Showcase Video</h3>
                        <div class="upload-zone">
                            <p>📹 Drag and drop your portfolio video here</p>
                            <p style="font-size: 0.9rem; color: #64748b;">or click to select</p>
                            <p class="note">Max 2 minutes • MP4, WebM, or MOV • Up to 100MB</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                    </div>

                    <!-- Skill Showcase Video -->
                    <div>
                        <h3 style="color: #22d3ee; margin-bottom: 1rem; font-size: 1.1rem;">Skill Showcase Video</h3>
                        <div class="upload-zone">
                            <p>📹 Drag and drop your skill demo video here</p>
                            <p style="font-size: 0.9rem; color: #64748b;">or click to select</p>
                            <p class="note">Max 2 minutes • MP4, WebM, or MOV • Up to 100MB</p>
                            <input type="file" accept="video/*" style="display: none;">
                        </div>
                    </div>

                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">💡 <strong>Pro Tip:</strong> Keep your videos engaging, authentic, and professional. Recruiters love seeing personality and passion!</p>
                </div>
            </div>

            <!-- SECTION 3: ANALYTICS & STATUS -->
            <div id="analytics" class="section">
                <div class="card">
                    <h2>📊 Your Profile Analytics</h2>
                    
                    <div class="analytics">
                        <div class="stat-card">
                            <div class="number"><?php echo intval($views_count); ?></div>
                            <div class="label">Profile Views</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo intval($saved_by_recruiters); ?></div>
                            <div class="label">Saved by Recruiters</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo intval($messages_count); ?></div>
                            <div class="label">Messages Received</div>
                        </div>
                    </div>

                    <div style="background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.75rem; padding: 1.5rem; margin-top: 2rem;">
                        <h3 style="color: #22d3ee; margin-bottom: 1rem;">Profile Completion</h3>
                        <p style="color: #94a3b8; margin-bottom: 1rem;">Complete your profile to increase visibility with recruiters.</p>
                        <div style="background: rgba(30,41,59,0.8); border-radius: 9999px; height: 8px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); height: 100%; width: 75%; transition: width 0.3s;"></div>
                        </div>
                        <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 1rem;">75% Complete • Add a profile photo and upload videos to reach 100%</p>
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

        // Upload zone interactions
        document.querySelectorAll('.upload-zone').forEach(zone => {
            const fileInput = zone.querySelector('input[type="file"]');
            
            zone.addEventListener('click', function() {
                fileInput.click();
            });

            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#22d3ee';
                this.style.background = 'rgba(30, 41, 59, 0.95)';
            });

            zone.addEventListener('dragleave', function() {
                this.style.borderColor = 'rgba(34, 211, 238, 0.5)';
                this.style.background = 'rgba(30, 41, 59, 0.8)';
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('video/')) {
                    handleVideoUpload(file, zone);
                }
            });

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('video/')) {
                    handleVideoUpload(file, zone);
                }
            });
        });

        function handleVideoUpload(file, zone) {
            const videoUrl = URL.createObjectURL(file);
            
            // Hide the drag-and-drop text
            zone.querySelectorAll('p').forEach(p => p.style.display = 'none');
            
            // Create video preview
            const videoPreview = document.createElement('video');
            videoPreview.src = videoUrl;
            videoPreview.style.cssText = 'width: 100%; height: 300px; object-fit: contain; border-radius: 0.5rem; margin-bottom: 1rem;';
            videoPreview.controls = true;
            
            // Clear zone and add video
            zone.innerHTML = '';
            zone.appendChild(videoPreview);
            
            // Show progress bar
            const progressContainer = document.createElement('div');
            progressContainer.style.cssText = 'margin-bottom: 1rem;';
            progressContainer.innerHTML = `
                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 0.5rem; overflow: hidden; height: 8px;">
                    <div id="uploadBar" style="background: linear-gradient(90deg, #0ea5e9, #06b6d4); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                </div>
                <div style="text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 0.5rem;">Uploading...</div>
            `;
            zone.appendChild(progressContainer);
            
            // Simulate progress
            const progressBar = zone.querySelector('#uploadBar');
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 100) progress = 100;
                progressBar.style.width = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        progressContainer.innerHTML = '<div style="text-align: center; color: #10b981; font-weight: 600; font-size: 1rem;">✅ Video Attached Successfully</div>';
                    }, 200);
                }
            }, 200);
        }
    </script>
</body>
</html>
<?php exit; ?>
