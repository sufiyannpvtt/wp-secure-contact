<?php
/*
Plugin Name: WP Secure Contact
Description: Secure contact form plugin with admin management (CRUD + Search).
Version: 1.0
Author: Mohammad Shadullah
*/

if (!defined('ABSPATH')) {
    exit;
}

/*--------------------------------------------------------------
# Create DB table on plugin activation
--------------------------------------------------------------*/
register_activation_hook(__FILE__, 'wpsc_create_table');

function wpsc_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wpsc_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/*--------------------------------------------------------------
# Shortcode: Contact Form
--------------------------------------------------------------*/
function wpsc_contact_form() {
    ob_start();
    ?>

    <?php if (get_transient('wpsc_success')) : ?>
        <p style="color: green;">Message sent successfully!</p>
        <?php delete_transient('wpsc_success'); ?>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('wpsc_form_action', 'wpsc_nonce'); ?>

        <p><input type="text" name="wpsc_name" placeholder="Your Name" required></p>
        <p><input type="email" name="wpsc_email" placeholder="Your Email" required></p>
        <p><textarea name="wpsc_message" placeholder="Your Message" required></textarea></p>
        <p><input type="submit" name="wpsc_submit" value="Send Message"></p>
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('wpsc_contact', 'wpsc_contact_form');

/*--------------------------------------------------------------
# Handle Form Submission (CREATE)
--------------------------------------------------------------*/
function wpsc_handle_form_submission() {

    if (!isset($_POST['wpsc_submit'])) {
        return;
    }

    if (!isset($_POST['wpsc_nonce']) ||
        !wp_verify_nonce($_POST['wpsc_nonce'], 'wpsc_form_action')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wpsc_messages';

    $wpdb->insert(
        $table,
        [
            'name'    => sanitize_text_field($_POST['wpsc_name']),
            'email'   => sanitize_email($_POST['wpsc_email']),
            'message' => sanitize_textarea_field($_POST['wpsc_message']),
        ]
    );

    set_transient('wpsc_success', true, 30);
}
add_action('init', 'wpsc_handle_form_submission');

/*--------------------------------------------------------------
# Admin Menu
--------------------------------------------------------------*/
add_action('admin_menu', 'wpsc_admin_menu');

function wpsc_admin_menu() {
    add_menu_page(
        'Contact Messages',
        'Secure Contact',
        'manage_options',
        'wpsc-admin',
        'wpsc_admin_page',
        'dashicons-email'
    );
}

/*--------------------------------------------------------------
# Handle Delete Action (DELETE)
--------------------------------------------------------------*/
add_action('admin_init', 'wpsc_handle_delete');

function wpsc_handle_delete() {

    if (!isset($_GET['action'], $_GET['id'], $_GET['_wpnonce'])) {
        return;
    }

    if ($_GET['action'] !== 'wpsc_delete') {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!wp_verify_nonce($_GET['_wpnonce'], 'wpsc_delete_msg')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wpsc_messages';
    $id = absint($_GET['id']);

    $wpdb->delete($table, ['id' => $id], ['%d']);

    wp_redirect(admin_url('admin.php?page=wpsc-admin'));
    exit;
}

/*--------------------------------------------------------------
# Admin Page (READ + SEARCH + DELETE UI)
--------------------------------------------------------------*/
function wpsc_admin_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wpsc_messages';

    // Search
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where = '';

    if ($search) {
        $where = $wpdb->prepare(
            "WHERE name LIKE %s OR email LIKE %s OR message LIKE %s",
            "%$search%",
            "%$search%",
            "%$search%"
        );
    }

    $messages = $wpdb->get_results(
        "SELECT * FROM $table $where ORDER BY created_at DESC"
    );

    echo '<div class="wrap"><h1>Contact Messages</h1>';

    // Search form
    echo '<form method="get" style="margin-bottom:15px;">
            <input type="hidden" name="page" value="wpsc-admin">
            <input type="text" name="s" value="'.esc_attr($search).'" placeholder="Search messages">
            <input type="submit" class="button" value="Search">
          </form>';

    if (!$messages) {
        echo '<p>No messages found.</p></div>';
        return;
    }

    echo '<table class="widefat striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($messages as $msg) {

        $delete_url = wp_nonce_url(
            add_query_arg([
                'action' => 'wpsc_delete',
                'id'     => $msg->id
            ]),
            'wpsc_delete_msg'
        );

        echo '<tr>
                <td>'.esc_html($msg->name).'</td>
                <td>'.esc_html($msg->email).'</td>
                <td>'.esc_html($msg->message).'</td>
                <td>'.esc_html($msg->created_at).'</td>
                <td>
                    <a href="'.esc_url($delete_url).'"
                       onclick="return confirm(\'Delete this message?\');">
                       Delete
                    </a>
                </td>
              </tr>';
    }

    echo '</tbody></table></div>';
}
