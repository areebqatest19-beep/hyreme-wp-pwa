<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Unified Auth Portal with Inline Errors, Regex, and Seamless Redirects.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('template_redirect', 'hyreme_auth_system');

function hyreme_auth_system() {
    
    // Only hijack the Login and Register pages
    if ( is_page(array(10, 11, 'login', 'register')) ) {
        
        $error_msg = '';
        $active_tab = 'register'; // Default view
        
        // 1. HANDLE FORM SUBMISSION IN THE BACKGROUND
        if ( isset($_POST['hyreme_auth_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            $mode = $_POST['auth_mode']; 
            $active_tab = $mode; // Keep user on the tab they were using

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
                            wp_safe_redirect( home_url() ); // Redirects to Home Page
                            exit;
                        } else {
                            $error_msg = $user_id->get_error_message();
                        }
                    }
                }
            } 
            elseif ( $mode === 'login' ) {
                $creds = array(
                    'user_login'    => sanitize_text_field($_POST['user_login']), 
                    'user_password' => $_POST['user_pass'],
                    'remember'      => true
                );
                $user = wp_signon( $creds, false );
                if ( is_wp_error( $user ) ) {
                    $error_msg = 'Invalid Username or Password. Please try again.';
                } else {
                    wp_safe_redirect( home_url() ); // Redirects to Home Page
                    exit;
                }
            }
        }

        // 2. RENDER THE APP UI
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>HYREME - Auth</title>
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
                .top-tab { cursor: pointer; padding-bottom: 0.5rem; font-weight: bold; transition: 0.3s; }
                .top-tab.active { color: #22d3ee; border-bottom: 2px solid #22d3ee; }
                .top-tab.inactive { color: #64748b; border-bottom: 2px solid transparent; }
                .regex-item { margin-bottom: 4px; transition: color 0.3s; }
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

                <form id="authForm" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem; margin: 0;">
                    <input type="hidden" name="auth_mode" id="auth_mode" value="register">
                    <input type="hidden" name="user_role" id="user_role" value="candidate">
                    
                    <div id="roleSelector" style="display: flex; gap: 0.5rem; background: #1e293b; padding: 0.25rem; border-radius: 9999px;">
                        <button type="button" id="cToggle" class="btn-toggle btn-active">Candidate</button>
                        <button type="button" id="rToggle" class="btn-toggle btn-inactive">Recruiter</button>
                    </div>

                    <div>
                        <label id="userLabel" style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Username</label>
                        <input type="text" name="user_login" id="user_login" placeholder="Choose a username" class="input-field" required>
                    </div>
                    
                    <div id="emailWrapper">
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Email</label>
                        <input type="email" name="user_email" id="user_email" placeholder="Enter your email" class="input-field">
                        <div id="warningMsg" style="color: #fca5a5; font-size: 0.8rem; margin-top: 0.5rem; display: none;">⚠️ Recruiters must use corporate domains.</div>
                    </div>
                    
                    <div>
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="passInput" name="user_pass" placeholder="Password" class="input-field" required>
                            <span id="eyeBtn" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;">👁️</span>
                        </div>
                        
                        <div id="regexRules" style="display: none; background: rgba(30, 41, 59, 0.8); padding: 0.75rem; border-radius: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.1);">
                            <div id="ruleLen" class="regex-item" style="color: #ef4444;">❌ Minimum 8 characters</div>
                            <div id="ruleNum" class="regex-item" style="color: #ef4444;">❌ At least 1 number</div>
                            <div id="ruleSpec" class="regex-item" style="color: #ef4444;">❌ At least 1 special character (@$!%*?&)</div>
                        </div>
                    </div>

                    <div id="confirmPassWrapper">
                        <label style="color: #e2e8f0; font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Confirm Password</label>
                        <input type="password" id="confirmPassInput" placeholder="Confirm your password" class="input-field">
                        <div id="passMatchWarning" style="color: #fca5a5; font-size: 0.8rem; margin-top: 0.5rem; display: none;">⚠️ Passwords do not match.</div>
                    </div>

                    <button type="submit" id="submitBtn" name="hyreme_auth_submit" class="btn-submit">Create Account</button>
                </form>
            </div>

            <script>
                const mode = document.getElementById('auth_mode');
                const emailWrap = document.getElementById('emailWrapper');
                const confWrap = document.getElementById('confirmPassWrapper');
                const emailInp = document.getElementById('user_email');
                const confInp = document.getElementById('confirmPassInput');
                const userLab = document.getElementById('userLabel');
                const userInp = document.getElementById('user_login');
                const subBtn = document.getElementById('submitBtn');
                const passMatch = document.getElementById('passMatchWarning');
                const authForm = document.getElementById('authForm');
                const pass = document.getElementById('passInput');
                const regexRules = document.getElementById('regexRules');

                // TAB SWITCHING FUNCTION
                function setAuthMode(newMode) {
                    mode.value = newMode;
                    if(newMode === 'login') {
                        document.getElementById('tabLogin').className = 'top-tab active';
                        document.getElementById('tabRegister').className = 'top-tab inactive';
                        emailWrap.style.display = 'none';
                        confWrap.style.display = 'none';
                        emailInp.removeAttribute('required');
                        confInp.removeAttribute('required');
                        regexRules.style.display = 'none'; // Hide regex on login
                        userLab.innerText = 'Username or Email';
                        userInp.placeholder = 'Enter username or email';
                        subBtn.innerText = 'Sign In';
                    } else {
                        document.getElementById('tabRegister').className = 'top-tab active';
                        document.getElementById('tabLogin').className = 'top-tab inactive';
                        emailWrap.style.display = 'block';
                        confWrap.style.display = 'block';
                        emailInp.setAttribute('required', 'true');
                        confInp.setAttribute('required', 'true');
                        userLab.innerText = 'Username';
                        userInp.placeholder = 'Choose a username';
                        subBtn.innerText = 'Create Account';
                    }
                }

                // Initialize tab based on PHP state (so if login fails, it stays on login)
                setAuthMode('<?php echo $active_tab; ?>');

                document.getElementById('tabLogin').onclick = () => setAuthMode('login');
                document.getElementById('tabRegister').onclick = () => setAuthMode('register');

                // ROLE TOGGLE
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
                    if (mode.value === 'register') document.getElementById('warningMsg').style.display = 'block';
                };

                // PASSWORD EYE
                document.getElementById('eyeBtn').onclick = function() {
                    pass.type = pass.type === 'password' ? 'text' : 'password';
                };

                // LIVE REGEX VALIDATION
                pass.addEventListener('focus', () => { if(mode.value === 'register') regexRules.style.display = 'block'; });
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

                // PREVENT SUBMISSION IF PASSWORDS DON'T MATCH
                authForm.addEventListener('submit', function(e) {
                    if (mode.value === 'register' && pass.value !== confInp.value) {
                        e.preventDefault();
                        passMatch.style.display = 'block';
                    } else { passMatch.style.display = 'none'; }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
