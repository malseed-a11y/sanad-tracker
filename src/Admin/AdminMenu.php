<?php

namespace SanadTracker\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting('sanad_tracker_settings_group', 'sanad_tracker_delete_on_uninstall');
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Region & Land Price Tracker', 'sanad-tracker'),
            __('Sanad Tracker', 'sanad-tracker'),
            'sanad_tracker_access',
            'sanad-tracker',
            [$this, 'renderPage'],
            'dashicons-location',
            30
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can('sanad_tracker_access')) {
            wp_die(__('Unauthorized.', 'sanad-tracker'));
        }

        $currentTab = sanitize_text_field($_GET['tab'] ?? 'regions');

        $tabs = [
            'regions'         => __('Regions', 'sanad-tracker'),
            'materials'       => __('Materials', 'sanad-tracker'),
            'material_prices' => __('Material Prices', 'sanad-tracker'),
            'land_prices'     => __('Land Prices', 'sanad-tracker'),
            'shortcode_gen'   => __('Shortcode Generator', 'sanad-tracker'),
        ];

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
                $viewDir = __DIR__ . '/Views/';
                $viewMap = [
                    'regions'         => 'regions-tab.php',
                    'materials'       => 'materials-tab.php',
                    'material_prices' => 'material-prices-tab.php',
                    'land_prices'     => 'land-prices-tab.php',
                    'shortcode_gen'   => 'shortcode-gen-tab.php',
                ];

                $viewFile = $viewMap[$currentTab] ?? 'regions-tab.php';
                $viewPath = $viewDir . $viewFile;

                if (file_exists($viewPath)) {
                    $data = $this->getViewData($currentTab);
                    extract($data);
                    require $viewPath;
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
    }

    private function getViewData(string $tab): array
    {
        $data = [];

        switch ($tab) {
            case 'regions':
                $repo = new \SanadTracker\Database\RegionRepository();
                $data['regions'] = $repo->getAll();
                break;

            case 'materials':
                $repo = new \SanadTracker\Database\MaterialRepository();
                $data['materials'] = $repo->getAll();
                break;

            case 'material_prices':
                $regionRepo = new \SanadTracker\Database\RegionRepository();
                $materialRepo = new \SanadTracker\Database\MaterialRepository();
                $data['regions'] = $regionRepo->getAllOrderedByName();
                $data['materials'] = $materialRepo->getAllOrderedByName();
                break;

            case 'land_prices':
                $repo = new \SanadTracker\Database\RegionRepository();
                $data['regions'] = $repo->getAllOrderedByName();
                break;

            case 'shortcode_gen':
            default:
                break;
        }

        return $data;
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_sanad-tracker') {
            return;
        }

        $currentTab = sanitize_text_field($_GET['tab'] ?? 'regions');

        wp_enqueue_style(
            'sanad-tracker-admin-base',
            SANAD_TRACKER_URL . 'assets/admin/css/admin-base.css',
            [],
            SANAD_TRACKER_VERSION
        );

        $tabCssMap = [
            'regions'         => 'regions.css',
            'materials'       => 'materials.css',
            'material_prices' => 'material-prices.css',
            'land_prices'     => 'land-prices.css',
            'shortcode_gen'   => 'shortcode-gen.css',
        ];

        if (isset($tabCssMap[$currentTab])) {
            wp_enqueue_style(
                'sanad-tracker-' . $currentTab,
                SANAD_TRACKER_URL . 'assets/admin/css/' . $tabCssMap[$currentTab],
                ['sanad-tracker-admin-base'],
                SANAD_TRACKER_VERSION
            );
        }

        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [], null, true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], null, 'all');

        $jsDeps = ['jquery', 'sweetalert2', 'select2'];

        $tabJsMap = [
            'regions' => [
                'handle' => 'sanad-tracker-regions',
                'file'   => 'regions.js',
                'vars'   => [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('sanad_tracker_regions_nonce'),
                    'i18n'     => [
                        'added'                => __('Added!', 'sanad-tracker'),
                        'updated'              => __('Updated!', 'sanad-tracker'),
                        'deleted'              => __('Deleted!', 'sanad-tracker'),
                        'error'                => __('Error', 'sanad-tracker'),
                        'confirm_delete_title' => __('Are you sure?', 'sanad-tracker'),
                        'confirm_delete_text'  => __('This region will be permanently deleted along with all its prices.', 'sanad-tracker'),
                        'confirm_delete_yes'   => __('Yes, delete it!', 'sanad-tracker'),
                        'cancel'               => __('Cancel', 'sanad-tracker'),
                    ],
                ],
            ],
            'materials' => [
                'handle' => 'sanad-tracker-materials',
                'file'   => 'materials.js',
                'vars'   => [
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
                ],
            ],
            'material_prices' => [
                'handle' => 'sanad-tracker-material-prices',
                'file'   => 'material-prices.js',
                'vars'   => [
                    'ajax_url'        => admin_url('admin-ajax.php'),
                    'nonce'           => wp_create_nonce('sanad_tracker_material_prices_nonce'),
                    'materials_nonce' => wp_create_nonce('sanad_tracker_materials_nonce'),
                    'i18n'            => [
                        'select_region'              => __('Select a region', 'sanad-tracker'),
                        'loading'                    => __('Loading...', 'sanad-tracker'),
                        'no_materials'               => __('No materials found for this region.', 'sanad-tracker'),
                        'price_placeholder'          => __('Enter price', 'sanad-tracker'),
                        'no_prices'                  => __('Please enter at least one price.', 'sanad-tracker'),
                        'warning'                    => __('Warning', 'sanad-tracker'),
                        'saved'                      => __('Saved!', 'sanad-tracker'),
                        'deleted'                    => __('Deleted!', 'sanad-tracker'),
                        'error'                      => __('Error', 'sanad-tracker'),
                        'confirm_delete_title'       => __('Are you sure?', 'sanad-tracker'),
                        'confirm_delete_text'        => __('This price entry will be permanently deleted.', 'sanad-tracker'),
                        'confirm_delete_yes'         => __('Yes, delete it!', 'sanad-tracker'),
                        'cancel'                     => __('Cancel', 'sanad-tracker'),
                        'no_entries'                 => __('No price entries found.', 'sanad-tracker'),
                        'delete'                     => __('Delete', 'sanad-tracker'),
                        'edit'                       => __('Edit', 'sanad-tracker'),
                        'save'                       => __('Save', 'sanad-tracker'),
                        'confirm_delete_date_title'  => __('Delete all entries?', 'sanad-tracker'),
                        'confirm_delete_date_text'   => __('All prices for this date will be permanently deleted.', 'sanad-tracker'),
                        'deleting'                   => __('Deleting...', 'sanad-tracker'),
                        'saving'                     => __('Saving...', 'sanad-tracker'),
                        'date'                       => __('Date', 'sanad-tracker'),
                        'actions'                    => __('Actions', 'sanad-tracker'),
                    ],
                ],
            ],
            'land_prices' => [
                'handle' => 'sanad-tracker-land-prices',
                'file'   => 'land-prices.js',
                'vars'   => [
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
                ],
            ],
        ];

        if (isset($tabJsMap[$currentTab])) {
            $config = $tabJsMap[$currentTab];
            wp_enqueue_script(
                $config['handle'],
                SANAD_TRACKER_URL . 'assets/admin/js/' . $config['file'],
                $jsDeps,
                SANAD_TRACKER_VERSION,
                true
            );
            wp_localize_script($config['handle'], 'SanadTracker' . str_replace(' ', '', ucwords(str_replace('_', ' ', $currentTab))), $config['vars']);
        }
    }
}
