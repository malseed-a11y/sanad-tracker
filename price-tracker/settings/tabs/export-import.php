<?php
if (!defined('ABSPATH')) {
    die;
}

global $wpdb;
?>
<h2><?php esc_html_e('Export/Import', 'price-tracker'); ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('price_tracker_export_csv', 'price_tracker_export_csv_nonce'); ?>
    <input type="hidden" name="action" value="price_tracker_export_csv">

    <label><?php esc_html_e('From Date', 'price-tracker'); ?></label>
    <input type="date" name="from_date">

    <label><?php esc_html_e('To Date', 'price-tracker'); ?></label>
    <input type="date" name="to_date">

    <label><?php esc_html_e('Taxonomy', 'price-tracker'); ?></label>
    <select name="filter_taxonomy">
        <option value=""><?php esc_html_e('-- All Taxonomies --', 'price-tracker'); ?></option>
        <?php
        $taxonomies = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}pt_taxonomy");
        foreach ($taxonomies as $t) {
            echo '<option value="' . esc_attr($t->id) . '">' . esc_html($t->name) . '</option>';
        }
        ?>
    </select>

    <label><?php esc_html_e('Category', 'price-tracker'); ?></label>
    <select name="filter_category">
        <option value=""><?php esc_html_e('-- All Categories --', 'price-tracker'); ?></option>
        <?php
        $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}pt_category");
        foreach ($categories as $c) {
            echo '<option value="' . esc_attr($c->id) . '">' . esc_html($c->name) . '</option>';
        }
        ?>
    </select>

    <input type="submit" class="button button-primary"
        value="<?php esc_attr_e('Export Data to CSV', 'price-tracker'); ?>">
</form>



<h2><?php esc_html_e('Import Data', 'price-tracker'); ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
    <?php wp_nonce_field('price_tracker_import_csv', 'price_tracker_import_csv_nonce'); ?>
    <input type="hidden" name="action" value="price_tracker_import_csv">

    <input type="file" name="import_csv" accept=".csv" required>
    <input type="submit" class="button button-primary"
        value="<?php esc_attr_e('Import CSV', 'price-tracker'); ?>">
</form>