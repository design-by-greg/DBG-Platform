<?php

namespace DBGPlatform\Core;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    public static function autoload(string $class): void
    {
        $prefix = 'DBGPlatform\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', '/', $relative);
        $file = DBG_PLATFORM_PLUGIN_DIR . 'src/' . $relative . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
