<?php
namespace SanadTracker\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addSettingPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting('sanad_tracker_settings_group', 'sanad_tracker_delete_on_uninstall');
    }

    public function addSettingPage(): void
    {
        add_menu_page(
            __('Region & Land Price Tracker', 'sanad-tracker'),
            __('Sanad Tracker', 'sanad-tracker'),
            'sanad_tracker_access',
            'sanad-tracker',
            [$this, 'renderSettingsPage'],
            'dashicons-location',
            30
        );
    }

    public function renderSettingsPage(): void
    {
        if (!current_user_can('sanad_tracker_access')) {
            wp_die(__('Unauthorized.', 'sanad-tracker'));
        }

        $currentTab = sanitize_text_field($_GET['tab'] ?? 'general');

        $tabs = [
            'general'         => __('General', 'sanad-tracker'),
            'regions'         => __('Regions', 'sanad-tracker'),
            'materials'       => __('Materials', 'sanad-tracker'),
            'material_prices' => __('Material Prices', 'sanad-tracker'),
            'land_prices'     => __('Land Prices', 'sanad-tracker'),
        ];

        $tabClassMap = [
            'general'         => 'SanadTracker\Admin\Tabs\GeneralTab',
            'regions'         => 'SanadTracker\Admin\Tabs\RegionsTab',
            'materials'       => 'SanadTracker\Admin\Tabs\MaterialsTab',
            'material_prices' => 'SanadTracker\Admin\Tabs\MaterialPricesTab',
            'land_prices'     => 'SanadTracker\Admin\Tabs\LandPricesTab',
        ];

        ob_start();
        ?>
        <div class="wrap sanad-admin-wrap">
            <h1><?php esc_html_e('Region & Land Price Tracker', 'sanad-tracker'); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab => $label): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sanad-tracker&tab=' . $tab)); ?>"
                       class="nav-tab <?php echo $currentTab === $tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sanad-tab-container">
                <?php
                if (isset($tabClassMap[$currentTab]) && class_exists($tabClassMap[$currentTab])) {
                    $tabInstance = new $tabClassMap[$currentTab]();
                    echo $tabInstance->render();
                } else {
                    $defaultTab = new \SanadTracker\Admin\Tabs\RegionsTab();
                    echo $defaultTab->render();
                }
                ?>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('sanad_tracker_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Uninstall Settings', 'sanad-tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sanad_tracker_delete_on_uninstall" value="1" <?php checked(get_option('sanad_tracker_delete_on_uninstall', 0), 1); ?>>
                                <?php esc_html_e('Delete all data on uninstall', 'sanad-tracker'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        echo ob_get_clean();
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_sanad-tracker') {
            return;
        }

        wp_enqueue_style(
            'sanad-tracker-admin-global',
            SANAD_TRACKER_URL . 'assets/css/admin/admin-global.css',
            [],
            SANAD_TRACKER_VERSION
        );

        wp_enqueue_style(
            'sanad-tracker-prices-tab',
            SANAD_TRACKER_URL . 'assets/css/admin/prices-tab.css',
            [],
            SANAD_TRACKER_VERSION,
            'all'
        );

        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [], null, true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], null, 'all');

        $jsDeps = ['jquery', 'sweetalert2', 'select2'];

        wp_enqueue_script(
            'sanad-tracker-regions-ajax',
            SANAD_TRACKER_URL . 'assets/js/admin/regions-ajax.js',
            $jsDeps,
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-regions-ajax', 'SanadTrackerRegionsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sanad_tracker_regions_nonce'),
            'i18n'     => [
                'added'                 => __('Added!', 'sanad-tracker'),
                'updated'               => __('Updated!', 'sanad-tracker'),
                'deleted'               => __('Deleted!', 'sanad-tracker'),
                'error'                 => __('Error', 'sanad-tracker'),
                'confirm_delete_title'  => __('Are you sure?', 'sanad-tracker'),
                'confirm_delete_text'   => __('This region will be permanently deleted along with all its prices.', 'sanad-tracker'),
                'confirm_delete_yes'    => __('Yes, delete it!', 'sanad-tracker'),
                'cancel'                => __('Cancel', 'sanad-tracker'),
            ],
        ]);

        wp_enqueue_script(
            'sanad-tracker-materials-ajax',
            SANAD_TRACKER_URL . 'assets/js/admin/materials-ajax.js',
            $jsDeps,
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-materials-ajax', 'SanadTrackerMaterialsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sanad_tracker_materials_nonce'),
            'i18n'     => [
                'added'                => __('Added!', 'sanad-tracker'),
                'updated'              => __('Updated!', 'sanad-tracker'),
                'deleted'              => __('Deleted!', 'sanad-tracker'),
                'error'                => __('Error', 'sanad-tracker'),
                'confirm_delete_title' => __('Are you sure?', 'sanad-tracker'),
                'confirm_delete_text'  => __('This material will be permanently deleted.', 'sanad-tracker'),
                'confirm_delete_yes'   => __('Yes, delete it!', 'sanad-tracker'),
                'cancel'               => __('Cancel', 'sanad-tracker'),
            ],
        ]);

        wp_enqueue_script(
            'sanad-tracker-material-prices-ajax',
            SANAD_TRACKER_URL . 'assets/js/admin/material-prices-ajax.js',
            $jsDeps,
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-material-prices-ajax', 'SanadTrackerMaterialPricesAjax', [
            'ajax_url'        => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('sanad_tracker_material_prices_nonce'),
            'materials_nonce' => wp_create_nonce('sanad_tracker_materials_nonce'),
            'i18n'            => [
                'select_region'          => __('Select a region', 'sanad-tracker'),
                'loading'                => __('Loading...', 'sanad-tracker'),
                'no_materials'           => __('No materials found for this region.', 'sanad-tracker'),
                'price_placeholder'      => __('Enter price', 'sanad-tracker'),
                'no_prices'              => __('Please enter at least one price.', 'sanad-tracker'),
                'warning'                => __('Warning', 'sanad-tracker'),
                'saved'                  => __('Saved!', 'sanad-tracker'),
                'deleted'                => __('Deleted!', 'sanad-tracker'),
                'error'                  => __('Error', 'sanad-tracker'),
                'confirm_delete_title'   => __('Are you sure?', 'sanad-tracker'),
                'confirm_delete_text'    => __('This price entry will be permanently deleted.', 'sanad-tracker'),
                'confirm_delete_yes'     => __('Yes, delete it!', 'sanad-tracker'),
                'cancel'                 => __('Cancel', 'sanad-tracker'),
                'no_entries'             => __('No price entries found.', 'sanad-tracker'),
                'delete'                 => __('Delete', 'sanad-tracker'),
                'edit'                   => __('Edit', 'sanad-tracker'),
                'save'                   => __('Save', 'sanad-tracker'),
                'confirm_delete_date_title' => __('Delete all entries?', 'sanad-tracker'),
                'confirm_delete_date_text'  => __('All prices for this date will be permanently deleted.', 'sanad-tracker'),
                'deleting'               => __('Deleting...', 'sanad-tracker'),
                'saving'                 => __('Saving...', 'sanad-tracker'),
            ],
        ]);

        wp_enqueue_script(
            'sanad-tracker-land-prices-ajax',
            SANAD_TRACKER_URL . 'assets/js/admin/land-prices-ajax.js',
            $jsDeps,
            SANAD_TRACKER_VERSION,
            true
        );

        wp_localize_script('sanad-tracker-land-prices-ajax', 'SanadTrackerLandPricesAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sanad_tracker_land_prices_nonce'),
            'i18n'     => [
                'select_region'        => __('Select a region', 'sanad-tracker'),
                'loading'              => __('Loading...', 'sanad-tracker'),
                'saved'                => __('Saved!', 'sanad-tracker'),
                'deleted'              => __('Deleted!', 'sanad-tracker'),
                'error'                => __('Error', 'sanad-tracker'),
                'confirm_delete_title' => __('Are you sure?', 'sanad-tracker'),
                'confirm_delete_text'  => __('This price entry will be permanently deleted.', 'sanad-tracker'),
                'confirm_delete_yes'   => __('Yes, delete it!', 'sanad-tracker'),
                'cancel'               => __('Cancel', 'sanad-tracker'),
                'no_entries'           => __('No price entries found.', 'sanad-tracker'),
                'delete'               => __('Delete', 'sanad-tracker'),
                'warning'              => __('Warning', 'sanad-tracker'),
                'edit'                 => __('Edit', 'sanad-tracker'),
                'save'                 => __('Save', 'sanad-tracker'),
                'saving'               => __('Saving...', 'sanad-tracker'),
            ],
        ]);

    }
}
