<?php

/**
 * Plugin Name: Price Tracker
 * Description: A plugin to track buy/sell prices and rate changes for commodities.
 * Version: 1.0.0
 * Author: 2P
 * Author URI: https://2p.com.tr
 * Text Domain: price-tracker
 * Domain Path: /languages
 * Requires at least: 6.0.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace PriceTrackerNamespace;

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

define('PRICE_TRACKER_VERSION', '1.0.0');
define('PRICE_TRACKER_DIR', __DIR__);
define('PRICE_TRACKER_URL', plugin_dir_url(__FILE__));

use PriceTrackerNamespace\settings\SettingsPage;
use PriceTrackerNamespace\classes\ShortcodeManager;
use PriceTrackerNamespace\classes\AjaxHandler;

class PriceTracker
{
    public function __construct()
    {

        new SettingsPage();
        new ShortcodeManager();
        new AjaxHandler();


        // Activation / Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }


    /**
     * Create database tables on activation
     */
    public function activate()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $wpdb->query('START TRANSACTION');

        // taxonomy table
        $table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
        $sql = "CREATE TABLE $table_taxonomy (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
        $result_taxonomy = dbDelta($sql);

        // category table
        $table_category = $wpdb->prefix . 'pt_category';
        $sql = "CREATE TABLE $table_category (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
        $result_category = dbDelta($sql);

        // taxonomy ↔ category pivot table
        $table_tax_cat = $wpdb->prefix . 'pt_taxonomy_category';
        $sql = "CREATE TABLE $table_tax_cat (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        taxonomy_id BIGINT(20) UNSIGNED NOT NULL,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        PRIMARY KEY (id),
        UNIQUE KEY taxonomy_category (taxonomy_id, category_id),
        FOREIGN KEY (taxonomy_id) REFERENCES $table_taxonomy(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES $table_category(id) ON DELETE CASCADE
    ) $charset_collate;";
        $result_tax_cat = dbDelta($sql);

        // items table (references taxonomy_category)
        $table_items = $wpdb->prefix . 'pt_items';
        $sql = "CREATE TABLE $table_items (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        taxonomy_category_id BIGINT(20) UNSIGNED NOT NULL,
        buy_price DECIMAL(15,2) NOT NULL,
        sell_price DECIMAL(15,2) NOT NULL,
        date DATE NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (taxonomy_category_id) REFERENCES $table_tax_cat(id) ON DELETE CASCADE
    ) $charset_collate;";
        $result_items = dbDelta($sql);

        if ($result_taxonomy || $result_category || $result_tax_cat || $result_items) {
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
        }

        // ✅ ADD CUSTOM CAPABILITY
        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role && ! $role->has_cap('price_tracker_access')) {
                $role->add_cap('price_tracker_access');
            }
        }
    }



    public function deactivate()
    {
        // No table drop here yet — we keep the data
        flush_rewrite_rules();
    }
}

new PriceTracker();
