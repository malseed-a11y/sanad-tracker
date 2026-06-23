<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
if (defined('WP_UNINSTALL_PLUGIN')) {
    $delete_data_on_uninstall = get_option('price-tracker-delete-data-on-uninstall', false);
    if ($delete_data_on_uninstall == "on") {

        // truncate tables and delete the data
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
        $table_category = $wpdb->prefix . 'pt_category';
        $table_tax_cat = $wpdb->prefix . 'pt_taxonomy_category';
        $table_items = $wpdb->prefix . 'pt_items';

        try {
            $wpdb->query("DROP TABLE IF EXISTS $table_items");
            $wpdb->query("DROP TABLE IF EXISTS $table_tax_cat");
            $wpdb->query("DROP TABLE IF EXISTS $table_category");
            $wpdb->query("DROP TABLE IF EXISTS $table_taxonomy");

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
        }
    }
}
