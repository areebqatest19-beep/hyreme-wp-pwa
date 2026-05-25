<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Corrected role routing, Recruiter-only email validation, and interactive UI elements (Password Eye & Live Validation).
 * Version: 2.1
 * Author: Areeb
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. CORRECTED RECRUITER EMAIL RESTRICTION (Business Domains Only)
   ========================================================================== */
add_action('um_submit_form_errors_hook_', 'hyreme_restrict_recruiter_email', 10, 1);
function hyreme_restrict_recruiter_email( $args ) {
    // ONLY block if the hidden hyreme_role_type is explicitly set to 'recruiter'
    if ( isset($_POST['hyreme_role_type']) && $_POST['hyreme_role_type'] === 'recruiter' ) {
        $banned_domains = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com');
        if ( isset( $args['user_email'] ) ) {
            $email = $args['user_email'];
            $domain = substr(strrchr($email, "@"), 1);
            if ( in_array( strtolower( $domain ), $banned_domains ) ) {
                UM()->form()->add_error( 'user_email', 'Recruiters must use a professional corporate email (e.g., hr@company.com). Personal domains are restricted.' );
            }
        }
    }
}

/* ==========================================================================
   2. SYSTEM-LEVEL USER ACCOUNT FORCE-ROUTING
   ========================================================================== */
add_action('um_user_register', 'hyreme_assign_dynamic_role', 10, 2);
function hyreme_assign_dynamic_role($user_id, $args) {
    if (isset($_POST['hyreme_role_type'])) {
        $assigned_role = ($_POST['hyreme_role_type'] === 'recruiter') ? 'recruiter' : 'candidate';
        wp_update_user(array('ID' => $user_id, 'role' => $assigned_role));
        UM()->roles()->set_role($user_id, $assigned_role);
    }
}

/* ==========================================================================
   3. PREMIUM UI, PASSWORD EYE, & LIVE REGEX VALIDATION
   ========================================================================== */
add_action('wp_head', 'hyreme_inject_nextgen_ui');
function hyreme_inject_nextgen_ui() {
    if ( is_page( array( 10, 11 ) ) ) { // Login and Register pages
        ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <style>
            /* Clean Canvas & Header Removal */
            body {
                background: #0a0a0c !important;
                font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", Roboto, sans-serif !important;
                color: #ffffff !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 100vh !important;
                margin: 0 !important;
            }
            /* Hide the ugly default WordPress header menu */
            header, .site-header, .site-branding, #masthead { display: none !important; }
            h1.entry-title { display: none !important; } /* Hides "Register" and "Login" text */

            .um {
                background: #15151a !important;
                border: 1px solid #23232b !important;
                border-radius: 24px !important;
                padding: 40px !important;
                max-width: 420px !important;
                width: 100% !important;
                box-shadow: 0 10px 40px rgba(0,0,0,0.5) !important;
                box-sizing: border-box !important;
            }

            /* Custom Role Selection Navigation Interface */
            .hyreme-auth-nav {
                display: flex;
                background: #000000;
                padding: 4px;
                border-radius: 12px;
                margin-bottom: 30px;
                border: 1px solid #1f1f26;
            }
            .hyreme-nav-tab {
                flex: 1;
                text-align: center;
                padding: 12px;
                font-size: 14px;
                font-weight: 600;
                color: #71717a;
                cursor: pointer;
                border-radius: 10px;
                transition: all 0.2s ease;
            }
            .hyreme-nav-tab.active {
                background: #272730;
                color: #ffffff;
            }

            /* Modern Inputs */
            .um-field { position: relative; margin-bottom: 20px !important; padding: 0 !important; }
            .um-field-label {
                font-size: 13px !important;
                font-weight: 500 !important;
                color: #a1a1aa !important;
                margin-bottom: 8px !important;
                display: block !important;
            }
            .um-form input[type=text], .um-form input[type=password], .um-form input[type=email] {
                background: #1c1c22 !important;
                border: 1px solid #2d2d38 !important;
                color: #ffffff !important;
                border-radius: 12px !important;
                padding: 14px 16px !important;
                font-size: 15px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                transition: border-color 0.2s;
            }
            .um-form input:focus { border-color: #3b82f6 !important; outline: none !important; }

            /* Password Eye Icon Overlay */
            .toggle-password {
                position: absolute;
                right: 16px;
                top: 38px;
                color: #71717a;
                cursor: pointer;
                font-size: 16px;
                z-index: 10;
            }
            .toggle-password:hover { color: #ffffff; }

            /* Live Password Regex Validation Rules Box */
            .password-rules {
                background: #0f0f13;
                border: 1px solid #1f1f26;
                border-radius: 8px;
                padding: 12px;
                margin-top: -10px;
                margin-bottom: 20px;
                display: none; /* Hidden until focused */
            }
            .password-rules ul {
                list-style: none;
                padding: 0; margin: 0;
            }
            .password-rules li {
                font-size: 12px;
                color: #71717a;
                margin-bottom: 4px;
                display: flex;
                align-items: center;
            }
            .password-rules li i { margin-right: 8px; font-size: 10px; }
            .password-rules li.valid { color: #10b981; } /* Green */
            .password-rules li.invalid { color: #ef4444; } /* Red */

            /* Buttons */
            .um .um-button {
                background: #3b82f6 !important;
                border-radius: 12px !important;
                padding: 14px !important;
                font-size: 15px !important;
                font-weight: 600 !important;
            }
            .um .um-button:hover { background: #2563eb !important; }
            
            /* Hide UI Elements on Login Page */
            .page-id-10 .hyreme-auth-nav { display: none !important; }
        </style>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var form = document.querySelector("form");
            if(!form) return;

            // Only inject the Tabs on the Registration Page (Page ID 11)
            if(document.body.classList.contains('page-id-11')) {
                var navHtml = '<div class="hyreme-auth-nav">' +
                              '<div class="hyreme-nav-tab active" id="tab-candidate">Candidate</div>' +
                              '<div class="hyreme-nav-tab" id="tab-recruiter">Recruiter</div>' +
                              '</div>';
                form.insertAdjacentHTML('beforebegin', navHtml);
                
                var inputPayload = '<input type="hidden" name="hyreme_role_type" id="hyreme_role_type" value="candidate">';
                form.insertAdjacentHTML('beforeend', inputPayload);

                var tabCan = document.getElementById("tab-candidate");
                var tabRec = document.getElementById("tab-recruiter");
                var payload = document.getElementById("hyreme_role_type");
                
                tabCan.addEventListener("click", function() {
                    tabCan.classList.add("active");
                    tabRec.classList.remove("active");
                    payload.value = "candidate";
                });
                
                tabRec.addEventListener("click", function() {
                    tabRec.classList.add("active");
                    tabCan.classList.remove("active");
                    payload.value = "recruiter";
                });
            }

            // Inject Eye Icons & Live Regex Validation for Passwords
            var passFields = document.querySelectorAll("input[type='password']");
            if(passFields.length > 0) {
                var mainPassField = passFields[0];
                
                // Add the eye icon
                mainPassField.insertAdjacentHTML('afterend', '<i class="fa-regular fa-eye toggle-password"></i>');
                var toggleBtn = document.querySelector('.toggle-password');
                
                toggleBtn.addEventListener('click', function() {
                    var type = mainPassField.getAttribute('type') === 'password' ? 'text' : 'password';
                    mainPassField.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });

                // Add the live rules box (only on registration)
                if(document.body.classList.contains('page-id-11')) {
                    var rulesHtml = '<div class="password-rules" id="pass-rules">' +
                                    '<ul>' +
                                    '<li id="rule-length" class="invalid"><i class="fa-solid fa-circle"></i> Minimum 8 characters</li>' +
                                    '<li id="rule-number" class="invalid"><i class="fa-solid fa-circle"></i> At least 1 number</li>' +
                                    '<li id="rule-special" class="invalid"><i class="fa-solid fa-circle"></i> At least 1 special character (@$!%*?&)</li>' +
                                    '</ul></div>';
                    
                    mainPassField.closest('.um-field').insertAdjacentHTML('afterend', rulesHtml);
                    
                    var rulesBox = document.getElementById('pass-rules');
                    var ruleLen = document.getElementById('rule-length');
                    var ruleNum = document.getElementById('rule-number');
                    var ruleSpec = document.getElementById('rule-special');

                    mainPassField.addEventListener('focus', function() { rulesBox.style.display = 'block'; });
                    
                    mainPassField.addEventListener('keyup', function() {
                        var val = this.value;
                        // Check Length
                        if(val.length >= 8) { ruleLen.className = 'valid'; } else { ruleLen.className = 'invalid'; }
                        // Check Number
                        if(/[0-9]/.test(val)) { ruleNum.className = 'valid'; } else { ruleNum.className = 'invalid'; }
                        // Check Special
                        if(/[@$!%*?&]/.test(val)) { ruleSpec.className = 'valid'; } else { ruleSpec.className = 'invalid'; }
                    });
                }
            }
        });
        </script>
        <?php
    }
}
