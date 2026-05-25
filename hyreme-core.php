<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Advanced platform authentication logic, domain validation, and Next-Gen Dark UI.
 * Version: 2.0
 * Author: Areeb
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. RECRUITER EMAIL RESTRICTION (Business Domains Only)
   ========================================================================== */
add_action('um_submit_form_errors_hook_', 'hyreme_restrict_recruiter_email', 10, 1);
function hyreme_restrict_recruiter_email( $args ) {
    // Check if the submission belongs to the Recruiter flow
    if ( isset( $args['form_id'] ) && isset($_POST['hyreme_role_type']) && $_POST['hyreme_role_type'] === 'recruiter' ) {
        $banned_domains = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com');
        if ( isset( $args['user_email'] ) ) {
            $email = $args['user_email'];
            $domain = substr(strrchr($email, "@"), 1);
            if ( in_array( strtolower( $domain ), $banned_domains ) ) {
                UM()->form()->add_error( 'user_email', 'Registration blocked. Recruiters must utilize a professional corporate email domain (e.g., corporate@company.com).' );
            }
        }
    }
}

/* ==========================================================================
   2. INJECT SYSTEM-LEVEL USER ACCOUNT FORCE-ROUTING
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
   3. PREMIUM APP UI ENGINEERING ENGINE
   ========================================================================== */
add_action('wp_head', 'hyreme_inject_nextgen_ui');
function hyreme_inject_nextgen_ui() {
    if ( is_page( array( 10, 11 ) ) ) { // Login and Register pages
        ?>
        <style>
            /* Reset & Global Canvas Setup */
            body {
                background: radial-gradient(circle at 50% 0%, #121216 0%, #08080a 100%) !important;
                font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", Roboto, sans-serif !important;
                color: #ffffff !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 100vh !important;
                margin: 0 !important;
            }

            /* Main Web-App Structural Container */
            .um {
                background: rgba(22, 22, 27, 0.85) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 28px !important;
                padding: 40px 32px !important;
                max-width: 440px !important;
                width: 100% !important;
                box-shadow: 0 24px 64px rgba(0, 0, 0, 0.8) !important;
                backdrop-filter: blur(20px) !important;
                box-sizing: border-box !important;
            }

            /* Custom Role Selection Navigation Interface */
            .hyreme-auth-nav {
                display: flex;
                background: #000000;
                padding: 4px;
                border-radius: 14px;
                margin-bottom: 32px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .hyreme-nav-tab {
                flex: 1;
                text-align: center;
                padding: 12px;
                font-size: 14px;
                font-weight: 600;
                color: #8e8e93;
                cursor: pointer;
                border-radius: 11px;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .hyreme-nav-tab.active {
                background: #2c2c35;
                color: #ffffff;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }

            /* Modern Input Framework Optimization */
            .um-form input[type=text], 
            .um-form input[type=password], 
            .um-form input[type=email] {
                background: #1c1c24 !important;
                border: 1px solid rgba(255, 255, 255, 0.07) !important;
                color: #ffffff !important;
                border-radius: 14px !important;
                padding: 16px 18px !important;
                font-size: 15px !important;
                transition: all 0.2s ease !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .um-form input:focus {
                border-color: #007aff !important;
                background: #22222d !important;
                box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15) !important;
                outline: none !important;
            }

            /* Input Element Spacing and Labels */
            .um-field {
                margin-bottom: 20px !important;
                padding: 0 !important;
            }
            .um-field-label {
                font-size: 13px !important;
                font-weight: 600 !important;
                color: #a1a1aa !important;
                margin-bottom: 8px !important;
                display: block !important;
            }

            /* Custom Domain Warning Banner */
            .hyreme-domain-notice {
                font-size: 11px;
                color: #ff453a;
                margin-top: 6px;
                display: none;
                font-weight: 500;
            }
            body.route-recruiter .hyreme-domain-notice {
                display: block;
            }

            /* Action Buttons styling mapping to iOS clean accents */
            .um .um-button {
                background: #007aff !important;
                border-radius: 14px !important;
                padding: 16px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                letter-spacing: -0.2px !important;
                transition: all 0.2s ease !important;
            }
            .um .um-button:hover {
                background: #0063cc !important;
                transform: scale(0.99);
            }

            /* Alternative Link Elements (Register option variant style) */
            .um-button.um-alt {
                background: transparent !important;
                color: #007aff !important;
                border: 1px solid rgba(0, 122, 255, 0.3) !important;
                margin-top: 12px !important;
            }
            .um-button.um-alt:hover {
                background: rgba(0, 122, 255, 0.05) !important;
            }

            /* Layout Polish Elements */
            .um-field-error, .um-form-error {
                background: #ff453a20 !important;
                color: #ff453a !important;
                border: 1px solid #ff453a40 !important;
                border-radius: 12px !important;
                padding: 12px !important;
                font-size: 13px !important;
                text-shadow: none !important;
            }
            .um-left, .um-right, .css-clear { display: none !important; }
        </style>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Setup dynamic DOM framework inside the form wrapper
            var form = document.querySelector("form");
            if(!form) return;

            // Generate Functional Tab Interfacing Navigation Layout
            var navHtml = '<div class="hyreme-auth-nav">' +
                          '<div class="hyreme-nav-tab active" id="tab-candidate">Candidate Account</div>' +
                          '<div class="hyreme-nav-tab" id="tab-recruiter">Recruiter / Employer</div>' +
                          '</div>';
            form.insertAdjacentHTML('beforebegin', navHtml);

            // Append hidden functional payload value field to track role switching routing actions
            var inputPayload = '<input type="hidden" name="hyreme_role_type" id="hyreme_role_type" value="candidate">';
            form.insertAdjacentHTML('beforeend', inputPayload);

            // Match structural labels with native intuitive placeholders cleanly
            var emailField = document.querySelector("input[type='email']");
            if(emailField) {
                emailField.setAttribute("placeholder", "Enter your account email address");
                emailField.insertAdjacentHTML('afterend', '<div class="hyreme-domain-notice">⚠️ Notice: Only professional organizational domains accepted here. Common standard personal platforms (Gmail, Yahoo, etc.) are restricted.</div>');
            }

            var userField = document.querySelector("input[type='text']");
            if(userField) userField.setAttribute("placeholder", "Choose a unique username");

            var passFields = document.querySelectorAll("input[type='password']");
            passFields.forEach(function(p, index) {
                p.setAttribute("placeholder", index === 0 ? "Create a secure password" : "Confirm your account password");
            });

            // Implement interactive Navigation Routing Tab toggle event listeners
            var tabCan = document.getElementById("tab-candidate");
            var tabRec = document.getElementById("tab-recruiter");
            var payload = document.getElementById("hyreme_role_type");

            tabCan.addEventListener("click", function() {
                tabCan.classList.add("active");
                tabRec.classList.remove("active");
                document.body.classList.remove("route-recruiter");
                payload.value = "candidate";
            });

            tabRec.addEventListener("click", function() {
                tabRec.classList.add("active");
                tabCan.classList.remove("active");
                document.body.classList.add("route-recruiter");
                payload.value = "recruiter";
            });
        });
        </script>
        <?php
    }
}
