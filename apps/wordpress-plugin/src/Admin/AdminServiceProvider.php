<?php

namespace DBGPlatform\Admin;

class AdminServiceProvider
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        (new FormHandler())->register();
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'DBG Platform',
            'DBG Platform',
            'manage_options',
            'dbg-platform',
            [$this, 'renderDashboard'],
            'dashicons-admin-generic'
        );

        add_submenu_page('dbg-platform', 'Dashboard', 'Dashboard', 'manage_options', 'dbg-platform', [$this, 'renderDashboard']);
        add_submenu_page('dbg-platform', 'Organisations', 'Organisations', 'manage_options', 'dbg-platform-organisations', [$this, 'renderOrganisations']);
        add_submenu_page('dbg-platform', 'Projects', 'Projects', 'manage_options', 'dbg-platform-projects', [$this, 'renderProjects']);
        add_submenu_page('dbg-platform', 'Assets', 'Assets', 'manage_options', 'dbg-platform-assets', [$this, 'renderAssets']);
        add_submenu_page('dbg-platform', 'Media', 'Media', 'manage_options', 'dbg-platform-media', [$this, 'renderMedia']);
        add_submenu_page('dbg-platform', 'Audit Logs', 'Audit Logs', 'manage_options', 'dbg-platform-audit-logs', [$this, 'renderAuditLogs']);
        add_submenu_page('dbg-platform', 'Settings', 'Settings', 'manage_options', 'dbg-platform-settings', [$this, 'renderSettings']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'dbg-platform') === false) {
            return;
        }

        wp_enqueue_style('dbg-platform-admin', DBG_PLATFORM_PLUGIN_URL . 'assets/admin.css', [], DBG_PLATFORM_VERSION);
    }

    public function renderDashboard(): void
    {
        $this->view('dashboard');
    }

    public function renderOrganisations(): void
    {
        $this->view('organisations');
    }

    public function renderProjects(): void
    {
        $this->view('projects');
    }

    public function renderAssets(): void
    {
        $this->view('assets');
    }

    public function renderMedia(): void
    {
        $this->view('media');
    }

    public function renderAuditLogs(): void
    {
        $this->view('audit-logs');
    }

    public function renderSettings(): void
    {
        $this->view('settings');
    }

    private function view(string $name): void
    {
        $file = DBG_PLATFORM_PLUGIN_DIR . 'src/Admin/Views/' . $name . '.php';

        if (file_exists($file)) {
            include $file;
            return;
        }

        echo '<div class="wrap"><h1>DBG Platform</h1><p>View not found.</p></div>';
    }
}
