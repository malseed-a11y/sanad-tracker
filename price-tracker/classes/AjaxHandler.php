<?php

namespace PriceTrackerNamespace\classes;

use DateTime;

if (!defined('ABSPATH')) {
    die;
}

class AjaxHandler
{
    public function __construct()
    {
        // Table Actions
        add_action('wp_ajax_price_tracker_table_filter', [$this, 'get_price_table_callback']);
        add_action('wp_ajax_nopriv_price_tracker_table_filter', [$this, 'get_price_table_callback']);

        // Chart Actions
        add_action('wp_ajax_get_price_tracker_chart', [$this, 'get_price_chart_callback']);
        add_action('wp_ajax_nopriv_get_price_tracker_chart',  [$this, 'get_price_chart_callback']);

        // insight Actions
        add_action('wp_ajax_get_price_tracker_insight', [$this, 'get_price_insight_callback']);
        add_action('wp_ajax_nopriv_get_price_tracker_insight',  [$this, 'get_price_insight_callback']);

        // Export Action
        add_action('admin_post_price_tracker_export_csv', [$this, 'price_tracker_export_csv']);

        // Import Action
        add_action('admin_post_price_tracker_import_csv', [$this, 'price_tracker_import_csv']);


        // Categories Actions
        add_action('wp_ajax_pt_add_category', [$this, 'pt_ajax_add_category']);
        add_action('wp_ajax_pt_delete_category', [$this, 'pt_ajax_delete_category']);
        add_action('wp_ajax_pt_edit_category', [$this, 'pt_ajax_edit_category']);


        // Taxonomies Actions
        add_action('wp_ajax_pt_add_taxonomy', [$this, 'pt_ajax_add_taxonomy']);
        add_action('wp_ajax_pt_delete_taxonomy', [$this, 'pt_ajax_delete_taxonomy']);
        add_action('wp_ajax_pt_edit_taxonomy', [$this, 'pt_ajax_edit_taxonomy']);


        // Items Actions
        add_action('wp_ajax_pt_add_item', [$this, 'pt_ajax_add_item']);
        add_action('wp_ajax_pt_delete_item', [$this, 'pt_ajax_delete_item']);
        add_action('wp_ajax_pt_edit_item', [$this, 'pt_ajax_edit_item']);

        // Unassigned Actions
        add_action('wp_ajax_price_tracker_reassign_unassigned', [$this, 'price_tracker_reassign_unassigned']);
        add_action('wp_ajax_price_tracker_delete_unassigned', [$this, 'price_tracker_delete_unassigned']);
    }
    // === FILTER ACTION ===
    public function get_price_table_callback()
    {
        check_ajax_referer('price_tracker_table_nonce', 'nonce');

        global $wpdb;

        $taxonomy_slug = sanitize_text_field($_POST['taxonomy'] ?? '');
        $date          = sanitize_text_field($_POST['date'] ?? '');
        $range         = sanitize_text_field($_POST['range'] ?? '');

        // Get taxonomy ID
        $taxonomy_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pt_taxonomy WHERE slug = %s",
            $taxonomy_slug
        ));

        if (!$taxonomy_id) {
            wp_send_json(['html' => '<p>No taxonomy found.</p>']);
        }

        // Get categories linked to this taxonomy via pivot
        $categories = $wpdb->get_results($wpdb->prepare("
            SELECT c.id, c.name, tc.id AS taxonomy_category_id
            FROM {$wpdb->prefix}pt_category c
            INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc
                ON c.id = tc.category_id
            WHERE tc.taxonomy_id = %d
            AND tc.status = 'active'
            ORDER BY c.name ASC
        ", $taxonomy_id));

        if (!$categories) {
            wp_send_json(['html' => '<p>No categories found for this taxonomy.</p>']);
        }

        $is_there_something_to_show = false;
        ob_start();
        include PRICE_TRACKER_DIR . '/templates/price-table.php';
        $html = ob_get_clean();

        // Check if we have any data to show
        if (!$is_there_something_to_show) {
            $html = '<p>' . __("No data available for the selected filters.", "price-tracker") . '</p>';
        }

        wp_send_json(['html' => $html]);
    }



    function get_price_chart_callback()
    {
        global $wpdb;
        check_ajax_referer('price_tracker_chart_nonce', 'nonce');

        $taxonomy_slug = sanitize_text_field($_POST['taxonomy'] ?? '');
        $category_slug = sanitize_text_field($_POST['category'] ?? '');
        $from_date     = sanitize_text_field($_POST['from'] ?? date('Y-m-d'));
        $to_date       = sanitize_text_field($_POST['to'] ?? date('Y-m-d'));

        if (empty($taxonomy_slug)) {
            wp_send_json_error(__('Taxonomy required.', 'price-tracker'));
        }

        $taxonomy_table = $wpdb->prefix . 'pt_taxonomy';
        $category_table = $wpdb->prefix . 'pt_category';
        $tax_cat_table  = $wpdb->prefix . 'pt_taxonomy_category';
        $items_table    = $wpdb->prefix . 'pt_items';

        $taxonomy_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $taxonomy_table WHERE slug=%s", $taxonomy_slug));
        if (!$taxonomy_id) {
            wp_send_json_error(__('Taxonomy not found.', 'price-tracker'));
        }

        $date_where = "";
        $params = [];
        if ($from_date) {
            $date_where .= " AND i.date >= %s";
            $params[] = $from_date;
        }
        if ($to_date) {
            $date_where .= " AND i.date <= %s";
            $params[] = $to_date;
        }

        $labels = [];
        $datasets = [];

        if (!empty($category_slug)) {
            // Single category case
            $category_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $category_table WHERE slug=%s", $category_slug));
            if (!$category_id) wp_send_json_error(__('Category not found.', 'price-tracker'));

            $query = "SELECT i.date, i.buy_price, i.sell_price
                  FROM $items_table i
                  INNER JOIN $tax_cat_table tc ON i.taxonomy_category_id = tc.id
                  WHERE tc.taxonomy_id=%d AND tc.category_id=%d $date_where
                  ORDER BY i.id DESC";

            $items = $wpdb->get_results($wpdb->prepare($query, array_merge([$taxonomy_id, $category_id], $params)));

            if (!$items) wp_send_json_error(__('No items found.', 'price-tracker'));

            $labels = array_map(fn($i) => $i->date, $items);
            $buy_prices = array_map(fn($i) => (float)$i->buy_price, $items);
            $sell_prices = array_map(fn($i) => (float)$i->sell_price, $items);

            $datasets = [
                [
                    'label' => esc_html__('Buy Price', 'price-tracker'),
                    'data'  => $buy_prices,
                    'borderColor' => 'rgba(75,192,192,1)',
                    'backgroundColor' => 'rgba(75,192,192,0.2)',
                    'fill' => false
                ],
                [
                    'label' => esc_html__('Sell Price', 'price-tracker'),
                    'data'  => $sell_prices,
                    'borderColor' => 'rgba(255,99,132,1)',
                    'backgroundColor' => 'rgba(255,99,132,0.2)',
                    'fill' => false
                ]
            ];

            $chart_data = [
                'labels' => $labels,
                'datasets' => $datasets
            ];
        } else {
            // Multi category case
            $categories = $wpdb->get_results($wpdb->prepare(
                "SELECT c.id, c.name 
             FROM $category_table c
             INNER JOIN $tax_cat_table tc ON c.id = tc.category_id
             WHERE tc.taxonomy_id=%d AND tc.status='active'",
                $taxonomy_id
            ));

            if (!$categories) wp_send_json_error(__('No categories found.', 'price-tracker'));

            $all_dates = [];
            $datasets = [];
            $category_data = [];


            if ($categories) {
                foreach ($categories as $index => $cat) {
                    $query = "SELECT i.date, i.sell_price
                  FROM $items_table i
                  INNER JOIN $tax_cat_table tc ON i.taxonomy_category_id = tc.id
                  WHERE tc.taxonomy_id=%d AND tc.category_id=%d $date_where
                  ORDER BY i.id DESC";

                    $items = $wpdb->get_results($wpdb->prepare($query, array_merge([$taxonomy_id, $cat->id], $params)));
                    if (!$items) continue;

                    // store category data
                    $category_data[$cat->id] = [
                        'name' => $cat->name,
                        'data' => []
                    ];

                    foreach ($items as $i) {
                        $date_str = date('Y-m-d', strtotime($i->date));
                        if (isset($category_data[$cat->id]['data'][$date_str])) {
                            continue; // skip duplicate dates
                        }
                        $category_data[$cat->id]['data'][$date_str] = floatval($i->sell_price);
                        $all_dates[$date_str] = true;
                    }
                }
            }

            // sort and normalize dates
            $all_dates = array_keys($all_dates);
            sort($all_dates);

            // build datasets aligned by all dates
            foreach ($category_data as $index => $cat_data) {
                $aligned_prices = [];
                foreach ($all_dates as $d) {
                    $aligned_prices[] = $cat_data['data'][$d] ?? null;
                }

                $datasets[] = [
                    'label' => $cat_data['name'],
                    'data'  => $aligned_prices,
                    'borderColor' => 'hsl(' . ($index * 60) . ',70%,50%)',
                    'backgroundColor' => 'hsl(' . ($index * 60) . ',70%,50%)',
                    'fill' => false
                ];
            }

            // chart data
            $chart_data = [
                'labels' => $all_dates,
                'datasets' => $datasets
            ];
        }

        wp_send_json_success($chart_data);
    }

    public function get_price_insight_callback()
    {
        check_ajax_referer('price_tracker_insight_nonce', 'nonce');
        global $wpdb;

        $taxonomy_slug = sanitize_text_field($_POST['taxonomy'] ?? '');
        $date          = sanitize_text_field($_POST['date'] ?? '');
        $range         = sanitize_text_field($_POST['range'] ?? '');

        // Validate taxonomy
        $taxonomy_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pt_taxonomy WHERE slug = %s",
            $taxonomy_slug
        ));
        if (!$taxonomy_id) {
            wp_send_json_error(['message' => __('Invalid taxonomy.', 'price-tracker')]);
        }

        /** -----------------------
         *  TABLE SECTION
         * ----------------------*/
        $categories = $wpdb->get_results($wpdb->prepare("
            SELECT c.id, c.name, tc.id AS taxonomy_category_id
            FROM {$wpdb->prefix}pt_category c
            INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc
                ON c.id = tc.category_id
            WHERE tc.taxonomy_id = %d
            AND tc.status = 'active'
            ORDER BY c.name ASC
        ", $taxonomy_id));

        ob_start();
        $is_there_something_to_show = false;

        if ($categories) {
            include PRICE_TRACKER_DIR . '/templates/price-table.php';
        } else {
            echo '<p>' . esc_html__('No categories found.', 'price-tracker') . '</p>';
        }

        $table_html = ob_get_clean();

        if (!$is_there_something_to_show) {
            $table_html = '<p>' . __("No data available for the selected filters.", "price-tracker") . '</p>';
        }

        /** -----------------------
         *  CHART SECTION
         * ----------------------*/
        $items_table    = $wpdb->prefix . 'pt_items';
        $tax_cat_table  = $wpdb->prefix . 'pt_taxonomy_category';
        $category_table = $wpdb->prefix . 'pt_category';

        $from_date = $date; // default to same date if no range
        if ($date && $range) {
            switch ($range) {
                case 'daily':
                    $from_date = date('Y-m-d', strtotime($date . ' -1 day'));
                    break;
                case 'weekly':
                    $from_date = date('Y-m-d', strtotime($date . ' -7 days'));
                    break;
                case 'monthly':
                    $from_date = date('Y-m-d', strtotime($date . ' -1 month'));
                    break;
                case 'yearly':
                    $from_date = date('Y-m-d', strtotime($date . ' -1 year'));
                    break;
            }
        }

        $date_where = '';
        $params = [];
        if ($from_date) {
            $date_where .= " AND i.date >= %s";
            $params[] = $from_date;
        }
        if ($date) {
            $date_where .= " AND i.date <= %s";
            $params[] = $date;
        }

        $all_dates = [];
        $datasets  = [];

        $categories = $wpdb->get_results($wpdb->prepare("
        SELECT c.id, c.name 
        FROM $category_table c
        INNER JOIN $tax_cat_table tc ON c.id = tc.category_id
        WHERE tc.taxonomy_id=%d AND tc.status='active'
        ", $taxonomy_id));

        $category_data = [];

        if ($categories) {
            foreach ($categories as $index => $cat) {
                $query = "SELECT i.date, i.sell_price
                  FROM $items_table i
                  INNER JOIN $tax_cat_table tc ON i.taxonomy_category_id = tc.id
                  WHERE tc.taxonomy_id=%d AND tc.category_id=%d $date_where
                  ORDER BY i.id DESC";

                $items = $wpdb->get_results($wpdb->prepare($query, array_merge([$taxonomy_id, $cat->id], $params)));
                if (!$items) continue;

                // store category data
                $category_data[$cat->id] = [
                    'name' => $cat->name,
                    'data' => []
                ];

                foreach ($items as $i) {
                    $date_str = date('Y-m-d', strtotime($i->date));
                    if (isset($category_data[$cat->id]['data'][$date_str])) {
                        continue; // skip duplicate dates
                    }
                    $category_data[$cat->id]['data'][$date_str] = floatval($i->sell_price);
                    $all_dates[$date_str] = true;
                }
            }
        }

        // sort and normalize dates
        $all_dates = array_keys($all_dates);
        sort($all_dates);

        // build datasets aligned by all dates
        foreach ($category_data as $index => $cat_data) {
            $aligned_prices = [];
            foreach ($all_dates as $d) {
                $aligned_prices[] = $cat_data['data'][$d] ?? null;
            }

            $datasets[] = [
                'label' => $cat_data['name'],
                'data'  => $aligned_prices,
                'borderColor' => 'hsl(' . ($index * 60) . ',70%,50%)',
                'backgroundColor' => 'hsl(' . ($index * 60) . ',70%,50%)',
                'fill' => false
            ];
        }

        // chart data
        $chart_data = [
            'labels' => $all_dates,
            'datasets' => $datasets
        ];

        /** -----------------------
         *  RESPONSE
         * ----------------------*/
        wp_send_json_success([
            'table_html' => $table_html,
            'chart' => $chart_data
        ]);
    }



    //===================================
    // CATEGORIES ACTIONS
    //===================================

    // === ADD CATEGORY ===
    function pt_ajax_add_category()
    {
        global $wpdb;
        check_ajax_referer('pt_category_nonce', 'security');
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to edit categories.', 'price-tracker')]);
        }

        $table_category = $wpdb->prefix . 'pt_category';
        $table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';

        $name = sanitize_text_field($_POST['name']);
        $slug = urldecode(sanitize_title($name));

        if (empty($name)) {
            wp_send_json_error(['message' => __('Category name is required.', 'price-tracker')]);
        }

        $wpdb->insert($table_category, [
            'name' => $name,
            'slug' => $slug,
        ]);

        $category_id = $wpdb->insert_id;

        if ($category_id && !empty($_POST['taxonomies']) && is_array($_POST['taxonomies'])) {
            foreach ($_POST['taxonomies'] as $taxonomy_id) {
                $wpdb->insert($table_tax_cat, [
                    'taxonomy_id' => intval($taxonomy_id),
                    'category_id' => $category_id,
                ]);
            }
        }

        wp_send_json_success([
            'message' => __('Category added successfully.', 'price-tracker'),
            'id'      => $category_id,
            'name'    => $name,
            'slug'    => $slug
        ]);
    }
    // === DELETE CATEGORY ===
    function pt_ajax_delete_category()
    {
        global $wpdb;
        check_ajax_referer('pt_category_nonce', 'security');

        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to edit categories.', 'price-tracker')]);
        }

        $table_category = $wpdb->prefix . 'pt_category';
        $id = intval($_POST['id']);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid category ID.', 'price-tracker')]);
        }

        $wpdb->delete($table_category, ['id' => $id]);

        wp_send_json_success(['message' => __('Category deleted successfully.', 'price-tracker')]);
    }

    // === EDIT CATEGORY ===
    function pt_ajax_edit_category()
    {
        global $wpdb;
        check_ajax_referer('pt_category_nonce', 'security');

        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to edit categories.', 'price-tracker')]);
        }

        $table_category = $wpdb->prefix . 'pt_category';
        $table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';

        $id   = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = urldecode(sanitize_title($name));

        if (!$id || empty($name)) {
            wp_send_json_error(['message' => __('Invalid data.', 'price-tracker')]);
        }

        // Update category itself
        $wpdb->update(
            $table_category,
            ['name' => $name, 'slug' => $slug],
            ['id' => $id]
        );

        // ---- Sync taxonomies ----
        $new_taxonomies = !empty($_POST['taxonomies']) && is_array($_POST['taxonomies'])
            ? array_map('intval', $_POST['taxonomies'])
            : [];

        // Current mappings (taxonomy_id => status)
        $current = $wpdb->get_results(
            $wpdb->prepare("SELECT taxonomy_id, status FROM $table_tax_cat WHERE category_id = %d", $id),
            OBJECT_K
        );

        // Reactivate or insert
        foreach ($new_taxonomies as $taxonomy_id) {
            if (isset($current[$taxonomy_id])) {
                // If inactive → activate it
                if ($current[$taxonomy_id]->status !== 'active') {
                    $wpdb->update(
                        $table_tax_cat,
                        ['status' => 'active'],
                        ['taxonomy_id' => $taxonomy_id, 'category_id' => $id]
                    );
                }
                unset($current[$taxonomy_id]); // processed
            } else {
                // New relation
                $wpdb->insert($table_tax_cat, [
                    'taxonomy_id' => $taxonomy_id,
                    'category_id' => $id,
                    'status'      => 'active'
                ]);
            }
        }

        // Any remaining in $current that are not in $new_taxonomies → deactivate
        foreach ($current as $taxonomy_id => $row) {
            if ($row->status === 'active') {
                $wpdb->update(
                    $table_tax_cat,
                    ['status' => 'inactive'],
                    ['taxonomy_id' => $taxonomy_id, 'category_id' => $id]
                );
            }
        }

        wp_send_json_success([
            'message' => __('Category updated successfully.', 'price-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
    }


    //============================
    // TAXONOMIES ACTIONS
    //============================

    // === ADD TAXONOMY ===
    function pt_ajax_add_taxonomy()
    {
        global $wpdb;
        check_ajax_referer('pt_taxonomy_nonce', 'security');

        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to add taxonomies.', 'price-tracker')]);
        }

        $table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
        $table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';

        $name = sanitize_text_field($_POST['name']);
        $slug = urldecode(sanitize_title($name));

        if (empty($name)) {
            wp_send_json_error(['message' => __('Taxonomy name is required.', 'price-tracker')]);
        }

        // Insert taxonomy
        $wpdb->insert($table_taxonomy, [
            'name' => $name,
            'slug' => $slug,
        ]);

        $taxonomy_id = $wpdb->insert_id;

        // Assign categories if provided
        if ($taxonomy_id && !empty($_POST['categories']) && is_array($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat_id) {
                $wpdb->insert($table_tax_cat, [
                    'taxonomy_id' => $taxonomy_id,
                    'category_id' => intval($cat_id),
                    'status'      => 'active'
                ]);
            }
        }

        wp_send_json_success([
            'message' => __('Taxonomy added successfully.', 'price-tracker'),
            'id'      => $taxonomy_id,
            'name'    => $name,
            'slug'    => $slug
        ]);
    }

    // === DELETE TAXONOMY ===
    function pt_ajax_delete_taxonomy()
    {
        global $wpdb;
        check_ajax_referer('pt_taxonomy_nonce', 'security');

        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to delete taxonomies.', 'price-tracker')]);
        }

        $table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
        $id = intval($_POST['id']);

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid taxonomy ID.', 'price-tracker')]);
        }

        $wpdb->delete($table_taxonomy, ['id' => $id]);

        wp_send_json_success(['message' => __('Taxonomy deleted successfully.', 'price-tracker')]);
    }

    // === EDIT TAXONOMY ===
    function pt_ajax_edit_taxonomy()
    {
        global $wpdb;
        check_ajax_referer('pt_taxonomy_nonce', 'security');

        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to edit taxonomies.', 'price-tracker')]);
        }

        $table_taxonomy = $wpdb->prefix . 'pt_taxonomy';
        $table_tax_cat  = $wpdb->prefix . 'pt_taxonomy_category';

        $id   = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = urldecode(sanitize_title($name));

        if (!$id || empty($name)) {
            wp_send_json_error(['message' => __('Invalid data.', 'price-tracker')]);
        }

        // Update taxonomy itself
        $wpdb->update(
            $table_taxonomy,
            ['name' => $name, 'slug' => $slug],
            ['id' => $id]
        );

        // Sync categories
        $new_categories = !empty($_POST['categories']) && is_array($_POST['categories'])
            ? array_map('intval', $_POST['categories'])
            : [];

        $current = $wpdb->get_results(
            $wpdb->prepare("SELECT category_id, status FROM $table_tax_cat WHERE taxonomy_id = %d", $id),
            OBJECT_K
        );

        // Reactivate or insert
        foreach ($new_categories as $cat_id) {
            if (isset($current[$cat_id])) {
                if ($current[$cat_id]->status !== 'active') {
                    $wpdb->update(
                        $table_tax_cat,
                        ['status' => 'active'],
                        ['taxonomy_id' => $id, 'category_id' => $cat_id]
                    );
                }
                unset($current[$cat_id]);
            } else {
                $wpdb->insert($table_tax_cat, [
                    'taxonomy_id' => $id,
                    'category_id' => $cat_id,
                    'status'      => 'active'
                ]);
            }
        }

        // Deactivate removed
        foreach ($current as $cat_id => $row) {
            if ($row->status === 'active') {
                $wpdb->update(
                    $table_tax_cat,
                    ['status' => 'inactive'],
                    ['taxonomy_id' => $id, 'category_id' => $cat_id]
                );
            }
        }

        wp_send_json_success([
            'message' => __('Taxonomy updated successfully.', 'price-tracker'),
            'id'      => $id,
            'name'    => $name,
            'slug'    => $slug,
        ]);
    }


    //============================
    // ITEMS ACTIONS
    //============================

    // === ADD ITEM ===
    function pt_ajax_add_item()
    {
        global $wpdb;

        // Security check
        check_ajax_referer('pt_item_nonce', 'security');

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to add items.', 'price-tracker')]);
        }

        $table_items = $wpdb->prefix . 'pt_items';
        $table_tc    = $wpdb->prefix . 'pt_taxonomy_category';

        $taxonomy_category_id = intval($_POST['taxonomy_category_id']);
        $buy_price = isset($_POST['buy_price']) ? floatval($_POST['buy_price']) : 0;
        $sell_price = isset($_POST['sell_price']) ? floatval($_POST['sell_price']) : 0;
        $date = sanitize_text_field($_POST['price_date']);

        // Validate required fields
        if (empty($taxonomy_category_id) || empty($date)) {
            wp_send_json_error(['message' => __('Category and date are required.', 'price-tracker')]);
        }

        // Verify taxonomy category exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_tc WHERE id = %d", $taxonomy_category_id)
        );

        if (!$exists) {
            wp_send_json_error(['message' => __('Invalid taxonomy category.', 'price-tracker')]);
        }

        // Insert new item
        $inserted = $wpdb->insert(
            $table_items,
            [
                'taxonomy_category_id' => $taxonomy_category_id,
                'buy_price'            => $buy_price,
                'sell_price'           => $sell_price,
                'date'                 => $date,
            ],
            ['%d', '%f', '%f', '%s']
        );

        if ($inserted === false) {
            wp_send_json_error(['message' => __('Failed to add item. Please try again.', 'price-tracker')]);
        }

        $item_id = $wpdb->insert_id;

        wp_send_json_success([
            'message' => __('Item added successfully.', 'price-tracker'),
            'id'      => $item_id,
            'taxonomy_category_id' => $taxonomy_category_id,
            'buy_price' => $buy_price,
            'sell_price' => $sell_price,
            'date' => $date,
        ]);
    }


    // === DELETE ITEM ===
    function pt_ajax_delete_item()
    {
        global $wpdb;
        $table_items = $wpdb->prefix . 'pt_items';

        // Security check
        check_ajax_referer('pt_item_nonce', 'security');

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to delete items.', 'price-tracker')]);
        }

        $id = intval($_POST['item_id'] ?? 0);
        if ($id) {
            $wpdb->delete($table_items, ['id' => $id], ['%d']);
        }

        wp_send_json_success([
            'message' => __('Item Removed successfully.', 'price-tracker')
        ]);
        exit;
    }


    // === EDIT ITEM ===
    function pt_ajax_edit_item()
    {
        global $wpdb;
        $table_items = $wpdb->prefix . 'pt_items';

        check_ajax_referer('pt_item_nonce', 'security');

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to edit items.', 'price-tracker')]);
        }

        $id = intval($_POST['item_id']);
        $buy_price = floatval($_POST['buy_price']);
        $sell_price = floatval($_POST['sell_price']);
        $date = sanitize_text_field($_POST['price_date']);

        if ($id) {
            $wpdb->update(
                $table_items,
                [
                    'buy_price' => $buy_price,
                    'sell_price' => $sell_price,
                    'date' => $date,
                ],
                ['id' => $id],
                ['%f', '%f', '%s'],
                ['%d']
            );
        }

        wp_send_json_success([
            'message' => __('Item Edited successfully.', 'price-tracker')
        ]);
        exit;
    }



    //============================
    // UNASSIGNED ACTIONS
    //============================

    // === DELETE UNASSIGNED ===
    function price_tracker_delete_unassigned()
    {
        check_ajax_referer('pt_unassigned_nonce', 'nonce');

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to delete unassigned items.', 'price-tracker')]);
        }


        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(__('Invalid relation ID.', 'price-tracker'));
        }

        global $wpdb;
        $table_tax_cat = $wpdb->prefix . 'pt_taxonomy_category';

        $deleted = $wpdb->delete($table_tax_cat, ['id' => $id], ['%d']);

        if ($deleted) {
            wp_send_json_success(['id' => $id, 'message' => __('Relation permanently deleted.', 'price-tracker')]);
        } else {
            wp_send_json_error(__('Failed to delete relation.', 'price-tracker'));
        }
    }

    // === REASSIGN UNASSIGNED ===
    function price_tracker_reassign_unassigned()
    {
        check_ajax_referer('pt_unassigned_nonce', 'nonce');

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to re-assign unassigned items.', 'price-tracker')]);
        }


        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(__('Invalid relation ID.', 'price-tracker'));
        }

        global $wpdb;
        $table_tax_cat = $wpdb->prefix . 'pt_taxonomy_category';

        $updated = $wpdb->update(
            $table_tax_cat,
            ['status' => 'active'],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success(['id' => $id, 'message' => __('Relation re-assigned successfully.', 'price-tracker')]);
        } else {
            wp_send_json_error(__('Failed to re-assign relation.', 'price-tracker'));
        }
    }



    // === EXPORT CSV ===
    function price_tracker_export_csv()
    {
        // Security check
        if (
            !isset($_POST['price_tracker_export_csv_nonce']) ||
            !wp_verify_nonce($_POST['price_tracker_export_csv_nonce'], 'price_tracker_export_csv')
        ) {
            wp_die(__('Security check failed.', 'price-tracker'));
        }
        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_send_json_error(['message' => __('You do not have permission to export CSV.', 'price-tracker')]);
        }


        global $wpdb;

        $taxonomy_table = $wpdb->prefix . 'pt_taxonomy';
        $category_table = $wpdb->prefix . 'pt_category';
        $tax_cat_table  = $wpdb->prefix . 'pt_taxonomy_category';
        $items_table    = $wpdb->prefix . 'pt_items';

        // === FILTER HANDLING ===
        $from_date = sanitize_text_field($_POST['from_date'] ?? '');
        $to_date   = sanitize_text_field($_POST['to_date'] ?? '');
        $taxonomy  = intval($_POST['filter_taxonomy'] ?? 0);
        $category  = intval($_POST['filter_category'] ?? 0);

        $where = "1=1";
        $args  = [];

        if ($taxonomy) {
            $where .= " AND tc.taxonomy_id = %d";
            $args[] = $taxonomy;
        }

        if ($category) {
            $where .= " AND tc.category_id = %d";
            $args[] = $category;
        }

        if ($from_date) {
            $where .= " AND i.date >= %s";
            $args[] = $from_date;
        }

        if ($to_date) {
            $where .= " AND i.date <= %s";
            $args[] = $to_date;
        }

        // Build query
        $sql = "
            SELECT i.id as item_id, t.name as taxonomy, c.name as category, 
                i.buy_price, i.sell_price, i.date, tc.status
            FROM $items_table i
            INNER JOIN $tax_cat_table tc ON i.taxonomy_category_id = tc.id
            INNER JOIN $taxonomy_table t ON tc.taxonomy_id = t.id
            INNER JOIN $category_table c ON tc.category_id = c.id
            WHERE $where
            ORDER BY i.date DESC
        ";

        $results = !empty($args) ? $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);


        if (empty($results)) {
            wp_die(__('No data available for export.', 'price-tracker'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=price-tracker-data-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Item ID', 'Taxonomy', 'Category', 'Buy Price', 'Sell Price', 'Date', 'Status'));

        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    // === IMPORT CSV ===
    function price_tracker_import_csv()
    {
        // Security check
        if (
            !isset($_POST['price_tracker_import_csv_nonce']) ||
            !wp_verify_nonce($_POST['price_tracker_import_csv_nonce'], 'price_tracker_import_csv')
        ) {
            wp_die(__('Security check failed.', 'price-tracker'));
        }

        // Permissions check
        if (!current_user_can('administrator') && !current_user_can('price_tracker_access')) {
            wp_die(__('You do not have permission to import CSV.', 'price-tracker'));
        }

        // File uploaded check
        if (empty($_FILES['import_csv']['tmp_name'])) {
            wp_die(__('No file uploaded.', 'price-tracker'));
        }

        // Validate file type
        $filetype = wp_check_filetype($_FILES['import_csv']['name']);
        if ($filetype['ext'] !== 'csv' || $filetype['type'] !== 'text/csv') {
            wp_die(__('Invalid file type. Please upload a CSV file.', 'price-tracker'));
        }

        // Validate file size (max 5MB)
        if ($_FILES['import_csv']['size'] > 5 * 1024 * 1024) {
            wp_die(__('The uploaded file is too large. Maximum size is 5MB.', 'price-tracker'));
        }

        global $wpdb;

        $taxonomy_table = $wpdb->prefix . 'pt_taxonomy';
        $category_table = $wpdb->prefix . 'pt_category';
        $tax_cat_table  = $wpdb->prefix . 'pt_taxonomy_category';
        $items_table    = $wpdb->prefix . 'pt_items';

        $imported = 0;
        $skipped  = 0;

        if (($handle = fopen($_FILES['import_csv']['tmp_name'], 'r')) !== false) {
            // Skip header
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                // Expected: Taxonomy, Category, Buy Price, Sell Price, Date, Status
                [$taxonomy_name, $category_name, $buy_price, $sell_price, $date, $status] = array_pad($row, 6, '');

                // Sanitize values
                $taxonomy_name = sanitize_text_field($taxonomy_name);
                $taxonomy_slug = urldecode(sanitize_title($taxonomy_name));
                $category_name = sanitize_text_field($category_name);
                $category_slug = urldecode(sanitize_title($category_name));
                $buy_price     = floatval($buy_price);
                $sell_price    = floatval($sell_price);
                $date          = preg_replace('/[^0-9\-]/', '', $date); // only allow YYYY-MM-DD
                $status        = in_array(strtolower($status), ['active', 'inactive'], true) ? strtolower($status) : 'active';

                // Skip if taxonomy or category missing
                if (empty($taxonomy_name) || empty($category_name)) {
                    $skipped++;
                    continue;
                }

                // Find or insert taxonomy
                $taxonomy_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $taxonomy_table WHERE name = %s LIMIT 1",
                    $taxonomy_name
                ));
                if (!$taxonomy_id) {
                    $wpdb->insert($taxonomy_table, ['name' => $taxonomy_name, 'slug' => $taxonomy_slug]);
                    $taxonomy_id = $wpdb->insert_id;
                }

                // Find or insert category
                $category_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $category_table WHERE name = %s LIMIT 1",
                    $category_name
                ));
                if (!$category_id) {
                    $wpdb->insert($category_table, ['name' => $category_name, 'slug' => $category_slug]);
                    $category_id = $wpdb->insert_id;
                }

                // Find or insert taxonomy_category relation
                $tax_cat_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tax_cat_table WHERE taxonomy_id = %d AND category_id = %d LIMIT 1",
                    $taxonomy_id,
                    $category_id
                ));
                if (!$tax_cat_id) {
                    $wpdb->insert($tax_cat_table, [
                        'taxonomy_id' => $taxonomy_id,
                        'category_id' => $category_id,
                        'status'      => $status,
                    ]);
                    $tax_cat_id = $wpdb->insert_id;
                }

                // Insert item
                $wpdb->insert($items_table, [
                    'taxonomy_category_id' => $tax_cat_id,
                    'buy_price'            => $buy_price,
                    'sell_price'           => $sell_price,
                    'date'                 => $date,
                ]);

                $imported++;
            }

            fclose($handle);
        }

        // Redirect back with notice
        $redirect_url = add_query_arg([
            'page'     => 'price-tracker',
            'tab'      => 'export_import',
            'imported' => $imported,
            'skipped'  => $skipped,
        ], admin_url('admin.php'));

        wp_safe_redirect($redirect_url);
        exit;
    }
}
