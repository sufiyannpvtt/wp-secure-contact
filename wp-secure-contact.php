<?php
/*
Plugin Name: WP Secure Contact
Description: Secure contact form plugin.
Version: 1.0
Author: Mohammad Shadullah
*/

if (!defined('ABSPATH')) {
    exit;
}

// Contact form shortcode
function wpsc_contact_form() {
    ob_start();
    ?>
    <?php if (get_transient('wpsc_success')): ?>
        <p style="color: green;">Message sent successfully!</p>
        <?php delete_transient('wpsc_success'); ?>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('wpsc_form_action', 'wpsc_nonce'); ?>

        <p>
            <input type="text" name="wpsc_name" placeholder="Your Name" required>
        </p>

        <p>
            <input type="email" name="wpsc_email" placeholder="Your Email" required>
        </p>

        <p>
            <textarea name="wpsc_message" placeholder="Your Message" required></textarea>
        </p>

        <p>
            <input type="submit" name="wpsc_submit" value="Send Message">
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('wpsc_contact', 'wpsc_contact_form');

// Handle form submission securely
function wpsc_handle_form_submission() {

    if (!isset($_POST['wpsc_submit'])) {
        return;
    }

    if (!isset($_POST['wpsc_nonce']) || 
        !wp_verify_nonce($_POST['wpsc_nonce'], 'wpsc_form_action')) {
        return;
    }

    $name    = sanitize_text_field($_POST['wpsc_name']);
    $email   = sanitize_email($_POST['wpsc_email']);
    $message = sanitize_textarea_field($_POST['wpsc_message']);

    // Success flag
    set_transient('wpsc_success', true, 30);
}
add_action('init', 'wpsc_handle_form_submission');


function wpsc_admin_menu() {
    add_menu_page(
        'Secure Contact',
        'Secure Contact',
        'manage_options',
        'wpsc-admin',
        'wpsc_admin_page'
    );
}
add_action('admin_menu', 'wpsc_admin_menu');

function wpsc_admin_page() {
    echo '<div class="wrap"><h1>WP Secure Contact Plugin</h1>';
    echo '<p>This plugin demonstrates secure WordPress form handling.</p></div>';
}


