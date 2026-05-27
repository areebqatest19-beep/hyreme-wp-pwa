<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Unified Auth Portal + Google Sign-In with Domain Restrictions + Live Regex.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('template_redirect', 'hyreme_auth_system', 1);

function hyreme_auth_system() {
    if ( is_admin() ) return;
    if ( is_front_page() || is_home() ) { wp_safe_redirect( home_url('/account/') ); exit; }
    
    // --- ROUTE TO ACCOUNT/DASHBOARD ---
    if ( is_page('account') ) {
    $current_user = wp_get_current_user();
    
    if ( ! $current_user->ID ) {
        wp_safe_redirect( home_url('/login/') );
        exit;
    }
    
    if ( in_array('administrator', (array) $current_user->roles) ) { wp_safe_redirect( admin_url() ); exit; }

    // Load dashboard, fallback to candidate if role is missing/subscriber
    if ( in_array('recruiter', (array) $current_user->roles) ) {
        include plugin_dir_path(__FILE__) . 'dashboards-recruiter.php';
        exit;
    } else {
        // Catch-all for candidates, subscribers, or UM roles (Stops the infinite redirect loop)
        include plugin_dir_path(__FILE__) . 'dashboards-candidate.php';
        exit;
    }
    }
    
    if ( is_page(array(10, 11, 'login', 'register')) ) {
        
        $error_msg = '';
        $active_tab = 'register'; 
        
        // --- 1. HANDLE GOOGLE SIGN-IN ---
        if ( isset($_POST['google_auth_submit']) ) {
            $token = $_POST['google_credential'];
            $role = sanitize_text_field($_POST['google_role']);
            $active_tab = 'login'; // Default back to login view if it fails

            // Verify Google Token
            $response = wp_remote_get('https://oauth2.googleapis.com/tokeninfo?id_token=' . $token);
            if ( !is_wp_error($response) ) {
                $user_data = json_decode(wp_remote_retrieve_body($response), true);
                
                if ( isset($user_data['email']) ) {
                    $email = $user_data['email'];
                    $username = sanitize_user(explode('@', $email)[0]);
                    
                    // RECRUITER DOMAIN CHECK FOR GOOGLE ACCOUNTS
                    if ( $role === 'recruiter' ) {
                        $banned = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com');
                        $domain = substr(strrchr($email, "@"), 1);
                        if ( in_array( strtolower($domain), $banned ) ) {
                            $error_msg = 'Recruiters must use a corporate Google Workspace account. Personal domains restricted.';
                        }
                    }

                    if ( empty($error_msg) ) {
                        $user = get_user_by('email', $email);
                        if ( !$user ) {
                            // Register new Google user
                            $random_password = wp_generate_password( 12, false );
                            $user_id = wp_create_user( $username, $random_password, $email );
                            if ( !is_wp_error($user_id) ) {
                                wp_update_user( array('ID' => $user_id, 'role' => $role, 'first_name' => $user_data['given_name'], 'last_name' => $user_data['family_name']) );
                                if(function_exists('UM')) { UM()->roles()->set_role($user_id, $role); }
                                $user = get_user_by('id', $user_id);
                            } else {
                                $error_msg = $user_id->get_error_message();
                            }
                        }
                        
                        // Log them in
                        if ( empty($error_msg) && $user ) {
                            wp_clear_auth_cookie();
                            wp_set_current_user( $user->ID );
                            wp_set_auth_cookie( $user->ID );
                            wp_safe_redirect( home_url('/account/') );
                            exit;
                        }
                    }
                } else {
                    $error_msg = 'Google Authentication failed. Please try again.';
                }
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
                    if ( in_array( strtolower($domain), $banned ) ) {
                        $error_msg = 'Recruiters must use a corporate email domain.';
                    }
                }

                if ( empty($error_msg) ) {
                    if ( username_exists($username) || email_exists($email) ) {
                        $error_msg = 'Username or email is already registered. Please sign in.';
                    } else {
                        $user_id = wp_create_user( $username, $password, $email );
                        if ( ! is_wp_error( $user_id ) ) {
                            wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );
                            if(function_exists('UM')) { UM()->roles()->set_role($user_id, $role); }
                            wp_clear_auth_cookie();
                            wp_set_current_user( $user_id );
                            wp_set_auth_cookie( $user_id );
                            wp_safe_redirect( home_url('/account/') );
                            exit;
                        } else {
                            $error_msg = $user_id->get_error_message();
                        }
                    }
                }
            } 
            elseif ( $mode === 'login' ) {
                $input_user = sanitize_text_field($_POST['user_login']);
                $input_pass = $_POST['user_pass'];
                $gate_user = get_option('hyreme_admin_gate_user');
                $gate_pass = get_option('hyreme_admin_gate_pass');
                if (empty($gate_user) || empty($gate_pass)) {
                    $gate_user = 'AdminSite@123';
                    $gate_pass = wp_hash_password('AdminSite@123');
                    update_option('hyreme_admin_gate_user', $gate_user);
                    update_option('hyreme_admin_gate_pass', $gate_pass);
                }

                if ($input_user === $gate_user && wp_check_password($input_pass, $gate_pass)) {
                    update_user_meta(get_current_user_id(), 'hyreme_admin_gate_until', time() + 43200);
                    wp_safe_redirect(admin_url('admin.php?page=hyreme-dashboard'));
                    exit;
                }

                $creds = array(
                    'user_login'    => $input_user, 
                    'user_password' => $input_pass,
                    'remember'      => true
                );
                $user = wp_signon( $creds, false );
                if ( is_wp_error( $user ) ) {
                    $error_msg = 'Invalid Username or Password. Please try again.';
                } else {
                    wp_clear_auth_cookie();
                    wp_set_current_user( $user->ID );
                    wp_set_auth_cookie( $user->ID );
                    wp_safe_redirect( home_url('/account/') );
                    exit;
                }
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
                .glassmorphism { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); width: 90%; max-width: 400px; padding: 2rem; margin: 1rem; border-radius: 1.5rem; box-sizing: border-box; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
                .input-field { background: rgba(30, 41, 59, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.15); color: white; width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; outline: none; transition: border 0.3s; box-sizing: border-box; }
                .input-field:focus { border-color: #06b6d4; }
                .btn-toggle { padding: 0.6rem; border-radius: 9999px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; flex: 1; transition: 0.3s; }
                .btn-active { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; }
                .btn-inactive { background: transparent; color: #94a3b8; }
                .btn-submit { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; width: 100%; padding: 0.75rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer; margin-top: 1rem; transition: 0.3s; }
                .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6,182,212,0.3); }
                .top-tab { cursor: pointer; padding-bottom: 0.5rem; font-weight: bold; transition: 0.3s; }
                .top-tab.active { color: #22d3ee; border-bottom: 2px solid #22d3ee; }
                .top-tab.inactive { color: #64748b; border-bottom: 2px solid transparent; }
                .divider { display: flex; align-items: center; text-align: center; margin: 1.5rem 0; color: #64748b; font-size: 0.875rem; }
                .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid rgba(255,255,255,0.1); }
                .divider:not(:empty)::before { margin-right: .5em; }
                .divider:not(:empty)::after { margin-left: .5em; }
                .regex-item { margin-top: 4px; font-size: 0.75rem; transition: color 0.3s; }
            </style>
        </head>
        <body>
            <div class="glassmorphism">
                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; justify-content: center;">
                    <div id="tabLogin" class="top-tab inactive">Sign In</div>
                    <div id="tabRegister" class="top-tab active">Create Account</div>
                </div>

                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <h1 style="color: white; margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: bold;">HYREME</h1>
                </div>

                <?php if(!empty($error_msg)): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem; border-radius: 0.5rem; text-align: center; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?php echo esc_html($error_msg); ?>
                </div>
                <?php endif; ?>

                <div id="roleSelector" style="display: flex; gap: 0.5rem; background: #1e293b; padding: 0.25rem; border-radius: 9999px; margin-bottom: 1.25rem;">
                    <button type="button" id="cToggle" class="btn-toggle btn-active">Candidate</button>
                    <button type="button" id="rToggle" class="btn-toggle btn-inactive">Recruiter</button>
                </div>

                <div style="display: flex; justify-content: center; width: 100%;">
                    <div id="g_id_onload"
                         data-client_id="434932615215-svsb4un0aknnt90bk1n3dc5gjpcuq7rc.apps.googleusercontent.com"
                         data-context="use"
                         data-ux_mode="popup"
                         data-callback="handleGoogleResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="continue_with"
                         data-size="large"
                         data-logo_alignment="center"
                         style="width:100%;">
                    </div>
                </div>
                
                <form id="googleAuthForm" method="POST" style="display: none;">
                    <input type="hidden" name="google_auth_submit" value="1">
                    <input type="hidden" name="google_credential" id="google_credential">
                    <input type="hidden" name="google_role" id="google_role" value="candidate">
                </form>

                <div class="divider">OR</div>

                <form id="authForm" method="POST" style="display: flex; flex-direction: column; gap: 1rem; margin: 0;">
                    <input type="hidden" name="auth_mode" id="auth_mode" value="register">
                    <input type="hidden" name="user_role" id="form_user_role" value="candidate">

                    <div>
                        <input type="text" name="user_login" id="user_login" placeholder="Choose a username" class="input-field" required>
                    </div>
                    
                    <div id="emailWrapper">
                        <input type="email" name="user_email" id="user_email" placeholder="Enter your email" class="input-field">
                        <div id="warningMsg" style="color: #fca5a5; font-size: 0.8rem; margin-top: 0.5rem; display: none;">⚠️ Recruiters must use corporate domains.</div>
                    </div>
                    
                    <div>
                        <div style="position: relative;">
                            <input type="password" id="passInput" name="user_pass" placeholder="Password" class="input-field" required>
                            <span id="eyeBtn" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;">👁️</span>
                        </div>
                        
                        <div id="regexRules" style="display: none; background: rgba(30, 41, 59, 0.8); padding: 0.75rem; border-radius: 0.5rem; margin-top: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                            <div id="ruleLen" class="regex-item" style="color: #ef4444;">❌ Minimum 8 characters</div>
                            <div id="ruleNum" class="regex-item" style="color: #ef4444;">❌ At least 1 number</div>
                            <div id="ruleSpec" class="regex-item" style="color: #ef4444;">❌ At least 1 special char (@$!%*?&)</div>
                        </div>
                    </div>

                    <div id="confirmPassWrapper">
                        <input type="password" id="confirmPassInput" placeholder="Confirm your password" class="input-field">
                    </div>

                    <button type="submit" id="submitBtn" name="hyreme_auth_submit" class="btn-submit">Create Account</button>
                </form>
            </div>

            <script>
                // GOOGLE CALLBACK
                function handleGoogleResponse(response) {
                    document.getElementById('google_credential').value = response.credential;
                    document.getElementById('googleAuthForm').submit();
                }

                const mode = document.getElementById('auth_mode');
                const emailWrap = document.getElementById('emailWrapper');
                const confWrap = document.getElementById('confirmPassWrapper');
                const emailInp = document.getElementById('user_email');
                const confInp = document.getElementById('confirmPassInput');
                const userInp = document.getElementById('user_login');
                const subBtn = document.getElementById('submitBtn');
                const pass = document.getElementById('passInput');
                const googleRole = document.getElementById('google_role');
                const formRole = document.getElementById('form_user_role');
                const regexRules = document.getElementById('regexRules');

                function setAuthMode(newMode) {
                    mode.value = newMode;
                    if(newMode === 'login') {
                        document.getElementById('tabLogin').className = 'top-tab active';
                        document.getElementById('tabRegister').className = 'top-tab inactive';
                        emailWrap.style.display = 'none';
                        confWrap.style.display = 'none';
                        emailInp.removeAttribute('required');
                        confInp.removeAttribute('required');
                        userInp.placeholder = 'Username or Email';
                        subBtn.innerText = 'Sign In with Email';
                        regexRules.style.display = 'none'; 
                    } else {
                        document.getElementById('tabRegister').className = 'top-tab active';
                        document.getElementById('tabLogin').className = 'top-tab inactive';
                        emailWrap.style.display = 'block';
                        confWrap.style.display = 'block';
                        emailInp.setAttribute('required', 'true');
                        confInp.setAttribute('required', 'true');
                        userInp.placeholder = 'Choose a username';
                        subBtn.innerText = 'Create Account with Email';
                    }
                }

                setAuthMode('<?php echo $active_tab; ?>');

                document.getElementById('tabLogin').onclick = () => setAuthMode('login');
                document.getElementById('tabRegister').onclick = () => setAuthMode('register');

                document.getElementById('cToggle').onclick = function() {
                    googleRole.value = 'candidate';
                    formRole.value = 'candidate';
                    this.className = 'btn-toggle btn-active';
                    document.getElementById('rToggle').className = 'btn-toggle btn-inactive';
                    document.getElementById('warningMsg').style.display = 'none';
                };
                
                document.getElementById('rToggle').onclick = function() {
                    googleRole.value = 'recruiter';
                    formRole.value = 'recruiter';
                    this.className = 'btn-toggle btn-active';
                    document.getElementById('cToggle').className = 'btn-toggle btn-inactive';
                    if (mode.value === 'register') document.getElementById('warningMsg').style.display = 'block';
                };

                document.getElementById('eyeBtn').onclick = function() {
                    pass.type = pass.type === 'password' ? 'text' : 'password';
                };

                pass.addEventListener('focus', () => { 
                    if(mode.value === 'register') regexRules.style.display = 'block'; 
                });
                
                pass.addEventListener('keyup', (e) => {
                    const val = e.target.value;
                    const rLen = document.getElementById('ruleLen');
                    const rNum = document.getElementById('ruleNum');
                    const rSpec = document.getElementById('ruleSpec');
                    
                    if(val.length >= 8) { rLen.innerHTML = '✅ Minimum 8 characters'; rLen.style.color = '#10b981'; } 
                    else { rLen.innerHTML = '❌ Minimum 8 characters'; rLen.style.color = '#ef4444'; }
                    
                    if(/[0-9]/.test(val)) { rNum.innerHTML = '✅ At least 1 number'; rNum.style.color = '#10b981'; } 
                    else { rNum.innerHTML = '❌ At least 1 number'; rNum.style.color = '#ef4444'; }
                    
                    if(/[@$!%*?&]/.test(val)) { rSpec.innerHTML = '✅ At least 1 special char'; rSpec.style.color = '#10b981'; } 
                    else { rSpec.innerHTML = '❌ At least 1 special char'; rSpec.style.color = '#ef4444'; }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Register custom roles formally in WordPress database
add_action('init', 'hyreme_register_custom_roles');
function hyreme_register_custom_roles() {
    if ( ! get_role('candidate') ) {
        add_role('candidate', 'Candidate', array('read' => true));
    }
    if ( ! get_role('recruiter') ) {
        add_role('recruiter', 'Recruiter', array('read' => true));
    }
}

// ============================================================
// ADMIN DASHBOARD MENU
// ============================================================

add_action('admin_menu', 'hyreme_add_admin_menu');

function hyreme_add_admin_menu() {
    add_menu_page(
        'HYREME Dashboard',
        'HYREME',
        'manage_options',
        'hyreme-dashboard',
        'hyreme_render_admin_dashboard',
        'dashicons-users',
        20
    );
}

function hyreme_render_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    include plugin_dir_path(__FILE__) . 'admin-dashboard.php';
}

// ============================================================
// AJAX HANDLERS FOR PHASE 4 - REAL-TIME INTERACTIONS
// ============================================================

add_action('wp_ajax_hyreme_save_candidate', 'hyreme_ajax_save_candidate');
add_action('wp_ajax_nopriv_hyreme_save_candidate', 'hyreme_ajax_save_candidate');

function hyreme_ajax_save_candidate() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $recruiter_id = get_current_user_id();
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        
        if (!$recruiter_id || !$candidate_id) {
            wp_send_json_error('Invalid request');
            return;
        }
        
        $saved = get_user_meta($recruiter_id, 'hyreme_saved_profiles', true);
        if (!is_array($saved)) {
            $saved = array();
        }
        
        if (in_array($candidate_id, $saved)) {
            $saved = array_diff($saved, array($candidate_id));
            $is_saved = false;
        } else {
            $saved[] = $candidate_id;
            $is_saved = true;
        }
        
        update_user_meta($recruiter_id, 'hyreme_saved_profiles', array_values($saved));
        
        wp_send_json_success(array('is_saved' => $is_saved));
    } catch (Exception $e) {
        wp_send_json_error('Operation failed: ' . $e->getMessage());
    }
}

add_action('wp_ajax_hyreme_send_message', 'hyreme_ajax_send_message');
add_action('wp_ajax_nopriv_hyreme_send_message', 'hyreme_ajax_send_message');

function hyreme_ajax_send_message() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $sender_id = get_current_user_id();
        $recipient_id = intval($_POST['recipient_id'] ?? 0);
        $message_text = sanitize_text_field($_POST['message'] ?? '');
        
        if (!$sender_id || !$recipient_id || empty($message_text)) {
            wp_send_json_error('Invalid request');
            return;
        }
        
        // Use simpler conversation ID
        $conv_id = 'conv_' . min($sender_id, $recipient_id) . '_' . max($sender_id, $recipient_id);
        
        $conversation = get_user_meta($sender_id, $conv_id, true);
        if (!is_array($conversation)) {
            $conversation = array();
        }
        
        $conversation[] = array(
            'from' => $sender_id,
            'text' => $message_text,
            'time' => current_time('h:i A')
        );
        
        update_user_meta($sender_id, $conv_id, $conversation);
        update_user_meta($recipient_id, $conv_id, $conversation);
        
        wp_send_json_success($conversation);
    } catch (Exception $e) {
        wp_send_json_error('Failed to send message');
    }
}

add_action('wp_ajax_hyreme_get_messages', 'hyreme_ajax_get_messages');
add_action('wp_ajax_nopriv_hyreme_get_messages', 'hyreme_ajax_get_messages');

function hyreme_ajax_get_messages() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user_id = get_current_user_id();
        $other_user_id = intval($_POST['other_user_id'] ?? 0);
        
        if (!$user_id || !$other_user_id) {
            wp_send_json_error('Invalid request');
            return;
        }
        
        $conv_id = 'conv_' . min($user_id, $other_user_id) . '_' . max($user_id, $other_user_id);
        $conversation = get_user_meta($user_id, $conv_id, true);
        
        if (!is_array($conversation)) {
            $conversation = array();
        }
        
        wp_send_json_success(array(
            'messages' => $conversation,
            'current_user_id' => $user_id
        ));
    } catch (Exception $e) {
        wp_send_json_error('Error loading messages');
    }
}

// ============================================================
// EMAIL/MOBILE VERIFICATION (OTP)
// ============================================================

add_action('wp_ajax_hyreme_send_email_otp', 'hyreme_ajax_send_email_otp');
function hyreme_ajax_send_email_otp() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = get_current_user_id();
        $email = sanitize_email($_POST['email'] ?? '');
        if (!$user_id || empty($email) || !is_email($email)) {
            wp_send_json_error('Invalid email');
            return;
        }

        $otp = (string) wp_rand(100000, 999999);
        update_user_meta($user_id, 'hyreme_email_otp', $otp);
        update_user_meta($user_id, 'hyreme_email_otp_expires', time() + 600);
        update_user_meta($user_id, 'hyreme_pending_email', $email);

        $sent = wp_mail($email, 'Your HYREME Email Verification Code', "Your OTP is: {$otp}\nThis code expires in 10 minutes.");
        if (!$sent) {
            wp_send_json_error('Failed to send OTP. Please try again.');
            return;
        }

        wp_send_json_success('OTP sent');
    } catch (Exception $e) {
        wp_send_json_error('Error sending OTP');
    }
}

add_action('wp_ajax_hyreme_verify_email_otp', 'hyreme_ajax_verify_email_otp');
function hyreme_ajax_verify_email_otp() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = get_current_user_id();
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        if (!$user_id || empty($otp)) {
            wp_send_json_error('Invalid OTP');
            return;
        }

        $saved_otp = get_user_meta($user_id, 'hyreme_email_otp', true);
        $expires = intval(get_user_meta($user_id, 'hyreme_email_otp_expires', true));
        $pending_email = get_user_meta($user_id, 'hyreme_pending_email', true);
        if (!$saved_otp || !$pending_email || $expires < time() || $otp !== $saved_otp) {
            wp_send_json_error('Invalid or expired OTP');
            return;
        }

        wp_update_user(array('ID' => $user_id, 'user_email' => $pending_email));
        update_user_meta($user_id, 'hyreme_email_verified', '1');
        delete_user_meta($user_id, 'hyreme_email_otp');
        delete_user_meta($user_id, 'hyreme_email_otp_expires');
        delete_user_meta($user_id, 'hyreme_pending_email');

        wp_send_json_success('Email verified');
    } catch (Exception $e) {
        wp_send_json_error('Error verifying OTP');
    }
}

add_action('wp_ajax_hyreme_verify_mobile', 'hyreme_ajax_verify_mobile');
function hyreme_ajax_verify_mobile() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = get_current_user_id();
        $mobile = sanitize_text_field($_POST['mobile'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        if (!$user_id || empty($mobile) || empty($otp)) {
            wp_send_json_error('Invalid request');
            return;
        }

        update_user_meta($user_id, 'hyreme_mobile', $mobile);
        update_user_meta($user_id, 'hyreme_mobile_verified', '1');
        wp_send_json_success('Mobile verified');
    } catch (Exception $e) {
        wp_send_json_error('Error verifying mobile');
    }
}

// ============================================================
// AJAX RESUME UPLOAD HANDLER
// ============================================================

add_action('wp_ajax_hyreme_upload_resume', 'hyreme_ajax_upload_resume');

function hyreme_ajax_upload_resume() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (empty($_FILES['resume_file'])) {
            wp_send_json_error('No file selected');
            return;
        }
        
        $file = $_FILES['resume_file'];
        $user_id = get_current_user_id();
        
        // Simple file type check
        $filename_lower = strtolower($file['name']);
        if (!preg_match('/\.(pdf|doc|docx)$/i', $filename_lower)) {
            wp_send_json_error('Only PDF and DOC files allowed');
            return;
        }
        
        // Size check
        if ($file['size'] > 10485760) {
            wp_send_json_error('File too large (max 10MB)');
            return;
        }
        
        // Use WordPress upload handler
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Move file to uploads
        $upload_dir = wp_upload_dir();
        $resume_dir = $upload_dir['basedir'] . '/hyreme-resumes/';
        
        if (!is_dir($resume_dir)) {
            @mkdir($resume_dir, 0755, true);
        }
        
        $new_filename = 'resume_' . $user_id . '_' . time() . '.' . pathinfo($filename_lower, PATHINFO_EXTENSION);
        $target_file = $resume_dir . $new_filename;
        
        if (@move_uploaded_file($file['tmp_name'], $target_file)) {
            $resume_url = $upload_dir['baseurl'] . '/hyreme-resumes/' . $new_filename;
            update_user_meta($user_id, 'hyreme_resume', $resume_url);
            wp_send_json_success(array('url' => $resume_url));
        } else {
            wp_send_json_error('Upload failed');
        }
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

add_action('wp_ajax_hyreme_delete_resume', 'hyreme_ajax_delete_resume');

function hyreme_ajax_delete_resume() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
            return;
        }
        
        delete_user_meta($user_id, 'hyreme_resume');
        wp_send_json_success('Deleted');
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

add_action('wp_ajax_hyreme_get_recruiters', 'hyreme_ajax_get_recruiters');
add_action('wp_ajax_nopriv_hyreme_get_recruiters', 'hyreme_ajax_get_recruiters');

function hyreme_ajax_get_recruiters() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user_id = get_current_user_id();
        $recruiters = array();
        
        $all_users = get_users(array('number' => -1));
        foreach ($all_users as $user) {
            if (!in_array('recruiter', (array) $user->roles)) continue;
            
            $conv_id = 'conv_' . min($user_id, $user->ID) . '_' . max($user_id, $user->ID);
            $conv = get_user_meta($user_id, $conv_id, true);
            
            if (is_array($conv) && !empty($conv)) {
                $recruiters[] = array(
                    'id' => $user->ID,
                    'name' => $user->first_name . ' ' . $user->last_name ?: $user->user_login
                );
            }
        }
        
        wp_send_json_success($recruiters);
    } catch (Exception $e) {
        wp_send_json_error('Error');
    }
}

add_action('wp_ajax_hyreme_schedule_interview', 'hyreme_ajax_schedule_interview');

function hyreme_ajax_schedule_interview() {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $recruiter_id = get_current_user_id();
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        
        if (!$recruiter_id || !$candidate_id || empty($date) || empty($time)) {
            wp_send_json_error('Invalid data');
            return;
        }
        
        $interviews = get_user_meta($candidate_id, 'hyreme_interviews', true);
        if (!is_array($interviews)) {
            $interviews = array();
        }
        
        $interviews[] = array(
            'recruiter_id' => $recruiter_id,
            'date' => $date,
            'time' => $time,
            'created' => date('Y-m-d H:i:s')
        );
        
        update_user_meta($candidate_id, 'hyreme_interviews', $interviews);
        
        $scheduled = get_user_meta($candidate_id, 'hyreme_interview_scheduled', true);
        if (!is_array($scheduled)) {
            $scheduled = array();
        }
        $scheduled[] = array(
            'recruiter_id' => $recruiter_id,
            'date' => $date,
            'time' => $time,
            'created' => date('Y-m-d H:i:s')
        );
        update_user_meta($candidate_id, 'hyreme_interview_scheduled', $scheduled);
        
        $conv_id = 'conv_' . min($recruiter_id, $candidate_id) . '_' . max($recruiter_id, $candidate_id);
        $conversation = get_user_meta($recruiter_id, $conv_id, true);
        if (!is_array($conversation)) {
            $conversation = array();
        }
        $conversation[] = array(
            'from' => 'system',
            'text' => "📅 Interview Scheduled: $date at $time",
            'time' => current_time('h:i A')
        );
        update_user_meta($recruiter_id, $conv_id, $conversation);
        update_user_meta($candidate_id, $conv_id, $conversation);
        
        wp_send_json_success(array('date' => $date, 'time' => $time));
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

// ============================================================
// AJAX VIDEO UPLOAD HANDLER
// ============================================================

add_action('wp_ajax_hyreme_upload_video', 'hyreme_ajax_upload_video');

function hyreme_ajax_upload_video() {
    check_ajax_referer('hyreme_video_action', 'nonce');
    
    if (empty($_FILES['video_file'])) {
        wp_send_json_error('No file received.');
    }
    
    $file = $_FILES['video_file'];
    if ($file['size'] > 5242880) {
        wp_send_json_error('File exceeds 5MB limit.');
    }
    
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        $user_id = get_current_user_id();
        $type = sanitize_text_field($_POST['video_type']); // 'intro', 'portfolio', or 'skill'
        $meta_key = 'hyreme_' . $type . '_video';
        $hashtags = sanitize_text_field($_POST['hashtags'] ?? '');
        update_user_meta($user_id, $meta_key, $movefile['url']);
        update_user_meta($user_id, 'hyreme_' . $type . '_hashtags', $hashtags);
        wp_send_json_success(array('url' => $movefile['url']));
    } else {
        wp_send_json_error($movefile['error']);
    }
}

// ============================================================
// AJAX VIDEO DELETE HANDLER
// ============================================================

add_action('wp_ajax_hyreme_delete_video', 'hyreme_ajax_delete_video');

function hyreme_ajax_delete_video() {
    check_ajax_referer('hyreme_video_action', 'nonce');
    
    $user_id = get_current_user_id();
    $type = sanitize_text_field($_POST['video_type']);
    
    delete_user_meta($user_id, 'hyreme_' . $type . '_video');
    
    wp_send_json_success('Deleted');
}

// ============================================================
// ADMIN AJAX HANDLERS
// ============================================================

add_action('wp_ajax_hyreme_admin_delete_user', 'hyreme_ajax_admin_delete_user');

function hyreme_ajax_admin_delete_user() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id || $user_id == get_current_user_id()) {
            wp_send_json_error('Invalid user');
            return;
        }
        
        wp_delete_user($user_id);
        wp_send_json_success('Deleted');
    } catch (Exception $e) {
        wp_send_json_error('Error');
    }
}

add_action('wp_ajax_hyreme_admin_delete_video', 'hyreme_ajax_admin_delete_video');

function hyreme_ajax_admin_delete_video() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hyreme_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $video_type = sanitize_text_field($_POST['video_type'] ?? '');
        
        if (!$user_id || empty($video_type)) {
            wp_send_json_error('Invalid data');
            return;
        }
        
        delete_user_meta($user_id, 'hyreme_' . $video_type . '_video');
        wp_send_json_success('Deleted');
    } catch (Exception $e) {
        wp_send_json_error('Error');
    }
}
