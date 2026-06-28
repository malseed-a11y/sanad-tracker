<?php

/**
 * Plugin Name: Sanad Tracker
 * Description: A plugin to track buy/sell prices and rate changes for commodities.
 * Version: 1.0.3
 * Author: 2P
 * Author URI: https://2p.com.tr
 * Text Domain: sanad-tracker
 * Domain Path: /languages
 * Requires at least: 6.0.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

define('SANAD_TRACKER_VERSION', '1.0.3');
define('SANAD_TRACKER_DIR', __DIR__);
define('SANAD_TRACKER_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, ['SanadTracker\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['SanadTracker\Core\Deactivator', 'deactivate']);

(new \SanadTracker\Core\Plugin())->run();
