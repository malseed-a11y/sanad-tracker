<?php

namespace PriceTrackerNamespace\classes;

if (!defined('ABSPATH')) {
    die;
}

if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use WP_List_Table;

class PTItemsTable extends WP_List_Table
{
    private $items_data;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'item',
            'plural'   => 'items',
            'ajax'     => false
        ]);
    }

    /** Prepare data + pagination */
    public function prepare_items()
    {
        global $wpdb;

        $per_page     = 10;
        $current_page = $this->get_pagenum();

        // === Sorting handling ===
        $orderby = sanitize_key($_GET['orderby'] ?? 'id');
        $order   = strtoupper(sanitize_text_field($_GET['order'] ?? 'DESC'));

        $allowed = array_keys($this->get_sortable_columns());
        if (!in_array($orderby, $allowed, true)) {
            $orderby = 'date';
        }
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';


        // === FILTER HANDLING ===
        $filter_taxonomy_id = intval($_GET['filter_taxonomy'] ?? 0);
        $filter_category_id = intval($_GET['filter_category'] ?? 0);
        $filter_date        = sanitize_text_field($_GET['filter_date'] ?? '');

        $where = "tc.status = 'active'";
        $args  = [];

        if ($filter_taxonomy_id) {
            $where .= " AND tc.taxonomy_id = %d";
            $args[] = $filter_taxonomy_id;
        }
        if ($filter_category_id) {
            $where .= " AND tc.category_id = %d";
            $args[] = $filter_category_id;
        }
        if ($filter_date) {
            $where .= " AND i.date = %s";
            $args[] = $filter_date;
        }

        // total items count
        $count_query = "SELECT COUNT(*) 
            FROM {$wpdb->prefix}pt_items i
            INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc ON i.taxonomy_category_id = tc.id
            WHERE $where";

        $total_items = !empty($args)
            ? $wpdb->get_var($wpdb->prepare($count_query, ...$args))
            : $wpdb->get_var($count_query);

        // fetch items with taxonomy/category names
        $data_query = "
            SELECT i.id, t.name AS taxonomy_name, c.name AS category_name,
                i.buy_price, i.sell_price, i.date
            FROM {$wpdb->prefix}pt_items i
            INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc ON i.taxonomy_category_id = tc.id
            INNER JOIN {$wpdb->prefix}pt_taxonomy t ON tc.taxonomy_id = t.id
            INNER JOIN {$wpdb->prefix}pt_category c ON tc.category_id = c.id
            WHERE $where
            ORDER BY i.$orderby $order
            LIMIT %d OFFSET %d
        ";


        $args_with_limits = array_merge($args, [$per_page, ($current_page - 1) * $per_page]);

        $this->items_data = !empty($args)
            ? $wpdb->get_results($wpdb->prepare($data_query, ...$args_with_limits), ARRAY_A)
            : $wpdb->get_results($wpdb->prepare($data_query, $per_page, ($current_page - 1) * $per_page), ARRAY_A);

        // Required: set headers
        $columns  = $this->get_columns();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, [], $sortable];

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->items = $this->items_data;
    }

    /** Add filter dropdowns above the table */
    public function extra_tablenav($which)
    {
        global $wpdb;

        if ($which === 'top') {
            // Fetch taxonomies and categories for filters
            $taxonomies = $wpdb->get_results("
                SELECT DISTINCT t.id, t.name
                FROM {$wpdb->prefix}pt_taxonomy t
                INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc ON tc.taxonomy_id = t.id
                WHERE tc.status = 'active'
            ");

            $categories = $wpdb->get_results("
                SELECT DISTINCT c.id, c.name
                FROM {$wpdb->prefix}pt_category c
                INNER JOIN {$wpdb->prefix}pt_taxonomy_category tc ON tc.category_id = c.id
                WHERE tc.status = 'active'
            ");


            $selected_taxonomy = intval($_GET['filter_taxonomy'] ?? 0);
            $selected_category = intval($_GET['filter_category'] ?? 0);
            $selected_date     = esc_attr($_GET['filter_date'] ?? '');
?>
     <form method="get">
            <input type="hidden" name="page" value="price-tracker">
            <input type="hidden" name="tab" value="items">
            <div class="alignleft actions">
                <select name="filter_taxonomy">
                    <option value=""><?php esc_html_e('-- All Taxonomies --', 'price-tracker'); ?></option>
                    <?php foreach ($taxonomies as $t): ?>
                        <option value="<?php echo esc_attr($t->id); ?>" <?php selected($selected_taxonomy, $t->id); ?>>
                            <?php echo esc_html($t->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_category">
                    <option value=""><?php esc_html_e('-- All Categories --', 'price-tracker'); ?></option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo esc_attr($c->id); ?>" <?php selected($selected_category, $c->id); ?>>
                            <?php echo esc_html($c->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="filter_date" value="<?php echo $selected_date; ?>" />

                <?php submit_button(__('Filter'), '', 'filter_action', false); ?>
            </div>
        </form>
<?php
        }
    }

    /** Define columns */
    public function get_columns()
    {
        return [
            'id'            => __('ID', 'price-tracker'),
            'taxonomy_name' => __('Taxonomy', 'price-tracker'),
            'category_name' => __('Category', 'price-tracker'),
            'buy_price'     => __('Buy Price', 'price-tracker'),
            'sell_price'    => __('Sell Price', 'price-tracker'),
            'date'          => __('Date', 'price-tracker'),
            'actions'       => __('Actions', 'price-tracker'),
        ];
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'taxonomy_name':
            case 'category_name':
            case 'buy_price':
            case 'sell_price':
            case 'date':
                return esc_html($item[$column_name]);
            case 'actions':
                return sprintf(
                    '<button class="edit-item button-secondary" 
                        data-item-id="%d"
                        data-buy-price="%s"
                        data-sell-price="%s"
                        data-date="%s">%s</button>
                     <button class="delete-item button-secondary" 
                        data-item-id="%d">%s</button>',
                    $item['id'],
                    esc_attr($item['buy_price']),
                    esc_attr($item['sell_price']),
                    esc_attr($item['date']),
                    __('Edit', 'price-tracker'),
                    $item['id'],
                    __('Delete', 'price-tracker')
                );
            default:
                return '';
        }
    }

    function get_sortable_columns()
    {
        return [
            'id'        => ['id', true],
            'date'      => ['date', true],
            'buy_price' => ['buy_price', false],
            'sell_price' => ['sell_price', false],
        ];
    }

    public function no_items()
    {
        _e('No items found.', 'price-tracker');
    }
}
