<?php

namespace DBGPlatform\Admin;

class AdminServiceProvider
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'DBG Platform',
            'DBG Platform',
            'manage_options',
            'dbg-platform',
            [$this, 'renderPage'],
            'dashicons-admin-generic'
        );
    }

    public function renderPage(): void
    {
        echo '<div class="wrap"><h1>DBG Platform</h1><p>Plugin scaffold active.</p></div>';
    }
}
