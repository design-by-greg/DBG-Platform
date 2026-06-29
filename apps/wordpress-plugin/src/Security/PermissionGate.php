<?php

namespace DBGPlatform\Security;

use WP_REST_Request;

class PermissionGate
{
    public function canRead(WP_REST_Request $request): bool
    {
        return is_user_logged_in() && current_user_can('read');
    }

    public function canManage(WP_REST_Request $request): bool
    {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    public function canEditPosts(WP_REST_Request $request): bool
    {
        return is_user_logged_in() && current_user_can('edit_posts');
    }
}
