<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Custom auth processor and Full-Screen UI Hijack.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Hijack the page and process the form
add_action('template_redirect', 'hyreme_auth_system');

function hyreme_auth_system() {
    
    // 1. HANDLE THE FORM DATABASE CONNECTION
    if ( isset($_POST['hyreme_register_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_pass'];
        $role = sanitize_text_field($_POST['user_role']); 

        if ( $role === 'recruiter' ) {
            $banned = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com');
            $domain = substr(strrchr($email, "@"), 1);
            if ( in_array( strtolower($domain), $banned ) ) {
                wp_die('Error: Recruiters must use a corporate email domain. Go back and try again.');
            }
        }

        if ( username_exists($username) || email_exists($email) ) {
            wp_die('Error: That username or email is already registered.');
        }

        $user_id = wp_create_user( $username, $password, $email );

        if ( ! is_wp_error( $user_id ) ) {
            wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );
            if(function_exists('UM')) { UM()->roles()->set_role($user_id, $role); }
            wp_clear_auth_cookie();
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_redirect( home_url('/account/') ); 
            exit;
        } else {
            wp_die($user_id->get_error_message());
        }
    }

    // 2. HIJACK THE PAGE RENDER (Bypasses WP Theme Completely)
    if ( is_page(11) || is_page('register') ) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>HYREME - Register</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                body { margin: 0; padding: 0; height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); background-size: 200% 200%; animation: gradient-shift 15s ease infinite; font-family: sans-serif; }
                @keyframes gradient-shift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
                .glassmorphism { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); width: 90%; max-width: 420px; padding: 2.5rem; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
                .input-field { background: rgba(30, 41, 59, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.15); color: white; width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: border 0.3s; box-sizing: border-box; }
                .input-field:focus { border-color: #06b6d4; }
                .btn-toggle { padding: 0.6rem; border-radius: 9999px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; flex: 1; transition: 0.3s; }
                .btn-active { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; }
                .btn-inactive { background: transparent; color: #94a3b8; }
                .btn-submit { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; width: 100%; padding: 0.75rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer; margin-top: 1.5rem; transition: 0.3s; }
                .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6,182,212,0.3); }
            </style>
        </head>
        <body>
            <div class="glassmorphism">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h1 style="color: white; margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: bold;">HYREME</h1>
                    <p style="color: #94a3b8; margin: 0; font-size: 0.875rem;">Video-First Hiring Platform</p>
                </div>

                <div style="display: flex; gap: 0.5rem; background: #1e293b; padding: 0.25rem; border-radius: 9999px; margin-bottom: 2rem;">
                    <button type="button" id="cToggle" class="btn-toggle btn-active">Candidate</button>
                    <button type="button" id="rToggle" class="btn-toggle btn-inactive">Recruiter</button>
                </div>

                <form method="POST" style="display: flex; flex-direction: column; gap: 1.25rem; margin: 0;">
                    <input type="hidden" name="user_role" id="user_role" value="candidate">
                    
                    <div>
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Username</label>
                        <input type="text" name="user_login" placeholder="Choose a username" class="input-field" required>
                    </div>
                    
                    <div>
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Email</label>
                        <input type="email" name="user_email" placeholder="Enter your email" class="input-field" required>
                        <div id="warningMsg" style="color: #fca5a5; font-size: 0.8rem; margin-top: 0.5rem; display: none;">⚠️ Recruiters must use corporate domains.</div>
                    </div>
                    
                    <div>
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="passInput" name="user_pass" placeholder="Create a password" class="input-field" required>
                            <span id="eyeBtn" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;">👁️</span>
                        </div>
                    </div>

                    <button type="submit" name="hyreme_register_submit" class="btn-submit">Get Started</button>
                </form>
            </div>

            <script>
                document.getElementById('cToggle').onclick = function() {
                    document.getElementById('user_role').value = 'candidate';
                    this.className = 'btn-toggle btn-active';
                    document.getElementById('rToggle').className = 'btn-toggle btn-inactive';
                    document.getElementById('warningMsg').style.display = 'none';
                };
                document.getElementById('rToggle').onclick = function() {
                    document.getElementById('user_role').value = 'recruiter';
                    this.className = 'btn-toggle btn-active';
                    document.getElementById('cToggle').className = 'btn-toggle btn-inactive';
                    document.getElementById('warningMsg').style.display = 'block';
                };
                document.getElementById('eyeBtn').onclick = function() {
                    var p = document.getElementById('passInput');
                    p.type = p.type === 'password' ? 'text' : 'password';
                };
            </script>
        </body>
        </html>
        <?php
        exit; // THIS IS THE MAGIC WORD. It kills WordPress and stops it from loading its footer or split columns.
    }
}
