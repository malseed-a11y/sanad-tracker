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
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Constants
define('SANAD_TRACKER_VERSION', '1.0.3');
define('SANAD_TRACKER_DIR', __DIR__);
define('SANAD_TRACKER_URL', plugin_dir_url(__FILE__));

// Activation hook — must be in the main plugin file
register_activation_hook(__FILE__, function () {
    $plugin = new SanadTracker();
    $plugin->activate();
});

class SanadTracker
{
    public function __construct()
    {
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        new \SanadTracker\Admin\AdminPage();
        new \SanadTracker\Ajax\RegionsAjax();
        new \SanadTracker\Ajax\MaterialsAjax();
        new \SanadTracker\Ajax\MaterialPricesAjax();
        new \SanadTracker\Ajax\LandPricesAjax();
        new \SanadTracker\Shortcodes\MaterialsShortcode();
        new \SanadTracker\Shortcodes\LandShortcode();
    }

    public function activate(): void
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();
        $table_regions          = $wpdb->prefix . 'sanad_tracker_regions';
        $table_materials        = $wpdb->prefix . 'sanad_tracker_materials';
        $table_material_prices  = $wpdb->prefix . 'sanad_tracker_material_prices';
        $table_land_prices      = $wpdb->prefix . 'sanad_tracker_land_prices';

        $sql_regions = "CREATE TABLE {$table_regions} (
            id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

        $sql_materials = "CREATE TABLE {$table_materials} (
            id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";

        $sql_material_prices = "CREATE TABLE {$table_material_prices} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            region_id   BIGINT(20) UNSIGNED NOT NULL,
            material_id BIGINT(20) UNSIGNED NOT NULL,
            price       DECIMAL(15,2) NOT NULL,
            date        DATE NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (region_id)   REFERENCES {$table_regions}(id)   ON DELETE CASCADE,
            FOREIGN KEY (material_id) REFERENCES {$table_materials}(id) ON DELETE CASCADE
        ) {$charset_collate};";

        $sql_land_prices = "CREATE TABLE {$table_land_prices} (
            id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            region_id            BIGINT(20) UNSIGNED NOT NULL,
            shell_core_price     DECIMAL(15,2) NOT NULL,
            fully_finished_price DECIMAL(15,2) NOT NULL,
            date                 DATE NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (region_id) REFERENCES {$table_regions}(id) ON DELETE CASCADE
        ) {$charset_collate};";

        dbDelta($sql_regions);
        dbDelta($sql_materials);
        dbDelta($sql_material_prices);
        dbDelta($sql_land_prices);

        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role && !$role->has_cap('sanad_tracker_access')) {
                $role->add_cap('sanad_tracker_access');
            }
        }
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }
}

new SanadTracker();
