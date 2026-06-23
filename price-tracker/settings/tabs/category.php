<?php
if (!defined('ABSPATH')) {
    die;
}

global $wpdb;
$table_category = $wpdb->prefix . 'pt_category';
$table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
$table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';





// Get all categories
$categories = $wpdb->get_results("SELECT * FROM $table_category ORDER BY id DESC");

// Get all taxonomies for dropdown
$taxonomies = $wpdb->get_results("SELECT * FROM $table_taxonomy ORDER BY name ASC");
?>

<div id="category" class="tab-content">
    <h3><?php esc_html_e('Manage Categories', 'price-tracker'); ?></h3>

    <!-- Add Category Form -->
    <form method="post" class="add-category-from">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="category_name"><?php esc_html_e('Category Name', 'price-tracker'); ?></label></th>
                <td>
                    <input type="text" name="category_name" id="category_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="category_taxonomies"><?php esc_html_e('Assign Taxonomies', 'price-tracker'); ?></label></th>
                <td>
                    <select class="support-select2" name="category_taxonomies[]" id="category_taxonomies" multiple>
                        <?php foreach ($taxonomies as $taxonomy): ?>
                            <option value="<?php echo esc_attr($taxonomy->id); ?>">
                                <?php echo esc_html($taxonomy->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Search or select multiple categories.', 'price-tracker'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Add Category', 'price-tracker')); ?>
    </form>

    <hr>
    <!-- Hidden Edit Category Form -->
    <div id="edit-category-wrapper" style="display:none; margin-top:20px;">
        <h3><?php esc_html_e('Edit Category', 'price-tracker'); ?></h3>
        <form method="post" class="edit-category-form">
            <input type="hidden" name="edit_category_id" id="edit_category_id">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="edit_category_name"><?php esc_html_e('Category Name', 'price-tracker'); ?></label></th>
                    <td>
                        <input type="text" name="edit_category_name" id="edit_category_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="edit_category_taxonomies"><?php esc_html_e('Assign Taxonomies', 'price-tracker'); ?></label></th>
                    <td>
                        <select class="support-select2" name="edit_category_taxonomies[]" id="edit_category_taxonomies" multiple>
                            <?php foreach ($taxonomies as $taxonomy): ?>
                                <option value="<?php echo esc_attr($taxonomy->id); ?>">
                                    <?php echo esc_html($taxonomy->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Update Category', 'price-tracker'), 'primary', 'update_category_btn'); ?>
            <button type="button" id="cancel-edit" class="button-secondary"><?php esc_html_e('Cancel', 'price-tracker'); ?></button>
        </form>
    </div>

    <hr>

    <!-- Categories List -->
    <h3><?php esc_html_e('Existing Categories', 'price-tracker'); ?></h3>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Name', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Slug', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Taxonomies', 'price-tracker'); ?></th>
                <th><?php esc_html_e('Actions', 'price-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <?php
                    // Get taxonomies for this category
                    $linked_taxonomies = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT t.* FROM $table_taxonomy t
                             INNER JOIN $table_tax_cat tc ON t.id = tc.taxonomy_id
                             WHERE tc.category_id = %d
                             AND tc.status = 'active'
                             ",
                            $category->id
                        )
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html($category->id); ?></td>
                        <td><?php echo esc_html($category->name); ?></td>
                        <td><?php echo esc_html($category->slug); ?></td>
                        <td>
                            <?php
                            if ($linked_taxonomies) {
                                echo esc_html(implode(', ', wp_list_pluck($linked_taxonomies, 'name')));
                            } else {
                                echo '<em>' . esc_html__('No taxonomies assigned', 'price-tracker') . '</em>';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="delete-category button-secondary" data-category-id="<?php echo esc_attr($category->id); ?>">
                                <?php esc_html_e('Delete', 'price-tracker'); ?>
                            </button> | 
                            <button class="edit-category button-secondary"
                                data-category-id="<?php echo esc_attr($category->id); ?>"
                                data-name="<?php echo esc_attr($category->name); ?>"
                                data-taxonomies="<?php echo esc_attr(implode(',', wp_list_pluck($linked_taxonomies, 'id'))); ?>">
                                <?php esc_html_e('Edit', 'price-tracker'); ?>
                            </button>


                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No categories found.', 'price-tracker'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>