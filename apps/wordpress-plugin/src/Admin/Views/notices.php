<?php
if (!defined('ABSPATH')) {
    exit;
}

$status = sanitize_key($_GET['dbg_status'] ?? '');
$errors = get_transient('dbg_platform_form_errors_' . get_current_user_id());

$successMessages = [
    'created' => 'Item created successfully.',
    'updated' => 'Item updated successfully.',
    'deleted' => 'Item archived successfully.',
    'uploaded' => 'File uploaded successfully.',
];

if (isset($successMessages[$status])) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($successMessages[$status]) . '</p></div>';
}

if ($status === 'error' && !empty($errors) && is_array($errors)) {
    echo '<div class="notice notice-error is-dismissible"><p><strong>Validation error.</strong></p><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul></div>';
    delete_transient('dbg_platform_form_errors_' . get_current_user_id());
}
