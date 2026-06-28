<?php

namespace SanadTracker\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Migration
{
    public static function run(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();

        $tableRegions         = $wpdb->prefix . 'sanad_tracker_regions';
        $tableMaterials       = $wpdb->prefix . 'sanad_tracker_materials';
        $tableMaterialPrices  = $wpdb->prefix . 'sanad_tracker_material_prices';
        $tableLandPrices      = $wpdb->prefix . 'sanad_tracker_land_prices';

        $sqlRegions = "CREATE TABLE {$tableRegions} (
            id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charsetCollate};";

        $sqlMaterials = "CREATE TABLE {$tableMaterials} (
            id   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charsetCollate};";

        $sqlMaterialPrices = "CREATE TABLE {$tableMaterialPrices} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            region_id   BIGINT(20) UNSIGNED NOT NULL,
            material_id BIGINT(20) UNSIGNED NOT NULL,
            price       DECIMAL(15,2) NOT NULL,
            date        DATE NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (region_id)   REFERENCES {$tableRegions}(id)   ON DELETE CASCADE,
            FOREIGN KEY (material_id) REFERENCES {$tableMaterials}(id) ON DELETE CASCADE
        ) {$charsetCollate};";

        $sqlLandPrices = "CREATE TABLE {$tableLandPrices} (
            id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            region_id            BIGINT(20) UNSIGNED NOT NULL,
            shell_core_price     DECIMAL(15,2) NOT NULL,
            fully_finished_price DECIMAL(15,2) NOT NULL,
            date                 DATE NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (region_id) REFERENCES {$tableRegions}(id) ON DELETE CASCADE
        ) {$charsetCollate};";

        dbDelta($sqlRegions);
        dbDelta($sqlMaterials);
        dbDelta($sqlMaterialPrices);
        dbDelta($sqlLandPrices);
    }
}
