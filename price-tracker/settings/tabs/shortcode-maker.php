<?php
global $wpdb;

$table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
$table_category = $wpdb->prefix . 'pt_category';

// Fetch taxonomies and categories
$taxonomies = $wpdb->get_results("SELECT * FROM $table_taxonomy ORDER BY name ASC");
$categories = $wpdb->get_results("SELECT * FROM $table_category ORDER BY id DESC");
?>

<div class="wrap">
    <h1><?php esc_html_e('Shortcode Generator', 'price-tracker'); ?></h1>

    <div id="shortcode-generator">
        <label for="shortcode-type"><?php esc_html_e('Shortcode Type', 'price-tracker'); ?></label>
        <select id="shortcode-type">
            <option value="price_tracker_chart">Price Chart</option>
            <option value="price_tracker_table">Price Table</option>
            <option value="price_tracker_insight">Price Insight</option>
        </select>

        <div class="field">
            <label for="taxonomy"><?php esc_html_e('Taxonomy', 'price-tracker'); ?></label>
            <select id="taxonomy" class="regular-text support-select2">
                <option value=""><?php esc_html_e('Select taxonomy', 'price-tracker'); ?></option>
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <option value="<?php echo esc_attr($taxonomy->slug); ?>">
                        <?php echo esc_html($taxonomy->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field category-field">
            <label for="category"><?php esc_html_e('Category', 'price-tracker'); ?></label>
            <select id="category" class="regular-text support-select2">
                <option value=""><?php esc_html_e('Select category', 'price-tracker'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="title"><?php esc_html_e('Title', 'price-tracker'); ?></label>
            <input type="text" id="title" placeholder="<?php esc_attr_e('Gold Price e.g.', 'price-tracker'); ?>">
        </div>

        <h2><?php esc_html_e('Generated Shortcode', 'price-tracker'); ?></h2>
        <textarea id="generated-shortcode" readonly></textarea>
        <button id="copy-shortcode" class="button"><?php esc_html_e('Copy to Clipboard', 'price-tracker'); ?></button>
    </div>
</div>
