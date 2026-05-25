<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Unified Auth Portal + Google Sign-In + Domain Restrictions + Live Regex.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('template_redirect', 'hyreme_auth_system');

function hyreme_auth_system() {
    if ( is_page(array(10, 11, 'login', 'register')) ) {
        
        $error_msg = '';
        $active_tab = 'register'; 
        
        // --- 1. HANDLE GOOGLE SIGN-IN ---
        if ( isset($_POST['google_auth_submit']) ) {
            $token = $_POST['google_credential'];
            $role = sanitize_text_field($_POST['google_role']);
            $active_tab = 'login'; 

            $response = wp_remote_get('https://oauth2.googleapis.com/tokeninfo?id_token=' . $token);
            if ( !is_wp_error($response) ) {
                $user_data = json_decode(wp_remote_retrieve_body($response), true);
                if ( isset($user_data['email']) ) {
                    $email = $user_data['email'];
                    $username = sanitize_user(explode('@', $email)[0]);
                    
                    if ( $role === 'recruiter' ) {
                        $banned = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com');
                        $domain = substr(strrchr($email, "@"), 1);
                        if ( in_array( strtolower($domain), $banned ) ) {
                            $error_msg = 'Recruiters must use a corporate Google Workspace account.';
                        }
                    }

                    if ( empty($error_msg) ) {
                        $user = get_user_by('email', $email);
                        if ( !$user ) {
                            $random_password = wp_generate_password( 12, false );
                            $user_id = wp_create_user( $username, $random_password, $email );
                            if ( !is_wp_error($user_id) ) {
                                wp_update_user( array('ID' => $user_id, 'role' => $role, 'first_name' => $user_data['given_name'], 'last_name' => $user_data['family_name']) );
                                if(function_exists('UM')) { UM()->roles()->set_role($user_id, $role); }
                                $user = get_user_by('id', $user_id);
                            } else { $error_msg = $user_id->get_error_message(); }
                        }
                        if ( empty($error_msg) && $user ) {
                            wp_clear_auth_cookie(); wp_set_current_user( $user->ID ); wp_set_auth_cookie( $user->ID );
                            wp_safe_redirect( home_url('/account/') ); exit;
                        }
                    }
                } else { $error_msg = 'Google Authentication failed.'; }
            }
        }

        // --- 2. HANDLE MANUAL EMAIL/PASS ---
        if ( isset($_POST['hyreme_auth_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            $mode = $_POST['auth_mode']; 
            $active_tab = $mode; 

            if ( $mode === 'register' ) {
                $username = sanitize_user($_POST['user_login']);
                $email = sanitize_email($_POST['user_email']);
                $password = $_POST['user_pass'];
                $role = sanitize_text_field($_POST['user_role']); 

                if ( $role === 'recruiter' ) {
                    $banned = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com');
                    $domain = substr(strrchr($email, "@"), 1);
                    if ( in_array( strtolower($domain), $banned ) ) { $error_msg = 'Recruiters must use a corporate email domain.'; }
                }

                if ( empty($error_msg) ) {
                    if ( username_exists($username) || email_exists($email) ) { $error_msg = 'Username or email is already registered.'; } 
                    else {
                        $user_id = wp_create_user( $username, $password, $email );
                        if ( ! is_wp_error( $user_id ) ) {
                            wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );
                            if(function_exists('UM')) { UM()->roles()->set_role($user_id, $role); }
                            wp_clear_auth_cookie(); wp_set_current_user( $user_id ); wp_set_auth_cookie( $user_id );
                            wp_safe_redirect( home_url('/account/') ); exit;
                        } else { $error_msg = $user_id->get_error_message(); }
                    }
                }
            } elseif ( $mode === 'login' ) {
                $creds = array( 'user_login' => sanitize_text_field($_POST['user_login']), 'user_password' => $_POST['user_pass'], 'remember' => true );
                $user = wp_signon( $creds, false );
                if ( is_wp_error( $user ) ) { $error_msg = 'Invalid Username or Password.'; } 
                else { wp_safe_redirect( home_url('/account/') ); exit; }
            }
        }

        // --- 3. RENDER UI ---
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>HYREME - Auth</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <style>
                body { margin: 0; padding: 0; height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); background-size: 200% 200%; animation: gradient-shift 15s ease infinite; font-family: sans-serif; }
                @keyframes gradient-shift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
                .glassmorphism { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); width: 90%; max-width: 420px; padding: 2.5rem; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
                .input-field { background: rgba(30, 41, 59, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.15); color: white; width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: border 0.3s; box-sizing: border-box; }
                .btn-submit { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; width: 100%; padding: 0.75rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer; margin-top: 1rem; }
                .regex-item { font-size: 0.75rem; margin-top: 4px; }
            </style>
        </head>
        <body>
            <div class="glassmorphism">
                <?php if(!empty($error_msg)): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem;"><?php echo esc_html($error_msg); ?></div>
                <?php endif; ?>
                
                <form id="authForm" method="POST">
                    <input type="hidden" name="auth_mode" id="auth_mode" value="register">
                    <input type="hidden" name="user_role" id="form_user_role" value="candidate">
                    
                    <input type="text" name="user_login" id="user_login" placeholder="Username" class="input-field mb-4" required>
                    <input type="email" name="user_email" id="user_email" placeholder="Email" class="input-field mb-4">
                    <input type="password" id="passInput" name="user_pass" placeholder="Password" class="input-field mb-2" required>
                    
                    <div id="regexRules" style="display: none; color: #94a3b8; margin-bottom: 1rem;">
                        <div id="ruleLen" class="regex-item">❌ Min 8 chars</div>
                        <div id="ruleNum" class="regex-item">❌ At least 1 number</div>
                        <div id="ruleSpec" class="regex-item">❌ At least 1 special char (@$!%*?&)</div>
                    </div>
                    
                    <button type="submit" name="hyreme_auth_submit" class="btn-submit">Register</button>
                </form>
            </div>

            <script>
                const pass = document.getElementById('passInput');
                const regexRules = document.getElementById('regexRules');
                
                pass.addEventListener('focus', () => { regexRules.style.display = 'block'; });
                pass.addEventListener('keyup', (e) => {
                    const val = e.target.value;
                    document.getElementById('ruleLen').innerHTML = val.length >= 8 ? '✅ Min 8 chars' : '❌ Min 8 chars';
                    document.getElementById('ruleLen').style.color = val.length >= 8 ? '#10b981' : '#ef4444';
                    document.getElementById('ruleNum').innerHTML = /[0-9]/.test(val) ? '✅ At least 1 number' : '❌ At least 1 number';
                    document.getElementById('ruleNum').style.color = /[0-9]/.test(val) ? '#10b981' : '#ef4444';
                    document.getElementById('ruleSpec').innerHTML = /[@$!%*?&]/.test(val) ? '✅ At least 1 special char' : '❌ At least 1 special char';
                    document.getElementById('ruleSpec').style.color = /[@$!%*?&]/.test(val) ? '#10b981' : '#ef4444';
                });
            </script>
        </body>
        </html>
        <?php exit; }
}
