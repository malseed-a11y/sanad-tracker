<?php if (!defined('ABSPATH')) exit;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$delete_data = get_option('sanad_tracker_delete_on_uninstall', false);

if ($delete_data) {
    global $wpdb;

    $table_regions          = $wpdb->prefix . 'sanad_tracker_regions';
    $table_materials        = $wpdb->prefix . 'sanad_tracker_materials';
    $table_material_prices  = $wpdb->prefix . 'sanad_tracker_material_prices';
    $table_land_prices      = $wpdb->prefix . 'sanad_tracker_land_prices';

    $wpdb->query("DROP TABLE IF EXISTS {$table_land_prices}");
    $wpdb->query("DROP TABLE IF EXISTS {$table_material_prices}");
    $wpdb->query("DROP TABLE IF EXISTS {$table_materials}");
    $wpdb->query("DROP TABLE IF EXISTS {$table_regions}");

    delete_option('sanad_tracker_delete_on_uninstall');
}
