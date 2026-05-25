<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Custom v0 form processor and security logic.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Process the Custom v0 Registration Form
add_action('template_redirect', 'hyreme_process_v0_registration');
function hyreme_process_v0_registration() {
    // Check if our custom v0 form was submitted
    if ( isset($_POST['hyreme_register_submit']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        
        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_pass'];
        $role = sanitize_text_field($_POST['user_role']); // 'candidate' or 'recruiter'

        // Recruiter Email Validation
        if ( $role === 'recruiter' ) {
            $banned_domains = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com');
            $domain = substr(strrchr($email, "@"), 1);
            if ( in_array( strtolower($domain), $banned_domains ) ) {
                wp_die('Error: Recruiters must use a corporate email domain. Please go back and try again.');
            }
        }

        // Check if user already exists
        if ( username_exists($username) || email_exists($email) ) {
            wp_die('Error: That username or email is already registered. Please log in.');
        }

        // Create the User in WordPress Database
        $user_id = wp_create_user( $username, $password, $email );

        if ( ! is_wp_error( $user_id ) ) {
            // Assign the correct Ultimate Member Role
            wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );
            if(function_exists('UM')) {
                UM()->roles()->set_role($user_id, $role);
            }
            
            // Auto-login the user
            wp_clear_auth_cookie();
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            
            // Redirect to their new Dashboard
            wp_redirect( home_url('/account/') ); 
            exit;
        } else {
            wp_die($user_id->get_error_message());
        }
    }
}
?>
