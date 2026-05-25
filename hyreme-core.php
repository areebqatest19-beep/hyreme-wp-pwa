<?php
/**
 * Plugin Name: HYREME Core Features
 * Description: Custom platform logic, including Recruiter email validation.
 * Version: 1.0
 * Author: Areeb
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Restrict Recruiter Registrations to Business Emails
add_action('um_submit_form_errors_hook_', 'hyreme_restrict_recruiter_email', 10, 1);

function hyreme_restrict_recruiter_email( $args ) {
    // Check if the user is trying to register as a Recruiter (Role ID: recruiter)
    if ( isset( $args['role'] ) && $args['role'] === 'recruiter' ) {
        
        $banned_domains = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com');
        
        if ( isset( $args['user_email'] ) ) {
            $email = $args['user_email'];
            $domain = substr(strrchr($email, "@"), 1);
            
            if ( in_array( strtolower( $domain ), $banned_domains ) ) {
                UM()->form()->add_error( 'user_email', 'Recruiters must use a professional/company email address to join HYREME.' );
            }
        }
    }
}
?>
