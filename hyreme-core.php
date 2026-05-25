<?php
/**
 * Plugin Name: HYREME Core Features
 */
add_action('um_submit_form_errors_hook_', 'hyreme_restrict_recruiter_email', 10, 1);
function hyreme_restrict_recruiter_email( $args ) {
    if ( isset( $args['role'] ) && $args['role'] === 'recruiter' ) {
        $banned = array('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com');
        if ( isset( $args['user_email'] ) ) {
            $domain = substr(strrchr($args['user_email'], "@"), 1);
            if ( in_array( strtolower($domain), $banned ) ) {
                UM()->form()->add_error('user_email', 'Recruiters must use a professional company email.');
            }
        }
    }
}
?>
