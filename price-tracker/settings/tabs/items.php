<?php
if (!defined('ABSPATH')) {
    die;
}

use PriceTrackerNamespace\classes\PTItemsTable;

global $wpdb;
$table_items    = $wpdb->prefix . 'pt_items';
$table_tc       = $wpdb->prefix . 'pt_taxonomy_category';
$table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
$table_category = $wpdb->prefix . 'pt_category';

// Get all taxonomies & categories
$taxonomies = $wpdb->get_results("SELECT * FROM $table_taxonomy ORDER BY name ASC");
$taxonomy_categories = $wpdb->get_results("
    SELECT tc.*, t.name AS taxonomy_name, c.name AS category_name
    FROM $table_tc tc
    LEFT JOIN $table_taxonomy t ON tc.taxonomy_id = t.id
    LEFT JOIN $table_category c ON tc.category_id = c.id
    WHERE tc.status = 'active'
    ORDER BY t.name, c.name
");

// Group categories by taxonomy for JS
$categories_by_taxonomy = [];
foreach ($taxonomy_categories as $tc) {
    $categories_by_taxonomy[$tc->taxonomy_id][] = $tc;
}

?>

<div id="items" class="tab-content">
    <h3><?php esc_html_e('Manage Items', 'price-tracker'); ?></h3>

    <!-- Add Item Form -->
    <form method="post" class="add-item-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="taxonomy_id"><?php esc_html_e('Taxonomy', 'price-tracker'); ?></label></th>
                <td>
                    <select name="taxonomy_id" id="taxonomy_id" required>
                        <option value=""><?php esc_html_e('-- Select Taxonomy --', 'price-tracker'); ?></option>
                        <?php foreach ($taxonomies as $taxonomy): ?>
                            <option value="<?php echo esc_attr($taxonomy->id); ?>">
                                <?php echo esc_html($taxonomy->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>

    <!-- This will be populated dynamically -->
    <div id="category-rows-wrapper" style="margin-top:20px;"></div>


    <hr>

    <!-- Hidden Edit Item Form -->
    <div id="edit-item-wrapper" style="display:none; margin-top:20px;">
        <h3><?php esc_html_e('Edit Item', 'price-tracker'); ?></h3>
        <form method="post" class="edit-item-form">
            <input type="hidden" name="edit_item_id" id="edit_item_id">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="edit_buy_price"><?php esc_html_e('Buy Price', 'price-tracker'); ?></label></th>
                    <td><input type="number" step="0.01" name="edit_buy_price" id="edit_buy_price" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_sell_price"><?php esc_html_e('Sell Price', 'price-tracker'); ?></label></th>
                    <td><input type="number" step="0.01" name="edit_sell_price" id="edit_sell_price" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_price_date"><?php esc_html_e('Date', 'price-tracker'); ?></label></th>
                    <td><input type="date" name="edit_price_date" id="edit_price_date" required></td>
                </tr>
            </table>

            <?php submit_button(__('Update Item', 'price-tracker'), 'primary', 'update_item_btn'); ?>
            <button type="button" id="cancel-edit-item" class="button-secondary"><?php esc_html_e('Cancel', 'price-tracker'); ?></button>
        </form>
    </div>
    <hr>


    <?php
    $items_table = new PTItemsTable();
    $items_table->prepare_items();
    ?>

    <!-- Items List -->
    <div class="wrap">
        <h1><?php esc_html_e('Items', 'price-tracker'); ?></h1>
        <?php
        $items_table->display();
        ?>
    </div>

</div>

<!-- JS: dynamic category filtering -->
<script>
    // Passing categories by taxonomy to JS
    var categoriesByTax = <?php echo wp_json_encode($categories_by_taxonomy); ?>;
</script>