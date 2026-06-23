<?php

namespace PriceTrackerNamespace\settings;

if (!defined('ABSPATH')) {
    die;
}

class SettingsPage
{
    private $default_tab = 'general';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_setting_page'));
        add_action('admin_init', array($this, 'register_settings_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_settings_fields()
    {
        // General settings
        register_setting('price-tracker-general-settings-group', 'price-tracker-delete-data-on-uninstall');
        register_setting('price-tracker-general-settings-group', 'price-tracker-allowed-roles', ['type' => 'array', 'sanitize_callback' => [$this, 'price_tracker_sanitize_allowed_roles'], 'default' => array('administrator')]);

        // We could register other options later if needed for taxonomy/category/items settings
    }

    public function add_setting_page()
    {
        add_menu_page(
            esc_html__('Price Tracker', 'price-tracker'),
            esc_html__('Price Tracker', 'price-tracker'),
            'price_tracker_access',
            'price-tracker',
            array($this, 'settings_page_render'),
            'dashicons-chart-line'
        );
    }

    public function enqueue_styles($hook)
    {

        // Only load on your plugin page
        if ($hook !== 'toplevel_page_price-tracker') {
            return;
        }
        wp_enqueue_style('price-tracker-settings-page-styles', PRICE_TRACKER_URL . '/assets/css/settings.css');

        // Select2 CSS & JS
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);

        // SweetAlert2 CSS & JS
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [], null, true);



        wp_enqueue_script('price-tracker-admin-js', PRICE_TRACKER_URL . '/dist/js/admin/price-tracker-admin.min.js', ['jquery', 'select2-js', 'sweetalert2-js'], null, true);

        // Category Page Scripts
        wp_enqueue_script('categories-ajax-actions-js', PRICE_TRACKER_URL . '/dist/js/admin/categories-ajax-actions.min.js', ['jquery', 'select2-js', 'sweetalert2-js'], null, true);
        wp_localize_script('categories-ajax-actions-js', 'priceTrackerCategoryAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'category_nonce'    => wp_create_nonce('pt_category_nonce'),
        ]);

        // Taxonomy Page Scripts
        wp_enqueue_script('taxonomies-ajax-actions-js', PRICE_TRACKER_URL . '/dist/js/admin/taxonomies-ajax-actions.min.js', ['jquery', 'select2-js', 'sweetalert2-js'], null, true);
        wp_localize_script('taxonomies-ajax-actions-js', 'priceTrackerTaxonomyAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'taxonomy_nonce' => wp_create_nonce('pt_taxonomy_nonce'),
        ]);

        // Item Page Scripts
        wp_enqueue_script('items-ajax-actions-js', PRICE_TRACKER_URL . '/dist/js/admin/items-ajax-actions.min.js', ['jquery', 'select2-js', 'sweetalert2-js'], null, true);
        wp_localize_script('items-ajax-actions-js', 'priceTrackerItemAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'item_nonce' => wp_create_nonce('pt_item_nonce'),
        ]);

        if (isset($_GET['tab']) && sanitize_text_field($_GET['tab']) == 'items') {
            wp_enqueue_style('pt-items-page-style', PRICE_TRACKER_URL . '/dist/css/admin/items-page.min.css');
            wp_enqueue_script('items-page-js', PRICE_TRACKER_URL . '/dist/js/admin/items-page.min.js', ['jquery', 'select2-js'], null, true);
        }

        if (isset($_GET['tab']) && sanitize_text_field($_GET['tab']) == 'shortcode_maker') {
            wp_enqueue_style('pt-shortcode-maker-style', PRICE_TRACKER_URL . '/dist/css/admin/shortcode-maker.min.css');
            wp_enqueue_script('pt-shortcode-maker-js', PRICE_TRACKER_URL . '/dist/js/admin/shortcode-maker.min.js', [], null, true);
        }
        // Unassigned Page Scripts
        wp_enqueue_script('unassigned-ajax-actions-js', PRICE_TRACKER_URL . '/dist/js/admin/unassigned-ajax-actions.min.js', ['jquery', 'select2-js', 'sweetalert2-js'], null, true);
        wp_localize_script('unassigned-ajax-actions-js', 'priceTrackerUnassignedAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'unassigned_nonce' => wp_create_nonce('pt_unassigned_nonce'),
        ]);
    }



    private function admin_tabs($current = 'general')
    {
        $tabs = array(
            'general'  => esc_html__('General', 'price-tracker'),
            'taxonomy' => esc_html__('Taxonomy', 'price-tracker'),
            'category' => esc_html__('Category', 'price-tracker'),
            'items'    => esc_html__('Items', 'price-tracker'),
            'unassigned'    => esc_html__('Unassigned', 'price-tracker'),
            'export_import'    => esc_html__('Export/Import', 'price-tracker'),
            'shortcode_maker'    => esc_html__('Shortcode Maker', 'price-tracker'),
        );

        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=price-tracker&tab=$tab'>$name</a>";
        }
        echo '</h2>';
    }

    public function settings_page_render()
    {
        if (!current_user_can('price_tracker_access')) {
            return;
        }

        $current_page = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $this->default_tab;

        // Print Tabs
        $this->admin_tabs($current_page);
?>
        <div class="price-tracker-settings-container">
            <?php
            if ($current_page == 'general') { ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('price-tracker-general-settings-group');
                    require_once(__DIR__ . '/tabs/general.php');
                    submit_button();
                    ?>
                </form>
            <?php
            }

            if ($current_page == 'taxonomy') {
                require_once(__DIR__ . '/tabs/taxonomy.php');
            }

            if ($current_page == 'category') {
                require_once(__DIR__ . '/tabs/category.php');
            }

            if ($current_page == 'items') {
                require_once(__DIR__ . '/tabs/items.php');
            }

            if ($current_page == 'unassigned') {
                require_once(__DIR__ . '/tabs/unassigned.php');
            }
            if ($current_page == 'export_import') {
                require_once(__DIR__ . '/tabs/export-import.php');
            }
            if ($current_page == 'shortcode_maker') {
                require_once(__DIR__ . '/tabs/shortcode-maker.php');
            }
            ?>
        </div>
<?php
    }

    public function price_tracker_sanitize_allowed_roles($roles)
    {
        if (!current_user_can('manage_options')) {
            return get_option('price_tracker_allowed_roles', ['administrator']);
        }

        global $wp_roles;

        // Ensure array
        $roles = is_array($roles) ? $roles : [];

        // Only allow real roles
        $valid_roles = array_keys($wp_roles->roles);
        $roles = array_intersect($roles, $valid_roles);

        // Always keep admin
        if (!in_array('administrator', $roles, true)) {
            $roles[] = 'administrator';
        }

        // 🔁 Sync capabilities
        foreach ($valid_roles as $role_key) {
            $role = get_role($role_key);
            if (!$role) {
                continue;
            }

            if (in_array($role_key, $roles, true)) {
                $role->add_cap('price_tracker_access');
            } else {
                $role->remove_cap('price_tracker_access');
            }
        }

        return array_values($roles);
    }
}
