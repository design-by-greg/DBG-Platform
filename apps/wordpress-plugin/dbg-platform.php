<?php
/**
 * Plugin Name: DBG Platform
 * Description: WordPress integration layer for DBG Platform.
 * Version: 0.2.0
 * Author: Design by Greg
 * Text Domain: dbg-platform
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DBG_PLATFORM_VERSION', '0.2.0');
define('DBG_PLATFORM_PLUGIN_FILE', __FILE__);
define('DBG_PLATFORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DBG_PLATFORM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once DBG_PLATFORM_PLUGIN_DIR . 'src/Core/Autoloader.php';

\DBGPlatform\Core\Autoloader::register();

add_action('plugins_loaded', function () {
    $plugin = new \DBGPlatform\Core\Plugin();
    $plugin->boot();
});
