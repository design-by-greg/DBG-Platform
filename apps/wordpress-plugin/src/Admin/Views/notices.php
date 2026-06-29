<?php
if (!defined('ABSPATH')) {
    exit;
}

$status = sanitize_key($_GET['dbg_status'] ?? '');
$errors = get_transient('dbg_platform_form_errors_' . get_current_user_id());

if ($status === 'created') {
    echo '<div class="notice notice-success is-dismissible"><p>Item created successfully.</p></div>';
}

if ($status === 'error' && !empty($errors) && is_array($errors)) {
    echo '<div class="notice notice-error is-dismissible"><p><strong>Validation error.</strong></p><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul></div>';
    delete_transient('dbg_platform_form_errors_' . get_current_user_id());
}
