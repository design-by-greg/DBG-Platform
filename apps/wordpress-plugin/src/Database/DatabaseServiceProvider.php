<?php

namespace DBGPlatform\Database;

class DatabaseServiceProvider
{
    public function register(): void
    {
        register_activation_hook(DBG_PLATFORM_PLUGIN_FILE, [$this, 'activate']);
    }

    public function activate(): void
    {
        (new Migrator())->run();
    }
}
